# Okta SDK

[[_TOC_]]

## Overview

The Okta SDK is an open source [Composer](https://getcomposer.org/) package created by [GitLab IT Engineering](https://about.gitlab.com/handbook/business-technology/engineering/) for use in internal Laravel applications for connecting to Okta for provisioning and deprovisioning of users, groups, applications, and other related functionality.

> **Disclaimer:** This is not an official package maintained by the GitLab or Okta product and development teams. This is an internal tool that we use in the GitLab IT department that we have open sourced as part of our company values.
>
> Please use at your own risk and create merge requests for any bugs that you encounter.
>
> We do not maintain a roadmap of community feature requests, however we invite you to contribute and we will gladly review your merge requests.

### v2 to v3 Upgrade Guide

There are several breaking changes with v3.0 in November 2022. See the [v3.0 changelog](changelog/3.0.md) to learn more.

#### What's Changed

- The `glamstack/okta-sdk` has been abandoned and has been renamed to `gitlab-it/okta-sdk`.
- The `config/glamstack-gitlab.php` was renamed to `config/gitlab-sdk.php`. No array changes were made.
- The namespace changed from `Glamstack\Okta` to `GitlabIt\Okta`.
- Changed from a modified version of [Calendar Versioning (CalVer)](https://calver.org/) to using [Semantic Versioning (SemVer)](https://semver.org/).
- License changed from `Apache 2.0` to `MIT`

#### Migration Steps

1. Remove `glamstack/okta-sdk` from `composer.json` and add `"gitlab-it/okta-sdk": "^3.0"`, then run `composer update`.
2. Navigate to your `config` directory and rename `glamstack-okta.php` to `okta-sdk.php`.
3. Perform a find and replace across your code base from `Glamstack\Okta` to `GitlabIt\Okta`.
4. Perform a find and replace for `config('glamstack-okta.` to `config('okta-sdk.`

### Maintainers

| Name | GitLab Handle |
|------|---------------|
| [Dillon Wheeler](https://about.gitlab.com/company/team/#dillonwheeler) | [@dillonwheeler](https://gitlab.com/dillonwheeler) |
| [Jeff Martin](https://about.gitlab.com/company/team/#jeffersonmartin) | [@jeffersonmartin](https://gitlab.com/jeffersonmartin) |

### How It Works

The URL of your Okta instance (ex. `https://mycompany.okta.com`) and API Token is specified in `config/okta-sdk.php` using variables inherited from your `.env` file.

If your connection configuration is stored in your database and needs to be provided dynamically, the `config/okta-sdk.php` configuration file can be overridden by passing in an array to the `connection_config` parameter during initialization of the SDK. See [Dynamic Variable Connection per API Call](#dynamic-variable-connection-per-api-call) to learn more.

Instead of providing a method for every endpoint in the API documentation, we have taken a simpler approach by providing a universal `ApiClient` that can perform `GET`, `POST`, `PUT`, and `DELETE` requests to any endpoint that you find in the [Okta API documentation](https://developer.okta.com/docs/reference/core-okta-api/) and handles the API response, error handling, and pagination for you.

This builds upon the simplicity of the [Laravel HTTP Client](https://laravel.com/docs/8.x/http-client) that is powered by the [Guzzle HTTP client](http://docs.guzzlephp.org/en/stable/) to provide "last lines of code parsing" for Okta API responses to improve the developer experience.

```php
// Initialize the SDK
$okta_api = new \GitlabIt\Okta\ApiClient('prod');

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

> Still Using `glamstack/okta-sdk` (v2.x)? See the [v3.0 Upgrade Guide](#v2-to-v3-upgrade-guide) for instructions to upgrade to `gitlab-it/okta-sdk:^3.0`.

```bash
composer require gitlab-it/okta-sdk:^3.0
```

If you are contributing to this package, see [CONTRIBUTING.md](CONTRIBUTING.md) for instructions on configuring a local composer package with symlinks.

### Publish the configuration file

This command should only be run the first time that you use this package. You will override any custom configuration if you run this command again if you do not back up your old config file.

```bash
php artisan vendor:publish --tag=okta-sdk
```

## Environment Configuration

### Environment Variables

To get started, add the following variables to your `.env` file. You can add these anywhere in the file on a new line, or add to the bottom of the file (your choice).

```bash
# .env

OKTA_DEFAULT_CONNECTION="dev"
OKTA_DEV_BASE_URL=""
OKTA_DEV_API_TOKEN=""
OKTA_PREVIEW_BASE_URL=""
OKTA_PREVIEW_API_TOKEN=""
OKTA_PROD_BASE_URL=""
OKTA_PROD_API_TOKEN=""
```

### Connection Keys

We use the concept of **_connection keys_** (a.k.a. instance keys) that refer to a configuration array in `config/okta-sdk.php` that allows you to configure the Base URL, API Token `.env` variable name, and log channels for each connection to the Okta API and provide a unique name for that connection.

Each connection has a different Base URL and API token associated with it.

We have pre-configured the `dev`, `preview`, and `prod` keys to help you get started quickly without any modifications needed to the `config/okta-sdk.php` file.

```php
# config/okta-sdk.php

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
]
```

If you have the rare use case where you have additional Okta instances or [least privilege]() service accounts beyond what is pre-configured, you can add additional connection keys in `config/okta-sdk.php` with the name of your choice and create new variables for the Base URL and API token using the other connections as examples.

#### Base URL

Each Okta customer is provided with a subdomain for their company. This is sometimes referred to as a tenant or `${yourOktaDomain}` in the API documentation. This should be configured in the `prod` connection key or using the `.env` variable (see below).

```
https://mycompany.okta.com
```

If you have access to the Okta Preview sandbox/testing/staging instance, you can also configure a Base URL and API token in the `preview` connection key.

```
https://mycompany.oktapreview.com
```

If you have a free [Okta developer account](https://developer.okta.com/signup/), you can configure the Base URL and API token in the `dev` key.

```
https://dev-12345678.okta.com
```

#### API Tokens

See the [Okta documentation](https://developer.okta.com/docs/guides/create-an-api-token/main/) for creating an API token. Keep in mind that the API token uses the permissions for the user it belongs to, so it is a best practice to create a service account (bot) user for production application use cases. Any tokens that are inactive for 30 days without API calls will automatically expire.

> **Security Warning:** It is important that you do not add your API token to the `config/okta-sdk.php` file to avoid committing to your repository (secret leak). All API tokens should be defined in the `.env` file which is included in `.gitignore` and not committed to your repository. For advanced use cases, you can store your variables in CI/CD variables or a secrets vault (no documentation provided here).

> **Internal Developer Note:** The API key is automatically prefixed with `SSWS ` when initializing the SDK.

#### Default Global Connection

By default, the SDK will use the `prod` connection key for all API calls across your application unless you override the default connection to a different **_connection key_** using the `OKTA_DEFAULT_CONNECTION` variable.

If you're just getting started, you should set this to `dev`. You can change this to `preview` or `prod` later when deploying your application to staging or production environments.

```bash
OKTA_DEFAULT_CONNECTION="dev"
```

### Default Connection Key Variables

You can use any combination of `prod`, `preview`, or `dev` connection keys. Be sure to replace `mycompany` with your own URL.

Simply leave any unused connections blank or remove them from your `.env` file. You can always add them later.

```bash
# .env

OKTA_DEFAULT_CONNECTION="dev"
OKTA_DEV_BASE_URL="https://dev-12345678.okta.com"
OKTA_DEV_API_TOKEN="YourDevT0k3nG0esH3r3"
OKTA_PREVIEW_BASE_URL="https://mycompany.oktapreview.com"
OKTA_PREVIEW_API_TOKEN="YourPreviewT0k3nG0esH3r3"
OKTA_PROD_BASE_URL="https://mycompany.okta.com"
OKTA_PROD_API_TOKEN="YourProdT0k3nG0esH3r3"
```

## Initializing the API Connection

To use the default connection, you do **_not_** need to provide the **_connection key_** to the `ApiClient`. This allows you to code your application without hard coding a connection key and simply update the `.env` variable.

```php
// Initialize the SDK
$okta_api = new \GitlabIt\Okta\ApiClient();

// Get a list of records
// https://developer.okta.com/docs/reference/api/groups/#list-groups
$groups = $okta_api->get('/groups');
```

#### Using a Specific Connection per API Call

If you want to use a specific **_connection key_** when using the `ApiClient` that is different from the `OKTA_DEFAULT_CONNECTION` `.env` variable, you can pass any **_connection key_** that has been configured in `config/okta-sdk.php` as the first construct argument for the `ApiClient`.

```php
// Initialize the SDK
$okta_api = new \GitlabIt\Okta\ApiClient('preview');

// Get a list of records
// https://developer.okta.com/docs/reference/api/groups/#list-groups
$groups = $okta_api->get('/groups');
```

> If you encounter errors, ensure that the API token has been added to your `.env` file in the `OKTA_{CONNECTION_KEY}_API_TOKEN` variable. Keep in mind that Okta API tokens automatically expire after 30 days of inactivity, so it is possible that you will have not run `dev` or `preview` API calls in awhile and will receive an unauthorized error message.

### Dynamic Variable Connection per API Call

If not utilizing a connection key in the `config/okta-sdk.php` configuration file, you can pass an array as the second argument with a custom connection configuration.

```php
// Initialize the SDK
$okta_api = new \GitlabIt\Okta\ApiClient(null, [
    'base_url' => 'https://mycompany.okta.com',
    'api_token' => '00fJq-ABCDEFGhijklmn0pQrsTu-Vw-xyZ12345678'
    'log_channels' => ['single', 'okta-sdk']
]);
```

> **Security Warning:** Do not commit a hard coded API token into your code base. This should only be used when using dynamic variables that are stored in your database.

Here is an example of how you can use your own Eloquent model to store your Okta instances and provide them to the SDK. You can choose whether to provide dynamic log channels as part of your application logic or hard code the channels that you have configured in your application that uses the SDK.

```php
// The $okta_instance_id is provided dynamically in the controller or service request

// Get Okta Instance
// Disclaimer: This is an example and is not a feature of the SDK.
$okta_instance = \App\Models\OktaInstance::query()
    ->where('id', $okta_instance_id)
    ->firstOrFail();

// Initialize the SDK
$okta_api = new \GitlabIt\Okta\ApiClient(null, [
    'base_url' => $okta_instance->api_base_url,
    'api_token' => decrypt($okta_instance->api_token),
    'log_channels' => ['single', 'okta-sdk']
]);
```

### Logging Configuration

By default, we use the `single` channel for all logs that is configured in your application's `config/logging.php` file. This sends all Okta API log messages to the `storage/logs/laravel.log` file.

You can configure the log channels for this SDK in `config/okta-sdk.php`. You can configure the log channels for the initial authentication in `auth.log_channels`. You can also configure the log channels for each of your connections in `connections.{connection_key}.log_channels`.

```php
// config/okta-sdk.php

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

If you would like to see Okta API logs in a separate log file that is easier to triage without unrelated log messages, you can create a custom log channel. For example, we recommend using the value of `okta-sdk`, however you can choose any name you would like. You can also create a log channel for each of your Okta connections (ex. `okta-sdk-prod` and `okta-sdk-preview`).

Add the custom log channel to `config/logging.php`.

```php
// config/logging.php

    'channels' => [

        // Add anywhere in the `channels` array

        'okta-sdk' => [
            'name' => 'okta-sdk',
            'driver' => 'single',
            'level' => 'debug',
            'path' => storage_path('logs/okta-sdk.log'),
        ],
    ],
```

Update the `channels.stack.channels` array to include the array key (ex. `okta-sdk`) of your custom channel. Be sure to add `okta-sdk` to the existing array values and not replace the existing values.

```php
// config/logging.php

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['single','slack', 'okta-sdk'],
            'ignore_exceptions' => false,
        ],
    ],
```

Finally, update the `config/okta-sdk.php` configuration.

```php
// config/okta-sdk.php

   'auth' => [
        'log_channels' => ['okta-sdk'],
    ],
```

You can repeat these configuration steps to customize any of your connection keys.

## Security Best Practices

### No Shared Tokens

Do not use an API token that you have already created for another purpose. You should generate a new API Token for each use case.

This is helpful during security incidents when a key needs to be revoked on a compromised system and you do not want other systems that use the same user or service account to be affected since they use a different key that wasn't revoked.

### API Token Storage

Do not add your API token to the `config/okta-sdk.php` file to avoid committing to your repository (secret leak).

All API tokens should be defined in the `.env` file which is included in `.gitignore` and not committed to your repository.

It is recommended to store a copy of each API token in your preferred password manager (ex. 1Password, LastPass, etc.) and/or secrets vault (ex. HashiCorp Vault, Ansible, etc.).

### API Token Permissions

Different Okta API operations require different admin privilege levels. API tokens inherit the privilege level of the admin account that is used to create them. It is therefore good practice to create a service account to use when you create API tokens so that you can assign the token the specific privilege level needed. See [Administrators documentation](https://help.okta.com/okta_help.htm?id=ext_Security_Administrators) for admin account types and the specific privileges of each.

Credit: [Okta Documentation - Create an API Token](https://developer.okta.com/docs/guides/create-an-api-token/main/)

#### Least Privilege

If you need to use different API keys for least privilege security reasons, you can customize `config/okta-sdk.php` to add the same Okta Base URL multiple times with different connection keys using any names that fit your needs (ex. `prod_scope1`, `prod_scope2`, `prod_scope3`.

You can customize the `.env` variable names as needed. The SDK uses the values from the `config/okta-sdk.php` file and does not use any `.env` variables directly.

```php
'prod_read_only' => [
    'base_url' => env('OKTA_PROD_BASE_URL'),
    'api_token' => env('OKTA_PROD_READ_ONLY_API_TOKEN'),
    'log_channels' => ['single']
],

'prod_super_admin' => [
    'base_url' => env('OKTA_PROD_BASE_URL'),
    'api_token' => env('OKTA_PROD_SUPER_ADMIN_API_TOKEN'),
    'log_channels' => ['single']
],

'prod_group_admin' => [
    'base_url' => env('OKTA_PROD_BASE_URL'),
    'api_token' => env('OKTA_PROD_GROUP_ADMIN_API_TOKEN'),
    'log_channels' => ['single']
],
```

You simply need to provide the connection key when invoking the SDK.

```php
$okta_api = new \GitlabIt\Okta\ApiClient('prod_read_only');
$groups = $okta_api->get('/groups')->object();
```

If you need more flexibility, use a [Dynamic Variable Connection per API Call](#dynamic-variable-connection-per-api-call).

## API Requests

You can make an API request to any of the resource endpoints in the [Okta REST API Documentation](https://developer.okta.com/docs/reference/core-okta-api/).

#### Inline Usage

```php
// Initialize the SDK
$okta_api = new \GitlabIt\Okta\ApiClient('prod');
```

### GET Requests

The endpoint starts with a leading `/` after `/api/v1`. The Okta API documentation provides the full endpoint, so remove the `/api/v1` when copy and pasting the endpoint.

See the [List all groups](https://developer.okta.com/docs/reference/api/groups/#list-groups) API documentation as reference for the examples below.

With the SDK, you use the `get()` method with the endpoint `/groups` as the first argument.

```php
$okta_api->get('/groups');
```

You can also use variables or database models to get data for constructing your endpoints.

```php
$endpoint = '/groups';
$records = $okta_api->get($endpoint);
```

Here are some more examples of using endpoints.

```php
// Get a list of records
// https://developer.okta.com/docs/reference/api/groups/#list-groups
$records = $okta_api->get('/groups');

// Get a specific record
// https://developer.okta.com/docs/reference/api/groups/#get-group
$record = $okta_api->get('/groups/0oa1ab2c3D4E5F6G7h8i');

// Get a specific record using a variable
// This assumes that you have a database column named `api_group_id` that
// contains the string with the Okta ID `0oa1ab2c3D4E5F6G7h8i`.
$okta_group = App\Models\OktaGroup::where('id', $id)->firstOrFail();
$api_group_id = $okta_group->api_group_id;
$record = $okta_api->get('/groups/' . $api_group_id);
```

### GET Requests with Query String Parameters

The second argument of a `get()` method is an optional array of parameters that is parsed by the SDK and the [Laravel HTTP Client](https://laravel.com/docs/8.x/http-client#get-request-query-parameters) and rendered as a query string with the `?` and `&` added automatically.

```php
// Search for records with a specific name
// https://developer.okta.com/docs/reference/api/groups/#list-groups-with-search
$records = $okta_api->get('/groups', [
    'search' => 'profile.name eq "Hack the Planet Engineers"'
]);

// This will parse the array and render the query string
// https://mycompany.okta.com/api/v1/groups?search=profile.name+eq+%22Hack%20the&%20Planet%20Engineers%22
```

```php
// List all deprovisioned users
// https://developer.okta.com/docs/reference/api/users/#list-users-with-search
$records = $okta_api->get('/users', [
    'search' => 'status eq "DEPROVISIONED"'
]);

// This will parse the array and render the query string
// https://mycompany.okta.com/api/v1/groups?search=status+eq+%22DEPROVISIONED%22
```

```php
// Get all users for a specific department
// https://developer.okta.com/docs/reference/api/users/#list-users-with-search
$records = $okta_api->get('/users', [
    'search' => 'profile.department eq "Engineering"'
]);

// This will parse the array and render the query string
// https://mycompany.okta.com/api/v1/groups?search=profile.department+eq+%22Engineering%22
```

### POST Requests

The `post()` method works almost identically to a `get()` request with an array of parameters, however the parameters are passed as form data using the `application/json` content type rather than in the URL as a query string. This is industry standard and not specific to the SDK.

You can learn more about request data in the [Laravel HTTP Client documentation](https://laravel.com/docs/8.x/http-client#request-data).

```php
// Create a group
// https://developer.okta.com/docs/reference/api/groups/#add-group
$record = $okta_api->post('/groups', [
    'name' => 'Hack the Planet Engineers',
    'description' => 'This group contains engineers that have proven they are elite enough to hack the Gibson.'
]);
```

### PUT Requests

The `put()` method is used for updating an existing record (similar to `PATCH` requests). You need to ensure that the ID of the record that you want to update is provided in the first argument (URI).

In most applications, this will be a variable that you get from your database or another location and won't be hard-coded.

```php
// Update a group
// https://developer.okta.com/docs/reference/api/groups/#update-group
$group_id = '0oa1ab2c3D4E5F6G7h8i';
$record = $okta_api->put('/groups/' . $group_id, [
    'description' => 'This group contains engineers that have liberated the garbage files.'
]);
```

### DELETE Requests

The `delete()` method is used for methods that will destroy the resource based on the ID that you provide.

Keep in mind that `delete()` methods will return different status codes depending on the vendor (ex. 200, 201, 202, 204, etc). Okta's API will return a `204` status code for successfully deleted resources.

```php
// Delete a group
// https://developer.okta.com/docs/reference/api/groups/#remove-group
$group_id = '0oa1ab2c3D4E5F6G7h8i';
$record = $okta_api->delete('/groups/' . $group_id);
```

### Class Methods

The examples above show basic inline usage that is suitable for most use cases. If you prefer to use classes and constructors, the example below will provide a helpful example.

```php
<?php

use GitlabIt\Okta\ApiClient;

class OktaGroupService
{
    protected $okta_api;

    public function __construct($connection_key = 'prod')
    {
        $this->$okta_api = new \GitlabIt\Okta\ApiClient($connection_key);
    }

    public function listGroups($query = [])
    {
        $groups = $this->$okta_api->get('/groups', $query);

        return $groups->object;
    }

    public function getGroup($id, $query = [])
    {
        $group = $this->$okta_api->get('/groups/'.$id, $query);

        return $group->object;
    }

    public function storeGroup($request_data)
    {
        $group = $this->$okta_api->post('/groups', $request_data);

        // To return an object with the newly created group
        return $group->object;

        // To return the ID of the newly created group
        // return $group->object->id;

        // To return the status code of the form request
        // return $group->status->code;

        // To return a bool with the status of the form request
        // return $group->status->successful;

        // To return the entire API response with the object, json, headers, and status
        // return $group;
    }

    public function updateGroup($id, $request_data)
    {
        $group = $this->$okta_api->put('/groups/'.$id, $request_data);

        // To return an object with the updated group
        return $group->object;

        // To return a bool with the status of the form request
        // return $group->status->successful;
    }

    public function deleteGroup($id)
    {
        $group = $this->$okta_api->delete('/groups/'.$id);

        return $group->status->successful;
    }
}
```

## API Responses

This SDK uses the GitLab IT standards for API response formatting.

```php
// API Request
$group = $okta_api->get('/groups/0oa1ab2c3D4E5F6G7h8i');

// API Response
$group->headers; // array
$group->json; // json
$group->object; // object
$group->status; // object
$group->status->code; // int (ex. 200)
$group->status->ok; // bool
$group->status->successful; // bool
$group->status->failed; // bool
$group->status->serverError; // bool
$group->status->clientError; // bool
```

#### API Response Headers

> The headers are returned as an array instead of an object since the keys use hyphens that conflict with the syntax of accessing keys and values easily.

```php
$group = $okta_api->get('/groups/0oa1ab2c3D4E5F6G7h8i');
$group->headers;
```

```php
[
    "Date" => "Sun, 30 Jan 2022 01:11:44 GMT",
    "Content-Type" => "application/json",
    "Transfer-Encoding" => "chunked",
    "Connection" => "keep-alive",
    "Server" => "nginx",
    "Public-Key-Pins-Report-Only" => "pin-sha256="REDACTED="; pin-sha256="REDACTED="; pin-sha256="REDACTED="; pin-sha256="REDACTED="; max-age=60; report-uri="https://okta.report-uri.com/r/default/hpkp/reportOnly"",
    "Vary" => "Accept-Encoding",
    "x-okta-request-id" => "A1b2C3D4e5@f6G7H8I9j0k1L2M3",
    "x-xss-protection" => "0",
    "p3p" => "CP="HONK"",
    "x-rate-limit-limit" => "1000",
    "x-rate-limit-remaining" => "998",
    "x-rate-limit-reset" => "1643505155",
    "cache-control" => "no-cache, no-store",
    "pragma" => "no-cache",
    "expires" => "0",
    "content-security-policy" => "default-src 'self' mycompany.okta.com *.oktacdn.com; connect-src 'self' mycompany.okta.com mycompany-admin.okta.com *.oktacdn.com *.mixpanel.com *.mapbox.com app.pendo.io data.pendo.io pendo-static-5634101834153984.storage.googleapis.com mycompany.kerberos.okta.com https://oinmanager.okta.com data:; script-src 'unsafe-inline' 'unsafe-eval' 'self' mycompany.okta.com *.oktacdn.com; style-src 'unsafe-inline' 'self' mycompany.okta.com *.oktacdn.com app.pendo.io cdn.pendo.io pendo-static-5634101834153984.storage.googleapis.com; frame-src 'self' mycompany.okta.com mycompany-admin.okta.com login.okta.com; img-src 'self' mycompany.okta.com *.oktacdn.com *.tiles.mapbox.com *.mapbox.com app.pendo.io data.pendo.io cdn.pendo.io pendo-static-5634101834153984.storage.googleapis.com data: blob:; font-src 'self' mycompany.okta.com data: *.oktacdn.com fonts.gstatic.com",
    "expect-ct" => "report-uri="https://oktaexpectct.report-uri.com/r/t/ct/reportOnly", max-age=0",
    "x-content-type-options" => "nosniff",
    "Strict-Transport-Security" => "max-age=315360000; includeSubDomains",
    "set-cookie" => "sid=""; Expires=Thu, 01-Jan-1970 00:00:10 GMT; Path=/ autolaunch_triggered=""; Expires=Thu, 01-Jan-1970 00:00:10 GMT; Path=/ JSESSIONID=E07ED763D2ADBB01B387772B9FB46EBF; Path=/; Secure; HttpOnly"
]
```

#### API Response Specific Header

```php
$content_type = $group->headers['Content-Type'];
```

```bash
application/json
```

#### API Response JSON

```php
$group = $okta_api->get('/groups/0oa1ab2c3D4E5F6G7h8i');
$group->json;
```

```json
"{"id":0oa1ab2c3D4E5F6G7h8i,"name":"Hack the Planet Engineers","state":"ACTIVE"}"
```

#### API Response Object

```php
$group = $okta_api->get('/groups/0oa1ab2c3D4E5F6G7h8i');
$group->object;
```

```php
{
  +"id": "0oa1ab2c3D4E5F6G7h8i"
  +"description": "This group contains engineers that have proven they are elite enough to hack the Gibson."
  +"name": "Hack the Planet Engineers"
  +"state": "ACTIVE"
}
```

#### Accessing single record values

You can access these variables using object notation. This is the most common use case for handling API responses.

```php
$group = $okta_api->get('/groups/0oa1ab2c3D4E5F6G7h8i')->object;

dd($group->name);
```

```
Hack the Planet Engineers
```

#### Looping through records

If you have an array of multiple objects, you can loop through the records.

```php
$groups = $okta_api->get('/groups')->object;

foreach($groups as $group) {

    dd($group->name);
}
```

```
Hack the Planet Engineers
```

#### Caching responses

The SDK does not use caching to avoid any constraints with you being able to control which endpoints you cache.

You can wrap an endpoint in a cache facade when making an API call. You can learn more in the [Laravel Cache](https://laravel.com/docs/9.x/cache) documentation.

```php
use Illuminate\Support\Facades\Cache;

$okta_api = new \GitlabIt\Okta\ApiClient($connection_key);

$groups = Cache::remember('okta_groups', now()->addHours(12), function () use ($okta_api) {
    return $okta_api->get('/groups')->object;
});

foreach($groups as $group) {
    dd($group->name);
}
```

When getting a specific ID or passing additional arguments, be sure to pass variables into `use($var1, $var2)`.

```php
$okta_api = new \GitlabIt\Okta\ApiClient($connection_key);
$group_id = '0oa1ab2c3D4E5F6G7h8i';

$groups = Cache::remember('okta_group_' . $group_id, now()->addHours(12), function () use ($okta_api, $group_id) {
    return $okta_api->get('/groups/' . $group_id)->object;
});
```

#### API Response Status

See the [Laravel HTTP Client documentation](https://laravel.com/docs/8.x/http-client#error-handling) to learn more about the different status booleans.

```php
$group = $okta_api->get('/groups/0oa1ab2c3D4E5F6G7h8i');
$group->status;
```

```php
{
  +"code": 200
  +"ok": true
  +"successful": true
  +"failed": false
  +"serverError": false
  +"clientError": false
}
```

#### API Response Status Code

```php
$group = $okta_api->get('/groups/0oa1ab2c3D4E5F6G7h8i');
$group->status->code;
```

```bash
200
```

## Error Handling

The HTTP status code for the API response is included in each log entry in the message and in the JSON `status_code`. Any internal SDK errors also include an equivalent status code depending on the type of error. The `message` includes the SDK friendly message.

If a `5xx` error is returned from the API, the `ApiClient` `handleException` method will return a response.

See the [Log Outputs](#log-outputs) below for how the SDK handles errors and logging.

See the [Okta API error codes documentation](https://developer.okta.com/docs/reference/error-codes/) to learn more about the codes that can be returned. More information on each resource endpoint can be found on the respective [API documentation page](https://developer.okta.com/docs/reference/core-okta-api/).

## Log Outputs

> The output of error messages is shown in the `README` to allow search engines to index these messages for developer debugging support. Any 5xx error messages will be returned as as `Symfony\Component\HttpKernel\Exception\HttpException` or configuration errors, including any errors in the `__construct()` method.

### Connection Configuration and Authentication

When the `ApiClient` class is invoked for the first time, an API connection test is performed to the `/org` endpoint of the Okta connection. This endpoint requires authentication, so this validates whether the API Token is valid.

```php
$okta_api = new \GitlabIt\Okta\ApiClient('prod');
$okta_api->get('/groups');
```

#### Valid API Token

```json
[2022-01-31 23:38:56] local.INFO: GET 200 https://gitlab.okta.com/api/v1/org {"api_endpoint":"https://gitlab.okta.com/api/v1/org","api_method":"GET","class":"GitlabIt\\Okta\\ApiClient","connection_key":"prod","message":"GET 200 https://gitlab.okta.com/api/v1/org","event_type":"okta-api-response-info","okta_request_id":"YfhzENHYyWivKath4UvZhAAAAt8","rate_limit_remaining":"998","status_code":200}

[2022-01-31 23:38:56] local.INFO: GET 200 https://gitlab.okta.com/api/v1/groups {"api_endpoint":"https://gitlab.okta.com/api/v1/groups","api_method":"GET","class":"GitlabIt\\Okta\\ApiClient","connection_key":"prod","message":"GET 200 https://gitlab.okta.com/api/v1/groups","event_type":"okta-api-response-info","okta_request_id":"YfhzEC100RhpyNJdV3sEiAAABmQ","rate_limit_remaining":"499","status_code":200}
```

#### Missing API Token

```json
[2022-01-31 23:40:26] local.CRITICAL: The API token for this Okta connection key is not defined in your `.env` file. The variable name for the API token can be found in the connection configuration in `config/okta-sdk.php`. Without this API token, you will not be able to performed authenticated API calls. {"event_type":"okta-api-config-missing-error","class":"GitlabIt\\Okta\\ApiClient","status_code":"501","message":"The API token for this Okta connection key is not defined in your `.env` file. The variable name for the API token can be found in the connection configuration in `config/okta-sdk.php`. Without this API token, you will not be able to performed authenticated API calls.","connection_key":"prod"}
```

#### Invalid API Token

```json
[2022-01-31 23:41:01] local.NOTICE: GET 401 https://gitlab.okta.com/api/v1/org {"api_endpoint":"https://gitlab.okta.com/api/v1/org","api_method":"GET","class":"GitlabIt\\Okta\\ApiClient","connection_key":"prod","event_type":"okta-api-response-client-error","message":"GET 401 https://gitlab.okta.com/api/v1/org","okta_request_id":"Yfhzjforta34Ho5ON3SqeQAADlY","okta_error_causes":[],"okta_error_code":"E0000011","okta_error_id":"oaepVpdl1ZQQO-U7Ki-e_-wHQ","okta_error_link":"E0000011","okta_error_summary":"Invalid token provided","rate_limit_remaining":null,"status_code":401}
```

#### ApiClient Construct API Token

```php
$okta_api = new \GitlabIt\Okta\ApiClient('prod', '00fJq-A1b2C3d4E5f6G7h8I9J0-Kl-mNoPqRsTuVwx');
$okta_api->get('/groups');
```

```json
[2022-01-31 23:42:04] local.NOTICE: The Okta API token for these API calls is using an API token that was provided in the ApiClient construct method. The API token that might be configured in the `.env` file is not being used. {"event_type":"okta-api-config-override-notice","class":"GitlabIt\\Okta\\ApiClient","status_code":"203","message":"The Okta API token for these API calls is using an API token that was provided in the ApiClient construct method. The API token that might be configured in the `.env` file is not being used.","okta_connection":"prod"}

[2022-01-31 23:42:04] local.INFO: GET 200 https://gitlab.okta.com/api/v1/org {"api_endpoint":"https://gitlab.okta.com/api/v1/org","api_method":"GET","class":"GitlabIt\\Okta\\ApiClient","connection_key":"prod","message":"GET 200 https://gitlab.okta.com/api/v1/org","event_type":"okta-api-response-info","okta_request_id":"YfhzzDq5PIe70D1-C8HRHwAACdg","rate_limit_remaining":"999","status_code":200}

[2022-01-31 23:42:05] local.INFO: GET 200 https://gitlab.okta.com/api/v1/groups {"api_endpoint":"https://gitlab.okta.com/api/v1/groups","api_method":"GET","class":"GitlabIt\\Okta\\ApiClient","connection_key":"prod","message":"GET 200 https://gitlab.okta.com/api/v1/groups","event_type":"okta-api-response-info","okta_request_id":"YfhzzK6LrJwm1XbvpcPnGwAAA6g","rate_limit_remaining":"499","status_code":200}
```

#### Missing Connection Configuration

```json
[2022-01-31 23:43:03] local.CRITICAL: The Okta connection key is not defined in `config/okta-sdk.php` connections array. Without this array config, there is no URL or API token to connect with. {"event_type":"okta-api-config-missing-error","class":"GitlabIt\\Okta\\ApiClient","status_code":"501","message":"The Okta connection key is not defined in `config/okta-sdk.php` connections array. Without this array config, there is no URL or API token to connect with.","connection_key":"test"}
```

#### Missing Base URL

```json
[2022-01-31 23:44:04] local.CRITICAL: The Base URL for this Okta connection key is not defined in `config/okta-sdk.php` or `.env` file. Without this configuration (ex. `https://mycompany.okta.com`), there is no URL to perform API calls with. {"event_type":"okta-api-config-missing-error","class":"GitlabIt\\Okta\\ApiClient","status_code":"501","message":"The Base URL for this Okta connection key is not defined in `config/okta-sdk.php` or `.env` file. Without this configuration (ex. `https://mycompany.okta.com`), there is no URL to perform API calls with.","connection_key":"test"}
```

## Issue Tracking and Bug Reports

Please visit our [issue tracker](https://gitlab.com/gitlab-it/okta-sdk/okta-sdk/-/issues) and create an issue or comment on an existing issue.

> **Disclaimer:** This is not an official package maintained by the GitLab or Okta product and development teams. This is an internal tool that we use in the GitLab IT department that we have open sourced as part of our company values.
>
> Please use at your own risk and create merge requests for any bugs that you encounter.
>
> We do not maintain a roadmap of community feature requests, however we invite you to contribute and we will gladly review your merge requests.

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) to learn more about how to contribute.
