<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

startSession();

// Проверка авторизации
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Необходима авторизация']);
    exit;
}

// Проверка метода запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Неверный метод запроса']);
    exit;
}

// Получение данных
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Неверный формат данных']);
    exit;
}

// Проверка CSRF токена
if (!verifyCsrfToken($data['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'error' => 'Неверный токен безопасности']);
    exit;
}

$caseId = (int)($data['case_id'] ?? 0);
$userId = $_SESSION['user_id'];

if (!$caseId) {
    echo json_encode(['success' => false, 'error' => 'ID кейса не указан']);
    exit;
}

// Проверка существования кейса
$case = db()->fetch("SELECT * FROM cases WHERE id = ? AND is_active = 1", [$caseId]);
if (!$case) {
    echo json_encode(['success' => false, 'error' => 'Кейс не найден']);
    exit;
}

// Проверка баланса
$user = db()->fetch("SELECT * FROM users WHERE id = ?", [$userId]);
if (!$user) {
    echo json_encode(['success' => false, 'error' => 'Пользователь не найден']);
    exit;
}

if ($user['balance'] < $case['price']) {
    echo json_encode(['success' => false, 'error' => 'Недостаточно средств']);
    exit;
}

// Получаем предметы кейса с шансами
$items = db()->fetchAll(
    "SELECT i.*, ci.chance FROM items i 
     JOIN case_items ci ON i.id = ci.item_id 
     WHERE ci.case_id = ? AND i.is_active = 1",
    [$caseId]
);

if (empty($items)) {
    echo json_encode(['success' => false, 'error' => 'В кейсе нет предметов']);
    exit;
}

// Логируем для отладки
error_log("Items in case: " . print_r($items, true));

// Проверяем сумму шансов
$totalChance = array_sum(array_column($items, 'chance'));
error_log("Total chance: " . $totalChance);

// Розыгрыш предмета с учетом вероятностей
$random = mt_rand(1, 10000) / 100; // случайное число от 0.01 до 100.00
$cumulative = 0;
$wonItem = null;

foreach ($items as $item) {
    $normalizedChance = ($item['chance'] / $totalChance) * 100;
    $cumulative += $normalizedChance;
    error_log("Item: {$item['name']}, Chance: {$item['chance']}, Normalized: {$normalizedChance}, Cumulative: {$cumulative}, Random: {$random}");
    
    if ($random <= $cumulative) {
        $wonItem = $item;
        break;
    }
}

// На всякий случай, если не выпало
if (!$wonItem) {
    $wonItem = $items[array_rand($items)];
    error_log("No item won by chance, selecting random: " . $wonItem['name']);
}

error_log("Won item: " . $wonItem['name']);

// Списываем деньги
$newBalance = updateBalance($userId, -$case['price'], 'case_open', 'Открытие кейса: ' . $case['name']);
if ($newBalance === false) {
    echo json_encode(['success' => false, 'error' => 'Ошибка при списании средств']);
    exit;
}

// Записываем открытие
$openId = db()->insert(
    "INSERT INTO case_opens (user_id, case_id, item_id, price_paid, item_value) VALUES (?, ?, ?, ?, ?)",
    [$userId, $caseId, $wonItem['id'], $case['price'], $wonItem['price']]
);

// Добавляем в инвентарь
db()->insert(
    "INSERT INTO user_inventory (user_id, item_id, case_open_id) VALUES (?, ?, ?)",
    [$userId, $wonItem['id'], $openId]
);

// Увеличиваем счетчик открытий кейса
db()->execute("UPDATE cases SET opens_count = opens_count + 1 WHERE id = ?", [$caseId]);

// Получаем все предметы для анимации
$allItems = [];
for ($i = 0; $i < 10; $i++) {
    foreach ($items as $item) {
        $allItems[] = $item;
    }
}
// Перемешиваем
shuffle($allItems);

echo json_encode([
    'success' => true,
    'item' => $wonItem,
    'balance' => $newBalance,
    'items' => $allItems,
    'message' => 'Вы выиграли: ' . $wonItem['name']
]);
?>