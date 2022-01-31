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

## Security Best Practices

### No Shared Tokens

Don't use an API token that you have already created for another purpose. You should generate a new Access Token for each use case.

This is helpful during security incidents when a key needs to be revoked on a compromised system and you don't want other systems that use the same user or service account to be affected since they use a different key that wasn't revoked.

### API Token Storage

Do not add your API token to the `config/glamstack-okta.php` file to avoid committing to your repository (secret leak).

All API tokens should be defined in the `.env` file which is included in `.gitignore` and not committed to your repository.

It is a recommended to store a copy of each API token in your preferred password manager (ex. 1Password, LastPass, etc.) and/or secrets vault (ex. HashiCorp Vault, Ansible, etc.).

### API Token Permissions

Different Okta API operations require different admin privilege levels. API tokens inherit the privilege level of the admin account that is used to create them. It is therefore good practice to create a service account to use when you create API tokens so that you can assign the token the specific privilege level needed. See [Administrators documentation](https://help.okta.com/okta_help.htm?id=ext_Security_Administrators) for admin account types and the specific privileges of each.

Credit: [Okta Documentation - Create an API Token](https://developer.okta.com/docs/guides/create-an-api-token/main/)

#### Least Privilege

If you need to use different tokens for least privilege security reasons, you can customize `config/glamstack-okta.php` to add the same Okta instance multiple times with different connection keys using any names that fit your needs (ex. `prod_scope1`, `prod_scope2`, `prod_scope3`.

You can customize the `.env` variable names as needed. The SDK uses the values from the `config/glamstack-okta.php` file and does not use any `.env` variables directly.

```php
'prod_scope1' => [
    'base_url' => env('OKTA_PROD_BASE_URL'),
    'api_token' => env('OKTA_PROD_SCOPE1_API_TOKEN'),
    'log_channels' => ['single']
],

'prod_scope2' => [
    'base_url' => env('OKTA_PROD_BASE_URL'),
    'api_token' => env('OKTA_PROD_SCOPE2_API_TOKEN'),
    'log_channels' => ['single']
],

'prod_scope3' => [
    'base_url' => env('OKTA_PROD_BASE_URL'),
    'api_token' => env('OKTA_PROD_SCOPE3_API_TOKEN'),
    'log_channels' => ['single']
],
```

You simply need to provide the instance key when invoking the SDK, and you may need to store the connection keys in your application's database for dynamically rendered pages.

```php
$okta_api = new \Glamstack\Okta\ApiClient('prod_scope1');
$groups = $gitlab_api->get('/groups')->object();
```

Alternatively, you can provide a different API key when initializing the service using the second argument. The API token from `config/glamstack-okta.php` is used if the second argument is not provided. This is helpful if your API tokens are stored in your database and are not hard coded into your `.env` file.

```php
// Get the API token from a model in your application.
// Disclaimer: This is an example and is not a feature of the SDK.
$service_account = App\Models\OktaServiceAccount::where('id', $id)->firstOrFail();
$api_token = decrypt($service_account->api_token);

// Use the SDK to connect using your access token.
$okta_api = new \Glamstack\Okta\ApiClient('prod', $api_token);
$groups = $okta_api->get('/groups')->object();
```

## API Requests

You can make an API request to any of the resource endpoints in the [Okta REST API Documentation](https://developer.okta.com/docs/reference/core-okta-api/).

#### Inline Usage

```php
// Initialize the SDK
$okta_api = new \Glamstack\Okta\ApiClient('prod');
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
$record = $okta_api->get('/groups/'.$api_group_id);
```

### GET Requests with Query String Parameters

The second argument of a `get()` method is an optional array of parameters that is parsed by the SDK and the [Laravel HTTP Client](https://laravel.com/docs/8.x/http-client#get-request-query-parameters) and rendered as a query string with the `?` and `&` added automatically.

```php
// Search for records with a specific name
// https://developer.okta.com/docs/reference/api/groups/#list-groups
// https://developer.okta.com/docs/reference/core-okta-api/#filter
$records = $okta_api->get('/groups', [
    'q' => 'Hack the Planet Engineers'
]);

// This will parse the array and render the query string
// https://mycompany.okta.com/api/v1/groups?q=Hack%20the&%20Planet%20Engineers
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

use Glamstack\Okta\ApiClient;

class OktaGroupService
{
    protected $okta_api;

    public function __construct($connection_key = 'prod')
    {
        $this->$okta_api = new \Glamstack\Okta\ApiClient($connection_key);
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

This SDK uses the GLAM Stack standards for API response formatting.

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

## Issue Tracking and Bug Reports

Please visit our [issue tracker](https://gitlab.com/gitlab-com/business-technology/engineering/access-manager/packages/composer/okta-sdk/-/issues) and create an issue or comment on an existing issue.

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) to learn more about how to contribute.
