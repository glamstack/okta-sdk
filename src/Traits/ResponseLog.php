<?php

namespace GitlabIt\Okta\Traits;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

trait ResponseLog
{
    /**
     * Create a log entry for an API call
     *
     * This method is called from other methods and will call specific methods
     * depending on the log severity level.
     *
     * @param string $method The lowercase name of the method that calls this
     * function (ex. `get`)
     *
     * @param string $url The URL of the API call including the concatenated
     * base URL and URI
     *
     * @param object $response The HTTP response formatted with
     * $this->parseApiResponse()
     *
     * @return void
     */
    public function logResponse(string $method, string $url, object $response): void
    {
        // Status code log messages (2xx, 4xx, 5xx)
        if ($response->status->ok == true) {
            $this->logInfo($method, $url, $response);
        } elseif ($response->status->clientError == true) {
            $this->logClientError($method, $url, $response);
        } elseif ($response->status->serverError == true) {
            $this->logServerError($method, $url, $response);
        }

        // Conditional logs for rate limits
        if (array_key_exists('x-rate-limit-remaining', $response->headers)) {
            $this->warningLogIfRateLimitApproaching($method, $url, $response);
            $this->errorLogIfRateLimitExceeded($method, $url, $response);
        }
    }

    /**
     * Create an info log entry for an API call
     *
     * @param string $method The lowercase name of the method that calls this
     * function (ex. `get`)
     *
     * @param string $url The URL of the API call including the concatenated
     * base URL and URI
     *
     * @param object $response The HTTP response formatted with
     * $this->parseApiResponse()
     *
     * @return void
     */
    public function logInfo(string $method, string $url, object $response): void
    {
        $message = Str::upper($method).' '.$response->status->code.' '.$url;

        Log::stack((array) $this->connection_config['log_channels'])
            ->info($message, [
                'api_endpoint' => $url,
                'api_method' => Str::upper($method),
                'class' => get_class(),
                'connection_key' => $this->connection_key,
                'message' => $message,
                'event_type' => 'okta-api-response-info',
                'okta_request_id' => $response->headers['x-okta-request-id'] ?? null,
                'rate_limit_remaining' => $response->headers['x-rate-limit-remaining'] ?? null,
                'status_code' => $response->status->code,
            ]);
    }

    /**
     * Create a notice log entry for an API call for client errors (4xx status)
     *
     * @param string $method The lowercase name of the method that calls this
     * function (ex. `get`)
     *
     * @param string $url The URL of the API call including the concatenated
     * base URL and URI
     *
     * @param object $response The HTTP response formatted with
     * $this->parseApiResponse()
     *
     * @return void
     */
    public function logClientError(string $method, string $url, object $response): void
    {
        $message = Str::upper($method).' '.$response->status->code.' '.$url;

        Log::stack((array) $this->connection_config['log_channels'])
            ->notice($message, [
                'api_endpoint' => $url,
                'api_method' => Str::upper($method),
                'class' => get_class(),
                'connection_key' => $this->connection_key,
                'event_type' => 'okta-api-response-client-error',
                'message' => $message,
                'okta_request_id' => $response->headers['x-okta-request-id'] ?? null,
                'okta_error_causes' => $response->object->errorCauses ?? null,
                'okta_error_code' => $response->object->errorCode ?? null,
                'okta_error_id' => $response->object->errorId ?? null,
                'okta_error_link' => $response->object->errorLink ?? null,
                'okta_error_summary' => $response->object->errorSummary ?? null,
                'rate_limit_remaining' => $response->headers['x-rate-limit-remaining'] ?? null,
                'status_code' => $response->status->code,
            ]);
    }

