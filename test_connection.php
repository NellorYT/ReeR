<?php
require_once __DIR__ . '/includes/functions.php';
startSession();
// Доступ только для администраторов
if (!isAdmin()) {
    http_response_code(403);
    exit('Доступ запрещён. Этот диагностический скрипт доступен только администраторам.');
}

echo "<h1>Проверка соединения с базой данных</h1>";

try {
    $db = db();
    
    if ($db->isConnected()) {
        echo "<p style='color:green'>✓ Подключение к MySQL успешно!</p>";
        
        // Проверяем таблицы
        $tables = $db->checkTables();
        
        echo "<h2>Проверка таблиц:</h2>";
        echo "<p>Существующие таблицы: " . implode(', ', $tables['existing']) . "</p>";
        
        if (empty($tables['missing'])) {
            echo "<p style='color:green'>✓ Все необходимые таблицы существуют!</p>";
        } else {
            echo "<p style='color:red'>✗ Отсутствуют таблицы: " . implode(', ', $tables['missing']) . "</p>";
            echo "<p>Импортируйте db.sql для создания таблиц</p>";
        }
        
        // Проверяем пользователя admin
        $admin = $db->fetch("SELECT * FROM users WHERE username = 'admin'");
        if ($admin) {
            echo "<p style='color:green'>✓ Пользователь admin существует</p>";
        } else {
            echo "<p style='color:red'>✗ Пользователь admin не найден</p>";
        }
        
    } else {
        echo "<p style='color:red'>✗ Не удалось подключиться к базе данных</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>Ошибка: " . $e->getMessage() . "</p>";
}

// Информация о PHP и MySQL
echo "<h2>Информация о системе:</h2>";
echo "<p>PHP версия: " . phpversion() . "</p>";
echo "<p>MySQL драйвер: " . (extension_loaded('pdo_mysql') ? '✓ Загружен' : '✗ НЕ ЗАГРУЖЕН') . "</p>";
echo "<p>PDO драйверы: " . implode(', ', PDO::getAvailableDrivers()) . "</p>";
echo "<p>Путь к сайту: " . SITE_PATH . "</p>";
echo "<p>URL сайта: " . SITE_URL . "</p>";