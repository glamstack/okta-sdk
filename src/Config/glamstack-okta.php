<?php

return [

    /**
     * ------------------------------------------------------------------------
     * Okta Auth Configuration
     * ------------------------------------------------------------------------
     *
     * @param string $default_connection The connection key (array key) of the 
     *.     connection that you want to use if not specified when instantiating 
     *.     the ApiClient.
     *     
     *      This allows you to globally switch between `dev`, `preview`, `prod`, 
     *      and any other connections that you have configured.
     * 
     * @param array $log_channels The Laravel log channels to send all related 
     *      info and error logs to for authentication config validation. If you
     *      leave this at the value of `['single']`, all API call logs will be 
     *      sent to the default log file for Laravel that you have configured in
     *      `config/logging.php` which is usually `storage/logs/laravel.log`.
     *
     *      If you would like to see Okta API logs in a separate log file that 
     *      is easier to triage without unrelated log messages, you can create 
     *      a custom log channel and add the channel name to the array. We 
     *      recommend creating a custom channel (ex. `glamstack-okta`), however 
     *      you can choose any name you would like.
     *      Ex. ['single', 'glamstack-google-example']
     *
     *      You can also add additional channels that logs should be sent to.
     *      Ex. ['single', 'glamstack-google-example', 'slack']
     *
     *      @see https://laravel.com/docs/8.x/logging
     */

    'auth' => [
        'default_connection' => env('OKTA_DEFAULT_CONNECTION', 'prod'),
        'log_channels' => ['single'],
    ],

    /**
     * ------------------------------------------------------------------------
     * Connections Configuration
     * ------------------------------------------------------------------------
     * 
     * To allow for least privilege access and multiple API keys, the SDK uses
     * this configuration section for configuring each of the API keys that
     * you use and configuring the different Base URLs for each token.
     *
     * Each connection has an array key that we refer to as the "connection 
     * key" that contains a array of configuration values that is used when 
     * the ApiClient is instantiated.
     * 
     * If you have the rare use case where you have additional Okta instances
     * that you connect to beyond what is pre-configured below, you can add 
     * an additional connection keys below with the name of your choice and 
     * create new variables for the Base URL and API token using the other 
     * instances as examples.
     *
     * ```php
     * $okta_api = new \Glamstack\Okta\ApiClient('prod');
     * ```
     *
     * You can add the `OKTA_DEFAULT_CONNECTION` variable in your .env file so 
     * you don't need to pass the connection key into the ApiClient. The `prod` 
     * connection key is used if the `.env` variable is not set.
     *
     * ```php
     * $okta_api = new \Glamstack\Okta\ApiClient();
     * ```
     * 
     * @param string $base_url The URL to to use for the ApiClient connection.
     *      This should usually use an `.env` variable, however can be 
     *      statically  set in the configuration array below if desired.
     *
     *      Each Okta customer is provided with a subdomain for their company. 
     *      This is sometimes referred to as a tenant or ${yourOktaDomain} in 
     *      the API documentation.
     *      Ex. `https://mycompany.okta.com`
     *
     *      If you have access to the Okta Preview sandbox/testing/staging 
     *      instances, you can also configure a Base URL and API token in the 
     *      `preview` key.
     *      Ex. `https://mycompany.oktapreview.com`
     *
     *      If you have a free Okta developer account, you can configure the 
     *      Base URL and API token in the `dev` key.
     *
     *      @see https://developer.okta.com/signup/
     *
     * @param string $api_token The API token for the respective connection.
     * 
     *      @see https://developer.okta.com/docs/guides/create-an-api-token/main/
     * 
     *      Security Warning: It is important that you don't add your API token
     *      to this file to avoid committing to your repository (secret leak). 
     *      All API tokens should be defined in the `.env` file which is 
     *      included in `.gitignore` and not committed to your repository.
     *
     *      Keep in mind that the API token uses the permissions for the user 
     *      it belongs to, so it is a best practice to create a service account
     *      (bot) user for production application use cases. Any tokens that are 
     *      inactive for 30 days without API calls will automatically expire.
     * 
     * @param array $log_channels The Laravel log channels to send all related 
     *      info and error logs to for for this Okta instance (connection).
     *      
     *      If you leave this at the value of `['single']`, all API call logs 
     *      will be sent to the default log file for Laravel that you have 
     *      configured in `config/logging.php` which is usually 
     *      `storage/logs/laravel.log`.
     *
     *      If you would like to see Okta API logs in a separate log file that 
     *      is easier to triage without unrelated log messages, you can create 
     *      a custom log channel and add the channel name to the array. We 
     *      recommend creating a custom channel (ex. `glamstack-okta` for all 
     *      connections or `glamstack-okta-prod` for a specific connection), 
     *      however you can choose any name you would like.
     *      Ex. ['single', 'glamstack-google-example']
     *
     *      You can also add additional channels that logs should be sent to.
     *      Ex. ['single', 'glamstack-google-example', 'slack']
     *
     *      @see https://laravel.com/docs/8.x/logging
     */

    'connections' => [

        'prod' => [
            'base_url' => env('OKTA_PROD_BASE_URL'),
            'api_token' => env('OKTA_PROD_API_TOKEN'),
            'log_channels' => ['single'],
        ],

        'preview' => [
            'base_url' => env('OKTA_PREVIEW_BASE_URL'),
            'api_token' => env('OKTA_PREVIEW_API_TOKEN'),
            'log_channels' => ['single'],
        ],

        'dev' => [
            'base_url' => env('OKTA_DEV_BASE_URL'),
            'api_token' => env('OKTA_DEV_API_TOKEN'),
            'log_channels' => ['single'],
        ],

    ],

];
