<?php

return [

    'force_https' => (bool) env('APP_FORCE_HTTPS', env('APP_ENV') === 'production'),

    'trusted_proxies' => env('TRUSTED_PROXIES'),

];
