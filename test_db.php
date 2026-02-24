    <?php
echo "<!DOCTYPE html>
<html>
<head>
    <title>Диагностика MySQL</title>
    <style>
        body { background: #0a0a0f; color: #fff; font-family: Arial; padding: 20px; }
        .success { color: #4bff91; }
        .error { color: #ff4b4b; }
        .warning { color: #ffb84b; }
        pre { background: #1a1a2a; padding: 10px; border-radius: 5px; }
        .box { background: #1a1a2a; border: 1px solid #2a2a3a; border-radius: 10px; padding: 20px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <h1>🔧 Диагностика подключения к MySQL</h1>";

// Проверяем расширение PDO MySQL
echo "<div class='box'>";
echo "<h2>1. Проверка PHP расширений</h2>";
if (extension_loaded('pdo_mysql')) {
    echo "<p class='success'>✅ PDO MySQL загружен</p>";
} else {
    echo "<p class='error'>❌ PDO MySQL НЕ загружен</p>";
}

if (extension_loaded('mysqli')) {
    echo "<p class='success'>✅ MySQLi загружен</p>";
} else {
    echo "<p class='error'>❌ MySQLi НЕ загружен</p>";
}
echo "</div>";

// Пробуем разные способы подключения
echo "<div class='box'>";
echo "<h2>2. Проверка подключения к MySQL</h2>";

$hosts = ['localhost', '127.0.0.1'];
$ports = [3306, 3307, 3308];
$users = ['root', ''];
$passwords = ['', 'root', 'mysql'];

foreach ($hosts as $host) {
    foreach ($ports as $port) {
        foreach ($users as $user) {
            foreach ($passwords as $pass) {
                try {
                    $dsn = "mysql:host=$host;port=$port";
                    $pdo = new PDO($dsn, $user, $pass);
                    echo "<p class='success'>✅ Успешно! host=$host, port=$port, user=$user, pass='$pass'</p>";
                    
                    // Получаем список баз данных
                    $stmt = $pdo->query("SHOW DATABASES");
                    $dbs = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    echo "<p>📊 Доступные базы данных:</p>";
                    echo "<pre>" . implode(", ", $dbs) . "</pre>";
                    
                    // Проверяем наличие нашей БД
                    if (in_array('unioncase', $dbs)) {
                        echo "<p class='success'>✅ База данных 'unioncase' существует!</p>";
                    } else {
                        echo "<p class='warning'>⚠️ База данных 'unioncase' не найдена</p>";
                        echo "<p>Создайте её через phpMyAdmin или импортируйте db.sql</p>";
                    }
                    
                    // Показываем правильные настройки
                    echo "<h3>📝 Скопируйте эти настройки в config.php:</h3>";
                    echo "<pre style='background: #0a0a0f;'>";
                    echo "define('DB_HOST', '$host');\n";
                    echo "define('DB_PORT', $port);\n";
                    echo "define('DB_USER', '$user');\n";
                    echo "define('DB_PASS', '$pass');\n";
                    echo "define('DB_NAME', 'unioncase');";
                    echo "</pre>";
                    
                    break 4;
                    
                } catch (Exception $e) {
                    // Пробуем дальше
                }
            }
        }
    }
}
echo "</div>";

// Инструкции
echo "<div class='box'>";
echo "<h2>3. Инструкция по настройке</h2>";

echo "<h3>Для XAMPP:</h3>";
echo "<ol>";
echo "<li>Откройте панель управления XAMPP</li>";
echo "<li>Запустите MySQL (кнопка Start)</li>";
echo "<li>Откройте http://localhost/phpmyadmin</li>";
echo "<li>Создайте базу данных 'unioncase'</li>";
echo "<li>Импортируйте файл db.sql</li>";
echo "</ol>";

echo "<h3>Для OpenServer:</h3>";
echo "<ol>";
echo "<li>Запустите OpenServer</li>";
echo "<li>Проверьте, что MySQL запущен (иконка в трее зеленая)</li>";
echo "<li>Откройте http://localhost/openserver/phpmyadmin</li>";
echo "<li>Создайте базу данных 'unioncase'</li>";
echo "<li>Импортируйте файл db.sql</li>";
echo "</ol>";

echo "<h3>Для MAMP:</h3>";
echo "<ol>";
echo "<li>Запустите MAMP</li>";
echo "<li>Нажмите 'Start Servers'</li>";
echo "<li>Откройте http://localhost:8888/phpmyadmin</li>";
echo "<li>Создайте базу данных 'unioncase'</li>";
echo "<li>Импортируйте файл db.sql</li>";
echo "</ol>";

echo "</div>";

// Проверка прав на папки
echo "<div class='box'>";
echo "<h2>4. Проверка прав на папки</h2>";

$folders = [
    'assets/images/',
    'assets/images/avatars/',
    'assets/images/cases/',
    'assets/images/items/',
    'assets/images/marketplace/'
];

foreach ($folders as $folder) {
    $fullPath = __DIR__ . '/' . $folder;
    if (!file_exists($fullPath)) {
        mkdir($fullPath, 0777, true);
        echo "<p class='warning'>📁 Создана папка: $folder</p>";
    }
    if (is_writable($fullPath)) {
        echo "<p class='success'>✅ Папка доступна для записи: $folder</p>";
    } else {
        echo "<p class='error'>❌ Папка НЕ доступна для записи: $folder</p>";
    }
}
echo "</div>";

echo "</body></html>";
?>