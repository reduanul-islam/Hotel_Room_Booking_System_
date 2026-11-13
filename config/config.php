<?php
/**
 * Central configuration for the Hotel Room Booking System.
 *
 * All values can be overridden with environment variables, which is the
 * recommended way to configure this app in a production/deployment
 * environment (Docker, hosting panel, CI, etc). Sensible local-dev
 * defaults are provided as fallbacks so the app also runs out of the box
 * on a stock XAMPP/LAMP/MAMP setup.
 */

if (!function_exists('env')) {
    function env(string $key, $default = null) {
        $value = getenv($key);
        return ($value === false || $value === '') ? $default : $value;
    }
}

return [
    'db' => [
        'host' => env('DB_HOST', 'sql200.infinityfree.com'),
        'user' => env('DB_USER', 'if0_42674025'),
        'pass' => env('DB_PASS', 'TheSchism69'),
        'name' => env('DB_NAME', 'if0_42674025_epiz_12345678_hotel'),
        'port' => (int) env('DB_PORT', 3306),
    ],

    // Set to false in production to hide detailed error messages.
    'debug' => filter_var(env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN),

    // Max upload size (bytes) allowed for room type thumbnails.
    'max_upload_size' => 2 * 1024 * 1024, // 2MB

    'allowed_image_types' => ['image/jpeg', 'image/png'],
];
