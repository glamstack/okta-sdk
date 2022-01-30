<?php

namespace Glamstack\Okta;

use Glamstack\Okta\Traits\ResponseLog;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ApiClient
{
    const API_VERSION = 1;

    private ?string $api_token;
    private ?string $base_url;
    private array $connection_config;
    private ?string $connection_key;
    private array $request_headers;

    public function __construct(
        string $connection_key = null,
        string $api_token = null
    ) {
        // Set the class connection_key variable.
        $this->setConnectionKey($connection_key);

        // Set the class connection_configuration variable
        $this->setConnectionConfig();

        // Set the class base_url variable.
        $this->setBaseUrl();

        // Set the class api_scopes variable.
        $this->setApiToken($api_token);

        // Set request headers
        $this->setRequestHeaders();

        // Test API Connection
        $this->testConnection();
    }

    /**
     * Set the connection_key class variable
     *
     * The connection_key variable by default will be set to `prod`. This can
     * be overridden when initializing the SDK with a different connection key
     * which is passed into this function to set the class variable to the key.
     *
     * @param string $connection_key (Optional) The connection key to use from
     * the configuration file.
     *
     * @return void
     */
    protected function setConnectionKey(?string $connection_key) : void
    {
        if ($connection_key == null) {
            $this->connection_key = config('glamstack-okta.auth.default_connection');
        } else {
            $this->connection_key = $connection_key;
        }
    }

    /**
     * Define an array in the class using the connection configuration in the
     * glamstack-okta.php connections array. If connection key is not specified,
     * an error log will be created and this function will return false.
     *
     * @return void
     */
    protected function setConnectionConfig(): void
    {
        if (array_key_exists($this->connection_key, config('glamstack-okta.connections'))) {
            $this->connection_config = config('glamstack-okta.connections.' . $this->connection_key);
        } else {
            $error_message = 'The Okta connection key is not defined in ' .
                '`config/glamstack-okta.php` connections array. Without this ' .
                'array config, there is no URL or API token to connect with.';

            Log::stack((array) config('glamstack-okta.auth.log_channels'))
                ->critical($error_message, [
                    'event_type' => 'okta-api-config-missing-error',
                    'class' => get_class(),
                    'status_code' => '501',
                    'message' => $error_message,
                    'connection_key' => $this->connection_key,
                ]);

            abort(501, $error_message);
        }
    }

    /**
     * Set the base_url class variable
     *
     * The base_url variable will use the connection configuration Base URL
     * that is defined in your `.env` file or config/glamstack-okta.php.
     *
     * @return void
     */
    protected function setBaseUrl() : void
    {
        if ($this->connection_config['base_url'] != null) {
            $this->base_url = $this->connection_config['base_url'] . '/api/v' . self::API_VERSION;
        } else {
            $error_message = 'The Base URL for this Okta connection key ' .
                'is not defined in `config/glamstack-okta.php` or `.env` file. ' .
                'Without this configuration (ex. `https://mycompany.okta.com`), ' .
                'there is no URL to perform API calls with.';

            Log::stack((array) config('glamstack-okta.auth.log_channels'))
                ->critical($error_message, [
                    'event_type' => 'okta-api-config-missing-error',
                    'class' => get_class(),
                    'status_code' => '501',
                    'message' => $error_message,
                    'connection_key' => $this->connection_key,
                ]);

            abort(501, $error_message);
        }
    }

    /**
     * Set the api_token class variable
     *
     * The api_token variable by default will use the connection configuration
     * API token that is defined in the `.env` file. When instantiating the
     * ApiClient, you can pass a different API token as an argument. This
     * method sets the API token based on whether the argument was provided.
     *
     * @param string|null $api_token
     * @return void
     */
    protected function setApiToken(?string $api_token) : void
    {
        // If API token was not provided in construct, use config file value
        if ($api_token == null && $this->connection_config['api_token'] != null) {
            $this->api_token = $this->connection_config['api_token'];

        // If API token was provided, override config file value
        } elseif ($api_token != null) {
            $this->api_token = $api_token;

            $info_message = 'The Okta API token for these API calls is using an ' .
                'API token that was provided in the ApiClient construct ' .
                'method. The API token that might be configured in the ' .
                '`.env` file is not being used.';

            Log::stack((array) config('glamstack-okta.auth.log_channels'))
                ->notice($info_message, [
                    'event_type' => 'okta-api-config-override-notice',
                    'class' => get_class(),
                    'status_code' => '203',
                    'message' =>  $info_message,
                    'okta_connection' => $this->connection_key,
                ]);
        // If API token is not defined, abort with an error message
        } else {
            $error_message = 'The API token for this Okta connection key ' .
                'is not defined in your `.env` file. The variable name for the ' .
                'API token can be found in the connection configuration in ' .
                '`config/glamstack-okta.php`. Without this API token, you will ' .
                'not be able to performed authenticated API calls.';

            Log::stack((array) config('glamstack-okta.auth.log_channels'))
                ->critical($error_message, [
                    'event_type' => 'okta-api-config-missing-error',
                    'class' => get_class(),
                    'status_code' => '501',
                    'message' => $error_message,
                    'connection_key' => $this->connection_key,
                ]);

            abort(501, $error_message);
        }
    }

    /**
     * Set the request headers for the Okta API request
     *
     * @return void
     */
    public function setRequestHeaders() : void
    {
        // Get Laravel and PHP Version
        $laravel = 'Laravel/'.app()->version();
        $php = 'PHP/'.phpversion();

        // Decode the composer.lock file
        $composer_lock_json = json_decode((string) file_get_contents(base_path('composer.lock')), true);

        // Use Laravel collection to search for the package. We will use the
        // array to get the package name (in case it changes with a fork) and
        // return the version key. For production, this will show a release
        // number. In development, this will show the branch name.
        /** @phpstan-ignore-next-line */
        $composer_package = collect($composer_lock_json['packages'])
            ->where('name', 'glamstack/okta-sdk')
            ->first();

        // Reformat `glamstack/okta-sdk` as `Glamstack-Okta-Sdk`
        $composer_package_formatted = Str::title(Str::replace('/', '-', $composer_package['name']));
        $package = $composer_package_formatted.'/'.$composer_package['version'];

        // Define request headers
        $this->request_headers = [
            'Authorization' => 'SSWS ' . $this->api_token,
            'User-Agent' => $package.' '.$laravel.' '.$php
        ];
    }

    /**
     * Test the connection to the Okta connection
     *
     * @see https://developer.okta.com/docs/reference/api/org/#get-org-settings
     *
     * @return void
     */
    public function testConnection() : void
    {
        // API call to get Okta organization details (a simple API endpoint)
        $response = $this->get('/org');

        if ($response->status->ok == false) {
            if (property_exists($response->object, 'errorCode')) {
                $error_message = 'Okta API Error ' . $response->object->errorCode . ' - ' .
                $response->object->errorSummary;
            } else {
                $error_message = 'The Okta API connection test failed for an unknown reason. See logs for details.';
            }
            abort($response->status->code, $error_message);
        }
    }

    /**
     * Convert API Response Headers to Object
     * This method is called from the parseApiResponse method to prettify the
     * Guzzle Headers that are an array with nested array for each value, and
     * converts the single array values into strings and converts to an object for
     * easier and consistent accessibility with the parseApiResponse format.
     *
     * @param array $header_response
     * [
     *     "Date" => array:1 [
     *       0 => "Sun, 30 Jan 2022 01:18:14 GMT"
     *     ]
     *     "Content-Type" => array:1 [
     *       0 => "application/json"
     *     ]
     *     "Transfer-Encoding" => array:1 [
     *       0 => "chunked"
     *     ]
     *     "Connection" => array:1 [
     *       0 => "keep-alive"
     *     ]
     *     "Server" => array:1 [
     *       0 => "nginx"
     *     ]
     *     // ...
     * ]
     *
     * @return array
     * [
     *     "Date" => "Sun, 30 Jan 2022 01:11:44 GMT",
     *     "Content-Type" => "application/json",
     *     "Transfer-Encoding" => "chunked",
     *     "Connection" => "keep-alive",
     *     "Server" => "nginx",
     *     "Public-Key-Pins-Report-Only" => "pin-sha256="REDACTED="; pin-sha256="REDACTED="; pin-sha256="REDACTED="; pin-sha256="REDACTED="; max-age=60; report-uri="https://okta.report-uri.com/r/default/hpkp/reportOnly"",
     *     "Vary" => "Accept-Encoding",
     *     "x-okta-request-id" => "A1b2C3D4e5@f6G7H8I9j0k1L2M3",
     *     "x-xss-protection" => "0",
     *     "p3p" => "CP="HONK"",
     *     "x-rate-limit-limit" => "1000",
     *     "x-rate-limit-remaining" => "998",
     *     "x-rate-limit-reset" => "1643505155",
     *     "cache-control" => "no-cache, no-store",
     *     "pragma" => "no-cache",
     *     "expires" => "0",
     *     "content-security-policy" => "default-src 'self' mycompany.okta.com *.oktacdn.com; connect-src 'self' mycompany.okta.com mycompany-admin.okta.com *.oktacdn.com *.mixpanel.com *.mapbox.com app.pendo.io data.pendo.io pendo-static-5634101834153984.storage.googleapis.com mycompany.kerberos.okta.com https://oinmanager.okta.com data:; script-src 'unsafe-inline' 'unsafe-eval' 'self' mycompany.okta.com *.oktacdn.com; style-src 'unsafe-inline' 'self' mycompany.okta.com *.oktacdn.com app.pendo.io cdn.pendo.io pendo-static-5634101834153984.storage.googleapis.com; frame-src 'self' mycompany.okta.com mycompany-admin.okta.com login.okta.com; img-src 'self' mycompany.okta.com *.oktacdn.com *.tiles.mapbox.com *.mapbox.com app.pendo.io data.pendo.io cdn.pendo.io pendo-static-5634101834153984.storage.googleapis.com data: blob:; font-src 'self' mycompany.okta.com data: *.oktacdn.com fonts.gstatic.com",
     *     "expect-ct" => "report-uri="https://oktaexpectct.report-uri.com/r/t/ct/reportOnly", max-age=0",
     *     "x-content-type-options" => "nosniff",
     *     "Strict-Transport-Security" => "max-age=315360000; includeSubDomains",
     *     "set-cookie" => "sid=""; Expires=Thu, 01-Jan-1970 00:00:10 GMT; Path=/ autolaunch_triggered=""; Expires=Thu, 01-Jan-1970 00:00:10 GMT; Path=/ JSESSIONID=E07ED763D2ADBB01B387772B9FB46EBF; Path=/; Secure; HttpOnly"
     * ]
     */
    public function convertHeadersToArray(array $header_response): array
    {
        $headers = [];

        foreach ($header_response as $header_key => $header_value) {
            // If array has multiple keys, leave as array
            if (count($header_value) > 1) {
                $headers[$header_key] = $header_value;

            // If array has a single key, convert to a string
            } else {
                $headers[$header_key] = $header_value[0];
            }
        }

        return $headers;
    }

    /**
     * Parse the API response and return custom formatted response for consistency
     *
     * @see https://laravel.com/docs/8.x/http-client#making-requests
     *
     * @param object $response Response object from API results
     *
     * @param false $paginated If the response is paginated or not
     *
     * @return object Custom response returned for consistency
     *  {
     *    +"headers": [
     *      "Date" => "Fri, 12 Nov 2021 20:13:55 GMT",
     *      "Content-Type" => "application/json",
     *      "Content-Length" => "1623",
     *      "Connection" => "keep-alive"
     *    ],
     *    +"json": "{"id":12345678,"name":"Dade Murphy","username":"z3r0c00l","state":"active"}"
     *    +"object": {
     *      +"id": 12345678
     *      +"name": "Dade Murphy"
     *      +"username": "z3r0c00l"
     *      +"state": "active"
     *    }
     *    +"status": {
     *      +"code": 200
     *      +"ok": true
     *      +"successful": true
     *      +"failed": false
     *      +"serverError": false
     *      +"clientError": false
     *   }
     * }
     */
    public function parseApiResponse(object $response, bool $paginated = false): object
    {
        return (object) [
            'headers' => $this->convertHeadersToArray($response->headers()),
            'json' => $paginated == true ? json_encode($response->paginated_results) : json_encode($response->json()),
            'object' => $paginated == true ? (object) $response->paginated_results : $response->object(),
            'status' => (object) [
                'code' => $response->status(),
                'ok' => $response->ok(),
                'successful' => $response->successful(),
                'failed' => $response->failed(),
                'serverError' => $response->serverError(),
                'clientError' => $response->clientError(),
            ],
        ];
    }

    /**
     * Handle Okta API Exception
     *
     * @see https://developer.okta.com/docs/reference/error-codes/
     *
     * @param \Illuminate\Http\Client\RequestException $exception An instance of the exception
     *
     * @param string $log_class get_class()
     *
     * @param string $reference Reference slug or identifier
     *
     * @return string Error message
     */
    public function handleException($exception, $log_class, $reference)
    {
        Log::stack((array) $this->connection_config['log_channels'])
            ->error($exception->getMessage(), [
                'class' => $log_class,
                'connection_key' => $this->connection_key,
                'event_type' => 'okta-sdk-exception-error',
                'exception' => $exception,
                'message' => $exception->getMessage(),
                'reference' => $reference,
                'status_code' => $exception->getCode(),
            ]);

        return $exception->getMessage();
    }
}
