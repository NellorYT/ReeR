<?php
// create_db.php - создание базы данных
// Запуск разрешён только из командной строки (CLI)
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Этот скрипт может быть запущен только из командной строки (CLI).');
}
$host = 'localhost';
$user = 'root';
$pass = '';

try {
    // Подключаемся без выбора базы данных
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Создаем базу данных
    $sql = "CREATE DATABASE IF NOT EXISTS unioncase CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    $pdo->exec($sql);
    
    echo "✅ База данных 'unioncase' успешно создана!\n";
    
    // Выбираем базу данных
    $pdo->exec("USE unioncase");
    
    // Читаем SQL файл
    $sqlFile = __DIR__ . '/db.sql';
    if (file_exists($sqlFile)) {
        $sql = file_get_contents($sqlFile);
        
        // Разделяем запросы
        $queries = explode(';', $sql);
        
        foreach ($queries as $query) {
            $query = trim($query);
            if (!empty($query)) {
                $pdo->exec($query);
            }
        }
        
        echo "✅ Таблицы успешно созданы и заполнены!\n";
        echo "📊 Созданы таблицы: users, marketplaces, cases, items, case_items, case_opens, user_inventory, balance_transactions\n";
        echo "👤 Пользователь admin создан (пароль: admin123)\n";
        echo "📦 Добавлены тестовые кейсы и предметы\n";
        
    } else {
        echo "❌ Файл db.sql не найден!\n";
        echo "📁 Ожидаемый путь: " . $sqlFile . "\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
    echo "\n💡 Возможные решения:\n";
    echo "1. Проверьте, запущен ли MySQL (XAMPP должен быть запущен)\n";
    echo "2. Проверьте пароль MySQL (по умолчанию пустой)\n";
    echo "3. Попробуйте создать базу вручную через phpMyAdmin\n";
}
?>