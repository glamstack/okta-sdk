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
    }

}
