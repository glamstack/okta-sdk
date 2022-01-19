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

    'log_channels' => ['single'],

];
