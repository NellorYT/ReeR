<?php
require_once __DIR__ . '/includes/functions.php';
startSession();
// Доступ только для администраторов
if (!isAdmin()) {
    http_response_code(403);
    exit('Доступ запрещён. Этот диагностический скрипт доступен только администраторам.');
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>Сброс пароля администратора</title>
    <style>
        body { background: #0a0a0f; color: #fff; font-family: Arial; padding: 20px; }
        .success { color: #4bff91; }
        .error { color: #ff4b4b; }
        pre { background: #1a1a2a; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🔧 Сброс пароля администратора</h1>";

try {
    // Проверяем существование пользователя admin
    $admin = db()->fetch("SELECT * FROM users WHERE username = 'admin'");
    
    if ($admin) {
        echo "<p>📋 Найден пользователь admin:</p>";
        echo "<pre>";
        echo "ID: " . $admin['id'] . "\n";
        echo "Username: " . $admin['username'] . "\n";
        echo "Email: " . $admin['email'] . "\n";
        echo "Role: " . $admin['role'] . "\n";
        echo "Current password hash: " . $admin['password'] . "\n";
        echo "</pre>";
        
        // Создаем новый хеш пароля
        $newPassword = 'admin123';
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 10]);
        
        // Обновляем пароль
        db()->execute("UPDATE users SET password = ? WHERE id = ?", [$hashedPassword, $admin['id']]);
        
        echo "<p class='success'>✅ Пароль успешно обновлен!</p>";
        echo "<p>Новый пароль: <strong>admin123</strong></p>";
        echo "<p>Новый хеш: " . $hashedPassword . "</p>";
        
        // Проверяем, работает ли новый пароль
        $updated = db()->fetch("SELECT * FROM users WHERE id = ?", [$admin['id']]);
        if (password_verify($newPassword, $updated['password'])) {
            echo "<p class='success'>✅ Проверка пройдена! Пароль работает.</p>";
        } else {
            echo "<p class='error'>❌ Ошибка верификации пароля!</p>";
        }
        
    } else {
        echo "<p class='error'>❌ Пользователь admin не найден!</p>";
        echo "<p>Создаю нового администратора...</p>";
        
        $hashedPassword = password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 10]);
        db()->insert(
            "INSERT INTO users (username, email, password, balance, role) VALUES (?, ?, ?, ?, ?)",
            ['admin', 'admin@unioncase.ru', $hashedPassword, 9999.99, 'admin']
        );
        
        echo "<p class='success'>✅ Администратор создан!</p>";
        echo "<p>Логин: admin</p>";
        echo "<p>Пароль: admin123</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Ошибка: " . $e->getMessage() . "</p>";
}

echo "<p><a href='login.php'>➡️ Перейти на страницу входа</a></p>";
echo "</body></html>";
?>