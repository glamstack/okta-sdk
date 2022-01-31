# Okta SDK

## Overview

The Okta SDK is an open source [Composer](https://getcomposer.org/) package created by [GitLab IT Engineering](https://about.gitlab.com/handbook/business-technology/engineering/) for use in the [GitLab Access Manager](https://gitlab.com/gitlab-com/business-technology/engineering/access-manager) Laravel application for connecting to Okta instances for provisioning and deprovisioning of users, groups, applications, and other related functionality.

> **Disclaimer:** This is not an official package maintained by the GitLab or Okta product and development teams. This is an internal tool that we use in the IT department that we have open sourced as part of our company values.
>
> Please use at your own risk and create issues for any bugs that you encounter.
>
> We do not maintain a roadmap of community feature requests, however we invite you to contribute and we will gladly review your merge requests.

### Maintainers

| Name | GitLab Handle |
|------|---------------|
| [Dillon Wheeler](https://about.gitlab.com/company/team/#dillonwheeler) | [@dillonwheeler](https://gitlab.com/dillonwheeler) |
| [Jeff Martin](https://about.gitlab.com/company/team/#jeffersonmartin) | [@jeffersonmartin](https://gitlab.com/jeffersonmartin) |

### How It Works

The URL of your Okta instance (ex. `https://mycompany.okta.com`) and API Token is specified in `config/glamstack-okta.php` using variables inherited from your `.env` file.

The package is not intended to provide functions for every endpoint in the Okta API.

We have taken a simpler approach by providing a universal `ApiClient` that can perform `GET`, `POST`, `PUT`, and `DELETE` requests to any endpoint that you find in the [Okta API documentation](https://developer.okta.com/docs/reference/core-okta-api/) and handles the API response, error handling, and pagination for you.

This builds upon the simplicity of the [Laravel HTTP Client](https://laravel.com/docs/8.x/http-client) that is powered by the [Guzzle HTTP client](http://docs.guzzlephp.org/en/stable/) to provide "last lines of code parsing" for Okta API responses to improve the developer experience.

We have additional classes and methods for the endpoints that GitLab Access Manager uses frequently that we will [iterate](https://about.gitlab.com/handbook/values/#iteration) upon over time.

```php
// Initialize the SDK
$okta_api = new \Glamstack\Okta\ApiClient('prod');

// Get a list of records
// https://developer.okta.com/docs/reference/api/groups/#list-groups
$groups = $okta_api->get('/groups');

// Search for records with a specific name
// https://developer.okta.com/docs/reference/api/groups/#list-groups
// https://developer.okta.com/docs/reference/core-okta-api/#filter
$groups = $okta_api->get('/groups', [
    'q' => 'Hack the Planet Engineers'
]);

// Search for records with a keyword across most metadata fields
// https://developer.okta.com/docs/reference/api/users/#list-users
// https://developer.okta.com/docs/reference/core-okta-api/#filter
$users = $okta_api->get('/users', [
    'filter' => 'firstName eq "Dade"'
]);

// Get a specific record
// https://developer.okta.com/docs/reference/api/groups/#get-group
$record = $okta_api->get('/groups/0oa1ab2c3D4E5F6G7h8i');

// Create a group
// https://developer.okta.com/docs/reference/api/groups/#add-group
$record = $okta_api->post('/groups', [
    'name' => 'Hack the Planet Engineers',
    'description' => 'This group contains engineers that have proven they are elite enough to hack the Gibson.'
]);

// Update a group
// https://developer.okta.com/docs/reference/api/groups/#update-group
$group_id = '0oa1ab2c3D4E5F6G7h8i';
$record = $okta_api->put('/groups/' . $group_id, [
    'description' => 'This group contains engineers that have liberated the garbage files.'
]);

// Delete a group
// https://developer.okta.com/docs/reference/api/groups/#remove-group
$group_id = '0oa1ab2c3D4E5F6G7h8i';
$record = $okta_api->delete('/groups/' . $group_id);
```

See the [API Requests](#api-requests) and [API Responses](#api-responses) section below for more details.

## Installation

### Requirements

| Requirement | Version |
|-------------|---------|
| PHP         | >=8.0   |
| Laravel     | >=8.0   |

### Add Composer Package

```bash
composer require glamstack/okta-sdk
```

> If you are contributing to this package, see [CONTRIBUTING](CONTRIBUTING.md) for instructions on configuring a local composer package with symlinks.

### Publish the configuration file

```bash
php artisan vendor:publish --tag=glamstack-okta
```

## Environment Configuration

### Connection Keys

We use the concept of **_connection keys_** that refer to a configuration array in `config/glamstack-okta.php` that allows you to configure the Base URL, API Token `.env` variable name, and log channels for each connection to the Okta API.

Each connection has a different Base URL and API token associated with it. To allow for least privilege for specific API calls, you can also configure multiple connections with the same Base URL and different API tokens that have different permission levels.

#### Base URL

Each Okta customer is provided with a subdomain for their company. This is sometimes referred to as a tenant or ${yourOktaDomain} in the API documentation. This should be configured in the `prod` connection key or using the `.env` variable (see below).

```
https://mycompany.okta.com
```

If you have access to the Okta Preview sandbox/testing/staging instance, you can also configure a Base URL and API token in the `preview` connection key.

```
https://mycompany.oktapreview.com
```

If you have a free [Okta developer account](https://developer.okta.com/signup/), you can configure the Base URL and API token in the `dev` key.

If you have the rare use case where you have additional Okta instances that you connect to beyond what is pre-configured below, you can add an additional connection keys below with the name of your choice and create new variables for the Base URL and API token using the other instances as examples.

To get started, add the following variables to your `.env` file. You can add these anywhere in the file on a new line, or add to the bottom of the file (your choice). Be sure to replace `mycompany` with your own URL.

```bash
# .env

OKTA_PROD_BASE_URL="https://mycompany.okta.com"
OKTA_PROD_API_TOKEN=""
OKTA_PREVIEW_BASE_URL="https://mycompany.oktapreview.com"
OKTA_PREVIEW_API_TOKEN=""
OKTA_DEV_BASE_URL=""
OKTA_DEV_API_TOKEN=""
```

#### API Tokens

See the [Okta documentation](https://developer.okta.com/docs/guides/create-an-api-token/main/) for creating an API token. Keep in mind that the API token uses the permissions for the user it belongs to, so it is a best practice to create a service account (bot) user for production application use cases. Any tokens that are inactive for 30 days without API calls will automatically expire.

> **Security Warning:** It is important that you don't add your API token to the `config/glamstack-okta.php` file to avoid committing to your repository (secret leak). All API tokens should be defined in the `.env` file which is included in `.gitignore` and not committed to your repository.

#### Default Global Connection

By default, the SDK will use the `prod` connection key for all API calls across your application unless you change the default connection to a different **_connection key_** defined in the `config/glamstack-okta.php` file.

You can optionally add the `OKTA_DEFAULT_CONNECTION` variable to your `.env` file and set the value to the **_connection key_** that you want to use as the default.

```bash
OKTA_DEFAULT_CONNECTION="dev"
```

To use the default connection, you do **_not_** need to provide the **_connection key_** to the `ApiClient`.

```php
// Initialize the SDK
$okta_api = new \Glamstack\Okta\ApiClient();

// Get a list of records
// https://developer.okta.com/docs/reference/api/groups/#list-groups
$groups = $okta_api->get('/groups');
```

#### Using a Specific Connection per API Call

If you want to use a specific **_connection key_** when using the `ApiClient` that is different from the `OKTA_DEFAULT_CONNECTION` `.env` variable, you can pass the **_connection key_** that has been configured in `config/glamstack-okta.php` as the first construct argument for the `ApiClient`.

```php
// Initialize the SDK
$okta_api = new \Glamstack\Okta\ApiClient('preview');

// Get a list of records
// https://developer.okta.com/docs/reference/api/groups/#list-groups
$groups = $okta_api->get('/groups');
```

> If you encounter errors, ensure that the API token has been added to you `.env` file in the `OKTA_{CONNECTION_KEY}_API_TOKEN` variable. Keep in mind that Okta API tokens automatically expire after 30 days of inactivity, so it is possible that you will have not run `dev` or `preview` API calls in awhile and will receive an unauthorized error message.

### Logging Configuration

By default, we use the `single` channel for all logs that is configured in your application's `config/logging.php` file. This sends all Okta API log messages to the `storage/logs/laravel.log` file.

You can configure the log channels for this SDK in `config/glamstack-okta.php`. You can configure the log channels for the initial authentication in `auth.log_channels`. You can also configure the log channels for each of your connections in `connections.{connection_key}.log_channels`.

```php
// config/glamstack-okta.php

   'auth' => [
        'log_channels' => ['single'],
    ],

    'connections' => [
        'prod' => [
            // ...
            'log_channels' => ['single'],
        ],
        'preview' => [
            // ...
            'log_channels' => ['single'],
        ],
        'dev' => [
            // ...
            'log_channels' => ['single'],
        ],
    ]
```

#### Custom Log Channels

If you would like to see Okta API logs in a separate log file that is easier to triage without unrelated log messages, you can create a custom log channel. For example, we recommend using the value of `glamstack-okta`, however you can choose any name you would like. You can also create a log channel for each of your Okta connections (ex. `glamstack-okta-prod` and `glamstack-okta-preview`).

Add the custom log channel to `config/logging.php`.

```php
// config/logging.php

    'channels' => [

        // Add anywhere in the `channels` array

        'glamstack-okta' => [
            'name' => 'glamstack-okta',
            'driver' => 'single',
            'level' => 'debug',
            'path' => storage_path('logs/glamstack-okta.log'),
        ],
    ],
```

Update the `channels.stack.channels` array to include the array key (ex. `glamstack-okta`) of your custom channel. Be sure to add `glamstack-okta` to the existing array values and not replace the existing values.

```php
// config/logging.php

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['single','slack', 'glamstack-okta'],
            'ignore_exceptions' => false,
        ],
    ],
```

Finally, update the `config/glamstack-okta.php` configuration.

```php
// config/glamstack-okta.php

   'auth' => [
        'log_channels' => ['glamstack-okta'],
    ],
```

You can repeat these configuration steps to customize any of your connection keys.

## Issue Tracking and Bug Reports

Please visit our [issue tracker](https://gitlab.com/gitlab-com/business-technology/engineering/access-manager/packages/composer/okta-sdk/-/issues) and create an issue or comment on an existing issue.

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) to learn more about how to contribute.