    /**
     * Create an error log entry for an API call for server errors (5xx status)
     *
     * @param string $method The lowercase name of the method that calls this
     * function (ex. `get`)
     *
     * @param string $url The URL of the API call including the concatenated
     * base URL and URI
     *
     * @param object $response The HTTP response formatted with
     * $this->parseApiResponse()
     *
     * @return void
     */
    public function logServerError(string $method, string $url, object $response): void
    {
        $message = Str::upper($method) . ' ' . $response->status->code . ' ' . $url;

        Log::stack((array) $this->connection_config['log_channels'])
            ->error($message, [
                'api_endpoint' => $url,
                'api_method' => Str::upper($method),
                'class' => get_class(),
                'connection_key' => $this->connection_key,
                'event_type' => 'okta-api-response-server-error',
                'message' => $message,
                'okta_request_id' => $response->headers['x-okta-request-id'] ?? null,
                'okta_error_causes' => $response->object->errorCauses ?? null,
                'okta_error_code' => $response->object->errorCode ?? null,
                'okta_error_id' => $response->object->errorId ?? null,
                'okta_error_link' => $response->object->errorLink ?? null,
                'okta_error_summary' => $response->object->errorSummary ?? null,
                'rate_limit_remaining' => $response->headers['x-rate-limit-remaining'] ?? null,
                'status_code' => $response->status->code,
            ]);
    }

    /**
     * Create a warning log entry for an API call if the rate limit remaining
     * is less than 10 percent that is calculated in the method
     *
     * @param string $method The lowercase name of the method that calls this
     * function (ex. `get`)
     *
     * @param string $url The URL of the API call including the concatenated
     * base URL and URI
     *
     * @param object $response The HTTP response formatted with
     * $this->parseApiResponse()
     *
     * @return void
     */
    public function warningLogIfRateLimitApproaching(string $method, string $url, object $response): void
    {
        if (array_key_exists('x-rate-limit-remaining', $response->headers)) {
            // Calculate percentage of rate limit remaining
            $rate_limit_percent_remaining = round(((int) $response->headers['x-rate-limit-remaining'] / (int) $response->headers['x-rate-limit-limit']) * 100);

            // If percentage remaining is less than 10%, add a warning log
            if ($rate_limit_percent_remaining <= 10) {
                $message = $rate_limit_percent_remaining .
                    ' percent of Okta API rate limit remaining';

                Log::stack((array) $this->connection_config['log_channels'])
                    ->warning($message, [
                        'api_endpoint' => $url,
                        'api_method' => Str::upper($method),
                        'class' => get_class(),
                        'connection_key' => $this->connection_key,
                        'event_type' => 'okta-api-rate-limit-approaching',
                        'message' => $message,
                        'okta_request_id' => $response->headers['x-okta-request-id'] ?? null,
                        'okta_rate_limit_remaining' => $response->headers['x-rate-limit-remaining'] ?? null,
                        'okta_rate_limit_limit' => $response->headers['x-rate-limit-limit'] ?? null,
                        'okta_rate_limit_percent' => $rate_limit_percent_remaining,
                        'status_code' => $response->status->code,
                    ]);
            }
        }
    }

    /**
     * Create an error log entry for an API call if the rate limit remaining
     * is equal to zero (0) or one (1), indicating that this is the last
     * request that will be successful.
     *
     * @param string $method The lowercase name of the method that calls this
     * function (ex. `get`)
     *
     * @param string $url The URL of the API call including the concatenated
     * base URL and URI
     *
     * @param object $response The HTTP response formatted with
     * $this->parseApiResponse()
     *
     * @return void
     */
    public function errorLogIfRateLimitExceeded(
        string $method,
        string $url,
        object $response
    ): void {
        if (array_key_exists('x-rate-limit-remaining', $response->headers)) {
            // If count remaining is 1 or zero, add a error log
            if ($response->headers['x-rate-limit-remaining'] <= 1) {
                $message = 'Okta API rate limit exceeded';

                Log::stack((array) $this->connection_config['log_channels'])
                    ->error($message, [
                        'event_type' => 'okta-api-rate-limit-exceeded',
                        'class' => get_class(),
                        'status_code' => $response->status->code,
                        'message' => $message,
                        'api_method' => Str::upper($method),
                        'api_endpoint' => $url,
                        'connection_key' => $this->connection_key,
                        'okta_request_id' => $response->headers['x-okta-request-id'] ?? null,
                        'okta_rate_limit_remaining' => $response->headers['x-rate-limit-remaining'] ?? null,
                        'okta_rate_limit_limit' => $response->headers['x-rate-limit-limit'] ?? null,
                    ]);
            }
        }
    }
}
