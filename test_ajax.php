<?php
// test_ajax.php - проверка AJAX путей
require_once __DIR__ . '/includes/config.php';
// Доступ разрешён только в режиме отладки
if (!defined('DEBUG_MODE') || !DEBUG_MODE) {
    http_response_code(403);
    exit('Доступ запрещён.');
}
echo json_encode([
    'success' => true,
    'message' => 'AJAX test successful',
    'base_path' => __DIR__,
    'server' => $_SERVER
]);
?>