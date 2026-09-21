<?php

return [
    'base_url' => rtrim((string) env('SUDREG_BASE_URL', 'https://sudreg-data.gov.hr/api/javni'), '/'),
    'token_url' => (string) env('SUDREG_TOKEN_URL', 'https://sudreg-data.gov.hr/api/oauth/token'),
    'client_id' => env('SUDREG_CLIENT_ID'),
    'client_secret' => env('SUDREG_CLIENT_SECRET'),
    'timeout' => 8,
];
