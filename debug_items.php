<?php
require_once '/includes/config.php';
require_once '/includes/db.php';

echo "<h1>Проверка сохранения предметов</h1>";

// Получаем все связи case_items
$items = db()->fetchAll("
    SELECT ci.*, c.name as case_name, i.name as item_name, i.price 
    FROM case_items ci
    JOIN cases c ON ci.case_id = c.id
    JOIN items i ON ci.item_id = i.id
    ORDER BY ci.case_id, ci.chance DESC
");

echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Кейс</th><th>Предмет</th><th>Цена</th><th>Шанс %</th></tr>";

foreach ($items as $item) {
    echo "<tr>";
    echo "<td>{$item['id']}</td>";
    echo "<td>{$item['case_name']}</td>";
    echo "<td>{$item['item_name']}</td>";
    echo "<td>{$item['price']} ₽</td>";
    echo "<td>{$item['chance']}%</td>";
    echo "</tr>";
}
echo "</table>";

// Проверяем сумму шансов по каждому кейсу
$cases = db()->fetchAll("SELECT * FROM cases");
echo "<h2>Сумма шансов по кейсам:</h2>";

foreach ($cases as $case) {
    $total = db()->fetch("SELECT SUM(chance) as total FROM case_items WHERE case_id = ?", [$case['id']])['total'];
    echo "<p>Кейс: {$case['name']} - <strong>" . number_format($total, 2) . "%</strong></p>";
}
?>