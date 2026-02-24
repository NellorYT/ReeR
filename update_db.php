<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

echo "<h1>Обновление структуры базы данных</h1>";

try {
    // Переименовываем таблицу marketplaces в case_themes
    db()->execute("RENAME TABLE marketplaces TO case_themes");
    echo "<p style='color:green'>✅ Таблица переименована: marketplaces -> case_themes</p>";
    
    // Обновляем структуру
    db()->execute("ALTER TABLE cases CHANGE marketplace_id theme_id INT DEFAULT NULL");
    echo "<p style='color:green'>✅ Поле переименовано: marketplace_id -> theme_id</p>";
    
    // Обновляем внешний ключ
    db()->execute("ALTER TABLE cases DROP FOREIGN KEY cases_ibfk_1");
    db()->execute("ALTER TABLE cases ADD CONSTRAINT cases_ibfk_1 FOREIGN KEY (theme_id) REFERENCES case_themes(id) ON DELETE SET NULL");
    echo "<p style='color:green'>✅ Внешний ключ обновлен</p>";
    
    // Добавляем поле icon в case_themes если его нет
    $columns = db()->fetchAll("SHOW COLUMNS FROM case_themes");
    $hasIcon = false;
    foreach ($columns as $col) {
        if ($col['Field'] === 'icon') {
            $hasIcon = true;
            break;
        }
    }
    
    if (!$hasIcon) {
        db()->execute("ALTER TABLE case_themes ADD COLUMN icon VARCHAR(255) DEFAULT NULL AFTER name");
        echo "<p style='color:green'>✅ Добавлено поле icon</p>";
    }
    
    echo "<h2 style='color:green; margin-top: 20px;'>✅ База данных успешно обновлена!</h2>";
    echo "<p>Теперь у вас темы кейсов вместо маркетплейсов.</p>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Ошибка: " . $e->getMessage() . "</p>";
}
?>