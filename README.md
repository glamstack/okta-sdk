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

### Custom Logging Configuration

By default, we use the `single` channel for all logs that is configured in your application's `config/logging.php` file. This sends all Okta API log messages to the `storage/logs/laravel.log` file.

If you would like to see Okta API logs in a separate log file that is easier to triage without unrelated log messages, you can create a custom log channel. For example, we recommend using the value of `glamstack-okta`, however you can choose any name you would like.

Add the custom log channel to `config/logging.php`.

```php
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
    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['single','slack', 'glamstack-okta'],
            'ignore_exceptions' => false,
        ],
    ],
```

## Issue Tracking and Bug Reports

Please visit our [issue tracker](https://gitlab.com/gitlab-com/business-technology/engineering/access-manager/packages/composer/okta-sdk/-/issues) and create an issue or comment on an existing issue.

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) to learn more about how to contribute.
