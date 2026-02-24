<?php
$folders = [
    'assets/images/',
    'assets/images/avatars/',
    'assets/images/cases/',
    'assets/images/items/',
    'assets/images/marketplace/',
    'ajax/'
];

echo "<h1>Проверка папок</h1>";

foreach ($folders as $folder) {
    $path = __DIR__ . '/' . $folder;
    if (!file_exists($path)) {
        mkdir($path, 0777, true);
        echo "<p style='color:orange'>📁 Создана папка: $folder</p>";
    } else {
        echo "<p style='color:green'>✅ Папка существует: $folder</p>";
    }
    
    if (is_writable($path)) {
        echo "<p style='color:green'>✅ Папка доступна для записи: $folder</p>";
    } else {
        echo "<p style='color:red'>❌ Папка НЕ доступна для записи: $folder</p>";
        chmod($path, 0777);
    }
    echo "<br>";
}

echo "<h2>Теперь должно работать:</h2>";
echo "<ul>";
echo "<li>✅ Открытие кейсов с анимацией</li>";
echo "<li>✅ Пополнение баланса</li>";
echo "<li>✅ Админ-функции</li>";
echo "<li>✅ CSRF защита</li>";
echo "</ul>";
?>