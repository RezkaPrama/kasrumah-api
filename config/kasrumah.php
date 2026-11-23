<?php

return [
    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        // Ganti lewat .env sesuai model Gemini yang tersedia untuk API key Anda.
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
        'timeout' => (int) env('GEMINI_TIMEOUT', 60),
    ],
];
