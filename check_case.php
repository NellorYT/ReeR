<?php
require_once '/includes/config.php';
require_once '/includes/db.php';

echo "<h1>Проверка кейсов и предметов</h1>";

// Получаем все кейсы
$cases = db()->fetchAll("SELECT * FROM cases");
echo "<h2>Кейсы:</h2>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Название</th><th>Цена</th><th>Предметов</th></tr>";
foreach ($cases as $case) {
    $count = db()->fetch("SELECT COUNT(*) as cnt FROM case_items WHERE case_id = ?", [$case['id']])['cnt'];
    echo "<tr>";
    echo "<td>{$case['id']}</td>";
    echo "<td>{$case['name']}</td>";
    echo "<td>{$case['price']}</td>";
    echo "<td>{$count}</td>";
    echo "</tr>";
}
echo "</table>";

// Получаем все предметы
$items = db()->fetchAll("SELECT * FROM items");
echo "<h2>Предметы:</h2>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Название</th><th>Цена</th><th>Редкость</th><th>Статус</th></tr>";
foreach ($items as $item) {
    echo "<tr>";
    echo "<td>{$item['id']}</td>";
    echo "<td>{$item['name']}</td>";
    echo "<td>{$item['price']}</td>";
    echo "<td>{$item['rarity']}</td>";
    echo "<td>" . ($item['is_active'] ? 'Активен' : 'Неактивен') . "</td>";
    echo "</tr>";
}
echo "</table>";

// Проверяем связи case_items
$case_items = db()->fetchAll("SELECT * FROM case_items");
echo "<h2>Связи кейсов с предметами:</h2>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Case ID</th><th>Item ID</th><th>Chance</th></tr>";
foreach ($case_items as $ci) {
    echo "<tr>";
    echo "<td>{$ci['id']}</td>";
    echo "<td>{$ci['case_id']}</td>";
    echo "<td>{$ci['item_id']}</td>";
    echo "<td>{$ci['chance']}%</td>";
    echo "</tr>";
}
echo "</table>";
?>