<?php

return [
    /*
    |--------------------------------------------------------------------------
    | DeepSeek API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration settings for DeepSeek AI API integrations.
    |
    */

    'api_key' => env('DEEPSEEK_API_KEY'),
    'base_url' => env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com'),
    'model' => env('DEEPSEEK_MODEL', 'deepseek-chat'),
    'max_context_messages' => (int) env('MAX_CONTEXT_MESSAGES', 10),
    'temperature' => (float) env('DEEPSEEK_TEMPERATURE', 0.7),
    'stream' => (bool) env('DEEPSEEK_STREAM', true),
    'verify_ssl' => (bool) env('DEEPSEEK_VERIFY_SSL', false),
    'max_tokens' => (int) env('DEEPSEEK_MAX_TOKENS', 800),
];
