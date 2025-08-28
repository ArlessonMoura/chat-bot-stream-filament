<?php

return [
    'api_key' => env('OPENAI_API_KEY'),
    'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
    'base_url' => env('OPENAI_BASE_URL'),
    'api_version' => env('OPENAI_API_VERSION', '2024-10-21'),
    'timeout' => env('OPENAI_TIMEOUT', 300),
];