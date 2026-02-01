<?php
/**
 * CONFIG.PHP - Cấu hình hệ thống
 * Đọc từ file .env và định nghĩa constants
 */

// Load .env file
function loadEnv($path) {
    if (!file_exists($path)) {
        die('.env file not found at: ' . $path);
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Set as environment variable
            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
}

// Load environment variables
loadEnv(dirname(__DIR__) . '/.env');

// Helper function to get env value with default
function env($key, $default = null) {
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }
    
    // Convert string booleans
    if ($value === 'true') return true;
    if ($value === 'false') return false;
    
    return $value;
}

// Thông tin ứng dụng
define('APP_NAME', env('APP_NAME', 'GAMES STORE'));
define('APP_VERSION', env('APP_VERSION', '1.0.0'));
define('APP_URL', env('APP_URL', 'http://localhost/steamweb'));
define('BASE_URL', env('BASE_URL', 'http://localhost/steamweb'));
define('APP_DEBUG', env('APP_DEBUG', true));

// Đường dẫn thư mục
define('ROOT_PATH', dirname(dirname(__FILE__)));
define('APP_PATH', ROOT_PATH . '/app');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('VIEWS_PATH', ROOT_PATH . '/views');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('UPLOADS_PATH', PUBLIC_PATH . '/uploads');
define('ASSETS_PATH', PUBLIC_PATH . '/assets');

// Cấu hình Database (từ .env)
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_NAME', env('DB_NAME', 'steamweb'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
define('DB_PORT', env('DB_PORT', 3306));

// Cấu hình Session
define('SESSION_LIFETIME', env('SESSION_LIFETIME', 3600)); // 1 giờ

// Timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

