<?php
require_once '../includes/functions.php';
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
$data = json_decode(file_get_contents('php://input'), true);

// Проверка CSRF токена
if (!verifyCsrfToken($data['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'error' => 'Неверный токен безопасности']);
    exit;
}

$amount = (float)($data['amount'] ?? 0);
$userId = $_SESSION['user_id'];

// Валидация
if ($amount <= 0 || $amount > 100000) {
    echo json_encode(['success' => false, 'error' => 'Некорректная сумма (от 1 до 100 000)']);
    exit;
}

// Пополнение баланса
$newBalance = updateBalance($userId, $amount, 'deposit', 'Пополнение баланса');

if ($newBalance === false) {
    echo json_encode(['success' => false, 'error' => 'Ошибка при пополнении баланса']);
    exit;
}

echo json_encode([
    'success' => true,
    'balance' => $newBalance,
    'message' => 'Баланс успешно пополнен на ' . number_format($amount, 2, '.', ' ') . ' ₽'
]);
?>