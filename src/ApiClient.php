<?php

namespace Glamstack\Okta;

use Glamstack\Okta\Traits\ResponseLog;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ApiClient
{
    use ResponseLog;

    const API_VERSION = 1;
    const REQUIRED_CONFIG_PARAMETERS = ['base_url', 'api_token', 'log_channels'];

    private string $api_token;
    private string $base_url;
    private array $connection_config;
    private string $connection_key;
    private array $request_headers;

    public function __construct(
        string $connection_key = null,
        array $connection_config = []
    ) {
        if (empty($connection_config)) {
            $this->setConnectionKeyConfiguration($connection_key);
        } else {
            $this->setCustomConfiguration($connection_config);
        }

        // Set the class api_scopes variable
        $this->setApiToken();

        // Set the class base_url variable
        $this->setBaseUrl();

        // Set request headers
        $this->setRequestHeaders();

        // Test API Connection
        $this->testConnection();
    }

    /**
     * Set the configuration utilizing the `connection_key`
     *
     * This method will utilize the `connection_key` provided in the construct
     * method. The `connection_key` will correspond to a `connection` in the
     * configuration file.
     *
     * @param ?string $connection_key
     *      The connection key to use for configuration.
     *
     * @return void
     */
    protected function setConnectionKeyConfiguration(?string $connection_key): void
    {
        // Set the class connection_key variable.
        $this->setConnectionKey($connection_key);

        // Set the class connection_configuration variable
        $this->setConnectionConfig();
    }

    /**
     * Set the configuration utilizing the `connection_config`
     *
     * This method will utilize the `connection_config` array provided in the
     * construct method. The `connection_config` array keys will have to match
     * the `REQUIRED_CONFIG_PARAMETERS` array
     *
     * @param array $connection_config
     *      Array that contains the required parameters for the connection
     *      configuration
     *
     * @return void
     */
    protected function setCustomConfiguration(array $connection_config): void
    {
        // Validate that `$connection_config` has all required parameters
        $this->validateConnectionConfigArray($connection_config);

        // Set the connection key to `custom` and will be ignored for remainder
        // of the SDK use
        $this->setConnectionKey('custom');

        // Set the connection_config array with the provided array
        $this->setConnectionConfig($connection_config);
    }

    /**
     * Validate that array keys in `REQUIRED_CONFIG_PARAMETERS` exists in the
     * `connection_config`
     *
     * This method will loop through each of the required parameters in 
     * `REQUIRED_CONFIG_PARAMETERS` and verify that each of them are contained
     * in the provided `connection_config` array. If there is a key missing
     * an error will be logged.
     *
     * @param array $connection_config
     *      The connection configuration array provided to the `construct` 
     *      method.
     */
    protected function validateConnectionConfigArray(array $connection_config)
    {
       foreach (self::REQUIRED_CONFIG_PARAMETERS as $parameter) {
           if (!array_key_exists($parameter, $connection_config)) {
               $error_message = 'The Okta ' . $parameter . ' is not defined ' .
                   'in the ApiClient construct conneciton_config array provided. ' .
                   'This is a required parameter to be passed in not using the ' .
                   'configuration file and connection_key initialization method.';

               Log::stack((array) config('glamstack-okta.auth.log_channels'))
                   ->critical(
                       $error_message,
                       [
                           'event_type' => 'okta-api-config-missing-error',
                           'class' => get_class(),
                           'status_code' => '501',
                           'message' => $error_message,
                           'connection_url' => $connection_config['base_url'],
                       ]
                   );
            } else {
                $error_message = 'The Okta SDK connection_config array provided ' .
                    'in the ApiClient construct connection_config array ' .
                    'size should be ' . count(self::REQUIRED_CONFIG_PARAMETERS) .
                    'but ' . count($connection_config) . ' array keys were provided.';

                Log::stack((array) config('glamstack-okta.auth.log_channels'))
                    ->critical(
                        $error_message,
                        [
                            'event_type' => 'okta-api-config-missing-error',
                            'class' => get_class(),
                            'status_code' => '501',
                            'message' => $error_message,
                            'connection_url' => $connection_config['base_url'],
                        ]
                    );
            }
        }
    }


    /**
     * Set the connection_key class property variable
     *
     * @param string $connection_key (Optional) The connection key to use from
     *     the configuration file. If not set, it will use the default connection 
     *     configured in OKTA_DEFAULT_CONNECTION `.env` variable. If the `.env` 
     *     variable is not set, the value in `config/glamstack-okta.php` will be 
     *     used, which has a default of the `prod` connection.
     *
     * @return void
     */
    protected function setConnectionKey(string $connection_key = null): void
    {
        if ($connection_key == null) {
            $this->connection_key = config('glamstack-okta.auth.default_connection');
        } else {
            $this->connection_key = $connection_key;
        }
    }

    /**
     * Set the connection_config class property array 
     *
     * Define an array in the class using the connection configuration in the
     * glamstack-okta.php connections array. If connection key is not specified,
     * an error log will be created and a 501 abort error will be thrown.
     *
     * @return void
     */
    protected function setConnectionConfig(array $custom_configuration = []): void
    {
        if (array_key_exists($this->connection_key, config('glamstack-okta.connections')) && empty($custom_configuration)) {
            $this->connection_config = config('glamstack-okta.connections.' . $this->connection_key);
        } elseif ($custom_configuration) {
            $this->connection_config = $custom_configuration;
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
     * Set the base_url class property variable
     *
     * The base_url variable will use the connection configuration Base URL
     * that is defined in your `.env` file or config/glamstack-okta.php.
     *
     * @return void
     */
    protected function setBaseUrl(): void
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
     * Set the api_token class property variable
     *
     * The api_token variable by default will use the connection configuration
     * API token that is defined in the `.env` file. When instantiating the
     * ApiClient, you can pass a different API token as an argument. This
     * method sets the API token based on whether the argument was provided.
     *
     * @return void
     */
    protected function setApiToken(): void
    {
        // If API token was not provided in construct, use config file value
        if ($this->connection_config['api_token'] != null) {
            $this->api_token = $this->connection_config['api_token'];
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
    public function setRequestHeaders(): void
    {
        // Get Laravel and PHP Version
        $laravel = 'Laravel/' . app()->version();
        $php = 'PHP/' . phpversion();

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
        $package = $composer_package_formatted . '/' . $composer_package['version'];

        // Define request headers
        $this->request_headers = [
            'Authorization' => 'SSWS ' . $this->api_token,
            'User-Agent' => $package . ' ' . $laravel . ' ' . $php
        ];
    }

    /**
     * Test the connection to the Okta API
     *
     * @see https://developer.okta.com/docs/reference/api/org/#get-org-settings
     *
     * @return void
     */
    public function testConnection(): void
    {
        // API call to get Okta organization details (a simple API endpoint)
        // Logging for the request is handled by the get() method.
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
     * Okta API Get Request
     *
     * Example Usage:
     * ```php
     * $okta_api = new \Glamstack\Okta\ApiClient('prod');
     * return $okta_api->get('/users/'.$id);
     * ```
     * @param string $uri The URI with leading slash after `/api/v1`
     *
     * @param array $request_data Optional query data to apply to GET request
     *
     * @return object|string See parseApiResponse() method. The content and
     *      schema of the object and json arrays can be found in the REST API
     *      documentation for the specific endpoint.
     */
    public function get(string $uri, array $request_data = []): object|string
    {
        try {
            // Utilize HTTP to run a GET request against the base URL with the
            // URI supplied from the parameter appended to the end.
            $request = Http::withHeaders($this->request_headers)
                ->get($this->base_url . $uri, $request_data);

            // Parse API Response and convert to returnable object with expected format
            $response = $this->parseApiResponse($request, false);
            $this->logResponse('get', $this->base_url . $uri, $response);

            // If the response is a paginated response
            if ($this->checkForPagination($response->headers) == true) {

                // Get paginated URL and send the request to the getPaginatedResults
                // helper function which loops through all paginated requests
                $paginated_results = $this->getPaginatedResults($this->base_url . $uri);

                // The $paginated_results will be returned as an object of objects
                // which needs to be converted to a flat object for standardizing
                // the response returned. This needs to be a separate function
                // instead of casting to an object due to return body complexities
                // with nested array and object mixed notation.
                $request->paginated_results = $this->convertPaginatedResponseToObject($paginated_results);

                // Unset property for body and json
                unset($request->body);
                unset($request->json);

                // Parse API Response and convert to returnable object with expected format
                // The checkForPagination method will return a boolean that is passed.
                $response = $this->parseApiResponse($request, true);
            }

            return $response;
        } catch (\Illuminate\Http\Client\RequestException $exception) {
            return $this->handleException($exception, get_class(), $uri);
        }
    }

    /**
     * Okta API POST Request
     * 
     * This method is called from other services to perform a POST request and
     * return a structured object.
     *
     * Example Usage:
     * ```php
     * $okta_api = new \Glamstack\Okta\ApiClient('prod');
     * return $okta_api->post('/groups', [
     *      'profile' => [
     *          'name' => 'Hack The Planet Elite Members',
     *          'description' => 'This is for all team members that are elite.'
     *      ]
     * ]);
     * ```
     *
     * @param string $uri The URI with leading slash after `/api/v1`
     *
     * @param array $request_data Optional Post Body array
     *
     * @return object|string See parseApiResponse() method. The content and
     *      schema of the object and json arrays can be found in the REST API
     *      documentation for the specific endpoint.
     */
    public function post(string $uri, array $request_data = []): object|string
    {
        try {
            $request = Http::withHeaders($this->request_headers)
                ->post($this->base_url . $uri, $request_data);

            $response = $this->parseApiResponse($request);

            $this->logResponse('post', $this->base_url . $uri, $response);

            return $response;
        } catch (\Illuminate\Http\Client\RequestException $exception) {
            return $this->handleException($exception, get_class(), $uri);
        }
    }

    /**
     * Okta API PUT Request
     * 
     * This method is called from other services to perform a PUT request and
     * return a structured object.
     *
     * Example Usage:
     * ```php
     * $okta_api = new \Glamstack\Okta\ApiClient('prod');
     * return $okta_api->post('/groups/' . $group_id, [
     *      'profile' => [
     *          'name' => 'Hack The Planet Apprentice Members',
     *          'description' => 'This is for all team members that are not quite elite.'
     *      ]
     * ]);
     * ```
     *
     * @param string $uri The URI with leading slash after `/api/v1`
     *
     * @param array $request_data Optional request data to send with PUT request
     *
     * @return object See parseApiResponse() method. The content and
     *      schema of the object and json arrays can be found in the REST API
     *      documentation for the specific endpoint.
     */
    public function put(string $uri, array $request_data = []): object|string
    {
        try {
            $request = Http::withHeaders($this->request_headers)
                ->put($this->base_url . $uri, $request_data);

            $response = $this->parseApiResponse($request);

            $this->logResponse('put', $this->base_url . $uri, $response);

            return $response;
        } catch (\Illuminate\Http\Client\RequestException $exception) {
            return $this->handleException($exception, get_class(), $uri);
        }
    }

    /**
     * Okta API DELETE Request
     * 
     * This method is called from other services to perform a DELETE request and 
     * return a structured object.
     *
     * Example Usage:
     * ```php
     * $group_id = '00ub0oNGTSWTBKOLGLNR';
     *
     * $okta_api = new \Glamstack\Okta\ApiClient('prod');
     * return $okta_api->delete('/user/'.$group_id);
     * ```
     *
     * @param string $uri The URI with leading slash after `/api/v1`
     *
     * @param array $request_data Optional request data to send with DELETE request
     *
     * @return object|string See parseApiResponse() method. The content and
     *      schema of the object and json arrays can be found in the REST API
     *      documentation for the specific endpoint.
     */
    public function delete(string $uri, array $request_data = []): object|string
    {
        try {
            $request = Http::withHeaders($this->request_headers)
                ->delete($this->base_url . $uri, $request_data);

            $response = $this->parseApiResponse($request);

            $this->logResponse('delete', $this->base_url . $uri, $response);

            return $response;
        } catch (\Illuminate\Http\Client\RequestException $exception) {
            return $this->handleException($exception, get_class(), $uri);
        }
    }

    /**
     * Convert API Response Headers to Object
     * 
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
     *     "Public-Key-Pins-Report-Only" => (truncated),
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
     *     "content-security-policy" => (truncated),
     *     "expect-ct" => "report-uri="https://oktaexpectct.report-uri.com/r/t/ct/reportOnly", max-age=0",
     *     "x-content-type-options" => "nosniff",
     *     "Strict-Transport-Security" => "max-age=315360000; includeSubDomains",
     *     "set-cookie" => (truncated)
     * ]
     */
    public function convertHeadersToArray(array $header_response): array
    {
        $headers = [];

        foreach ($header_response as $header_key => $header_value) {
            // If array has multiple keys, leave as array
            if (count($header_value) > 1) {
                $headers[$header_key] = $header_value;
            } else {
                $headers[$header_key] = $header_value[0];
            }
        }

        return $headers;
    }

    /**
     * Check if the responses uses pagination and contains multiple pages
     *
     * @param array $headers API response headers from Okta request or parsed response.
     *
     * @return bool True if the response requires multiple pages | False if response is a single page
     */
    public function checkForPagination(array $headers): bool
    {
        // If a 'link' header exists, then there is another page to loop
        // <https://mycompany.okta.com/api/v1/apps?after=0oa1ab2c3D4E5F6G7h8i&limit=50>; rel="next"
        if (array_key_exists('link', $headers)) {
            if (is_array($headers['link'])) {
                foreach ($headers['link'] as $link_key => $link_url) {
                    if (Str::contains($link_url, 'next')) {
                        return true;
                    }
                }
            }

            // If no links contain next, return false result
            return false;
        } else {
            // If links array key does not exist, return false result
            return false;
        }
    }

    /**
     * Parse the header array for the paginated URL that contains `next`.
     *
     * Note: The Okta SDK uses a cursor pagination instead of page navigation.
     *
     * @see https://developer.okta.com/docs/reference/core-okta-api/#pagination
     *
     * @param array $headers API response headers from Okta request or parsed response.
     *
     * @return ?string URL string or null if not found
     */
    public function generateNextPaginatedResultUrl(array $headers): ?string
    {
        // If a 'link' header exists, then there is another page to loop
        // <https://mycompany.okta.com/api/v1/apps?after=0oa1ab2c3D4E5F6G7h8i&limit=50>; rel="next"
        if (array_key_exists('link', $headers)) {
            foreach ($headers['link'] as $link_key => $link_url) {
                if (Str::contains($link_url, 'next')) {
                    // Remove the '<' and '>; rel="next"' that is around the next api_url
                    // Before: <https://mycompany.okta.com/api/v1/apps?after=0oa1ab2c3D4E5F6G7h8i&limit=50>; rel="next"
                    // After: https://mycompany.okta.com/api/v1/apps?after=0oa1ab2c3D4E5F6G7h8i&limit=50
                    $url = Str::remove('<', $headers['link'][$link_key]);
                    $url = Str::remove('>; rel="next"', $url);

                    return $url;
                }
            }

            // If no links contain next, return null result
            return null;
        } else {
            return null;
        }
    }

    /**
     * Helper function used to get Okta API results that require pagination.
     *
     * @see https://developer.okta.com/docs/reference/core-okta-api/#pagination
     *
     * Example Usage:
     * ```php
     * $this->getPaginatedResults('/users');
     * ```
     *
     * @param string $endpoint The endpoint to use Okta API on.
     *
     * @param mixed $query_string Optional request data to send with GET request
     *
     * @return array An array of the response objects for each page combined.
     */
    public function getPaginatedResults(string $paginated_url): array
    {
        // Define empty array for adding API results to
        $records = [];

        // Perform API calls while $api_url is not null
        do {
            // Get the record
            $request = Http::withHeaders($this->request_headers)
                ->get($paginated_url);

            $response = $this->parseApiResponse($request);
            $this->logResponse('get', $paginated_url, $response);

            // Loop through each object from the response and add it to
            // the $records array
            foreach ($response->object as $api_record) {
                $records[] = $api_record;
            }

            // Get next page of results by parsing link and updating URL
            if ($this->checkForPagination($response->headers)) {
                $paginated_url = $this->generateNextPaginatedResultUrl($response->headers);
            } else {
                $paginated_url = null;
            }
        } while ($paginated_url != null);

        return $records;
    }

    /**
     * Convert paginated API response array into an object
     *
     * @param array $paginatedResponse Combined object returns from multiple pages of
     * API responses.
     *
     * @return object Object of the API responses combined.
     */
    public function convertPaginatedResponseToObject(array $paginatedResponse): object
    {
        $results = [];

        foreach ($paginatedResponse as $response_key => $response_value) {
            $results[$response_key] = $response_value;
        }
        return (object) $results;
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
