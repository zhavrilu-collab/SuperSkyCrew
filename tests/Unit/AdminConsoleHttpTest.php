<?php

namespace Tests\Unit;

use App\Support\AdminConsoleHttp;
use Tests\TestCase;

class AdminConsoleHttpTest extends TestCase
{
    public function test_resolve_loopback_options_builds_curl_resolve_for_https_host(): void
    {
        $options = AdminConsoleHttp::resolveLoopbackOptions([
            'https://admin.example.com/api/v1',
        ]);

        $this->assertSame([
            CURLOPT_RESOLVE => ['admin.example.com:443:127.0.0.1'],
        ], $options);
    }
}
