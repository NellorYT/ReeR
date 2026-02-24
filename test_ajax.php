<?php
// test_ajax.php - проверка AJAX путей
echo json_encode([
    'success' => true,
    'message' => 'AJAX test successful',
    'base_path' => __DIR__,
    'server' => $_SERVER
]);
?>