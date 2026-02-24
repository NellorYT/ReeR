<?php
// UnionCase - Конфигурация

// Настройки базы данных для локального сервера
define('DB_HOST', 'localhost');
define('DB_PORT', 3306);
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'unioncase');
define('DB_CHARSET', 'utf8mb4');

// Настройки сайта - АВТОМАТИЧЕСКОЕ ОПРЕДЕЛЕНИЕ URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('SITE_URL', $protocol . '://' . $host);  // Без /UnionCase в конце!
define('SITE_NAME', 'UnionCase');
define('SITE_PATH', dirname(__DIR__));  // Корневая папка сайта

// Настройки сессии
define('SESSION_NAME', 'unioncase_session');
define('SESSION_LIFETIME', 86400 * 7); // 7 дней

// Настройки загрузки файлов
define('UPLOAD_DIR', SITE_PATH . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR);
define('UPLOAD_URL', SITE_URL . '/assets/images/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Настройки безопасности
define('BCRYPT_COST', 10);

// Часовой пояс
date_default_timezone_set('Europe/Moscow');

// Режим отладки
define('DEBUG_MODE', true);

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
