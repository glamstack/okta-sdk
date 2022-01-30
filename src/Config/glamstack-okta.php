<?php

return [

    /**
     * Log Channels
     * ------------------------------------------------------------------------
     * Throughout the SDK, we use the config('glamstack-okta.log_channels')
     * array variable to allow you to set the log channels (custom log stack)
     * that you want API logs to be sent to.
     *
     * If you leave this at the value of `['single']`, all API call logs will
     * be sent to the default log file for Laravel that you have configured
     * in config/logging.php which is usually storage/logs/laravel.log.
     *
     * If you would like to see Okta API logs in a separate log file that
     * is easier to triage without unrelated log messages, you can create a
     * custom log channel and add the channel name to the array. For example,
     * we recommend creating a custom channel with the name `glamstack-okta`,
     * however you can choose any name you would like.
     * Ex. ['single', 'glamstack-okta']
     *
     * You can also add additional channels that logs should be sent to.
     * Ex. ['single', 'glamstack-okta', 'slack']
     *
     * https://laravel.com/docs/8.x/logging
     */

    'auth' => [
        'default_connection' => env('OKTA_DEFAULT_CONNECTION', 'prod'),
        'log_channels' => ['single'],
    ],

    /**
     * Okta Instances
     * ------------------------------------------------------------------------
     * Each Okta customer is provided with a subdomain for their organization.
     * Ex. `https://mycompany.okta.com`
     *
     * If you have access to the Okta Preview sandbox/testing/staging instance,
     * you can also configure an Base URL and API token in the `preview` key.
     * Ex. `https://mycompany.oktapreview.com`
     *
     * If you have a free Okta developer account, you can configure the Base URL
     * and API token in the `dev` key.
     *
     * @see https://developer.okta.com/signup/
     *
     * If you have the rare use case where you have additional Okta instances
     * that you connect to, you can add an additional connection keys below
     * with the name of your choice and create new variables for the Base URL
     * and API token using the other instances as examples.
     *
     * You need to specify the URL for each instance in your `.env` or in the
     * array below. You will need to create an API token for each instance.
     *
     * To avoid using the connection key in the construct arguments when using
     * the ApiClient, you can set the `OKTA_DEFAULT_CONNECTION` variable in
     * your `.env` file that you want to use for any ApiClients that are not
     * explicitly defined. This allows you to globally switch between `dev`,
     * `preview`, `prod`, and any other instances that you have configured.
     * See the README for more example usage.
     *
     * @see https://developer.okta.com/docs/guides/create-an-api-token/main/
     *
     * Keep in mind that the API token uses the permissions for the user it
     * belongs to, so it is a best practice to create a service account
     * (bot) user for production application use cases. Any tokens that are
     * inactive for 30 days without API calls will automatically expire.
     *
     * Security Warning: It is important that you don't add your API token to
     * this file to avoid committing to your repository (secret leak). All
     * API tokens should be defined in the `.env` file which is included
     * in `.gitignore` and not committed to your repository.
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
