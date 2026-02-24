<?php
require_once dirname(__FILE__) . '/db.php';
require_once dirname(__FILE__) . '/config.php';

// Запуск сессии
function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/',
            'secure'   => false,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        session_start();
    }
}

// Проверка авторизации
function isLoggedIn() {
    startSession();
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Получение текущего пользователя
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    return db()->fetch("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
}

// Проверка прав администратора
function isAdmin() {
    if (!isLoggedIn()) return false;
    $user = getCurrentUser();
    return $user && $user['role'] === 'admin';
}

// Редирект
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Экранирование HTML
function e($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// Форматирование цены
function formatPrice($price) {
    return number_format($price, 2, '.', ' ') . ' ₽';
}

// Генерация CSRF токена
function getCsrfToken() {
    startSession();
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Проверка CSRF токена
function verifyCsrfToken($token) {
    startSession();
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Генерация slug из строки
function generateSlug($str) {
    $str = mb_strtolower($str, 'UTF-8');
    $translit = [
        'а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'yo',
        'ж'=>'zh','з'=>'z','и'=>'i','й'=>'j','к'=>'k','л'=>'l','м'=>'m',
        'н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u',
        'ф'=>'f','х'=>'h','ц'=>'ts','ч'=>'ch','ш'=>'sh','щ'=>'sch',
        'ъ'=>'','ы'=>'y','ь'=>'','э'=>'e','ю'=>'yu','я'=>'ya',' '=>'-',
        '|'=>'-','/'=>'-','\\'=>'-','('=>'',')'=> ''
    ];
    $str = strtr($str, $translit);
    $str = preg_replace('/[^a-z0-9\-]/', '', $str);
    $str = preg_replace('/-+/', '-', $str);
    return trim($str, '-');
}

// Обновление баланса пользователя
function updateBalance($userId, $amount, $type, $description = '') {
    $user = db()->fetch("SELECT balance FROM users WHERE id = ?", [$userId]);
    if (!$user) return false;
    
    $balanceBefore = $user['balance'];
    $balanceAfter = $balanceBefore + $amount;
    
    if ($balanceAfter < 0) return false;
    
    db()->execute("UPDATE users SET balance = ? WHERE id = ?", [$balanceAfter, $userId]);
    db()->insert(
        "INSERT INTO balance_transactions (user_id, type, amount, description, balance_before, balance_after) VALUES (?, ?, ?, ?, ?, ?)",
        [$userId, $type, $amount, $description, $balanceBefore, $balanceAfter]
    );
    
    // Обновляем сессию
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $userId) {
        $_SESSION['balance'] = $balanceAfter;
    }
    
    return $balanceAfter;
}

// Открытие кейса
function openCase($userId, $caseId) {
    $case = db()->fetch("SELECT * FROM cases WHERE id = ? AND is_active = 1", [$caseId]);
    if (!$case) return ['error' => 'Кейс не найден'];
    
    $user = db()->fetch("SELECT * FROM users WHERE id = ?", [$userId]);
    if (!$user) return ['error' => 'Пользователь не найден'];
    
    if ($user['balance'] < $case['price']) {
        return ['error' => 'Недостаточно средств на балансе'];
    }
    
    // Получаем предметы кейса
    $items = db()->fetchAll(
        "SELECT i.*, ci.chance FROM items i 
         JOIN case_items ci ON i.id = ci.item_id 
         WHERE ci.case_id = ? AND i.is_active = 1",
        [$caseId]
    );
    
    if (empty($items)) return ['error' => 'В кейсе нет предметов'];
    
    // Розыгрыш предмета
    $totalChance = array_sum(array_column($items, 'chance'));
    $random = mt_rand(1, (int)($totalChance * 10000)) / 10000;
    $cumulative = 0;
    $wonItem = null;
    
    foreach ($items as $item) {
        $cumulative += $item['chance'];
        if ($random <= $cumulative) {
            $wonItem = $item;
            break;
        }
    }
    
    if (!$wonItem) $wonItem = $items[count($items) - 1];
    
    // Снимаем деньги
    $newBalance = updateBalance($userId, -$case['price'], 'case_open', 'Открытие кейса: ' . $case['name']);
    if ($newBalance === false) return ['error' => 'Недостаточно средств'];
    
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
    
    // Увеличиваем счётчик открытий кейса
    db()->execute("UPDATE cases SET opens_count = opens_count + 1 WHERE id = ?", [$caseId]);
    
    return [
        'success' => true,
        'item' => $wonItem,
        'balance' => $newBalance,
        'open_id' => $openId
    ];
}

// Продажа предмета из инвентаря
function sellInventoryItem($userId, $inventoryId) {
    $invItem = db()->fetch(
        "SELECT ui.*, i.price, i.name FROM user_inventory ui 
         JOIN items i ON ui.item_id = i.id 
         WHERE ui.id = ? AND ui.user_id = ? AND ui.is_sold = 0",
        [$inventoryId, $userId]
    );
    
    if (!$invItem) return ['error' => 'Предмет не найден'];
    
    $sellPrice = $invItem['price'] * 0.85; // 85% от стоимости
    
    db()->execute("UPDATE user_inventory SET is_sold = 1, sold_price = ? WHERE id = ?", [$sellPrice, $inventoryId]);
    updateBalance($userId, $sellPrice, 'item_sell', 'Продажа: ' . $invItem['name']);
    
    return ['success' => true, 'price' => $sellPrice];
}

// Загрузка изображения
function uploadImage($file, $subdir = '') {
    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    if ($file['size'] > MAX_UPLOAD_SIZE) return false;
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTENSIONS)) return false;
    
    $dir = UPLOAD_DIR . ($subdir ? $subdir . '/' : '');
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    
    $filename = uniqid() . '_' . time() . '.' . $ext;
    $filepath = $dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ($subdir ? $subdir . '/' : '') . $filename;
    }
    return false;
}

// Flash-сообщения
function setFlash($type, $message) {
    startSession();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    startSession();
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Получение редкости на русском
function getRarityName($rarity) {
    $names = [
        'common'   => 'Обычный',
        'uncommon' => 'Необычный',
        'rare'     => 'Редкий',
        'epic'     => 'Эпический',
        'legendary'=> 'Легендарный'
    ];
    return $names[$rarity] ?? 'Обычный';
}

// Получение цвета редкости
function getRarityColor($rarity) {
    $colors = [
        'common'   => '#b0b0b0',
        'uncommon' => '#4bff91',
        'rare'     => '#4b8bff',
        'epic'     => '#b24bff',
        'legendary'=> '#ffd700'
    ];
    return $colors[$rarity] ?? '#b0b0b0';
}

// Получение URL изображения
function getImageUrl($image, $default = null) {
    if ($image && file_exists(UPLOAD_DIR . $image)) {
        return UPLOAD_URL . $image;
    }
    if ($default) return $default;
    return SITE_URL . '/assets/images/default.png';
}

// Пагинация
function paginate($total, $perPage, $currentPage, $baseUrl) {
    $totalPages = ceil($total / $perPage);
    if ($totalPages <= 1) return '';

    // Определяем разделитель: '?' если в URL нет query-параметров, иначе '&'
    $sep = (strpos($baseUrl, '?') === false) ? '?' : '&';

    $html = '<nav class="pagination">';
    for ($i = 1; $i <= $totalPages; $i++) {
        $active = ($i == $currentPage) ? ' active' : '';
        $html .= "<a href=\"{$baseUrl}{$sep}page={$i}\" class=\"page-btn{$active}\">{$i}</a>";
    }
    $html .= '</nav>';
    return $html;
}

// Массовая продажа всех предметов
function sellAllInventoryItems($userId) {
    $items = db()->fetchAll(
        "SELECT ui.id, i.price, i.name FROM user_inventory ui 
         JOIN items i ON ui.item_id = i.id 
         WHERE ui.user_id = ? AND ui.is_sold = 0",
        [$userId]
    );
    
    if (empty($items)) {
        return ['error' => 'Нет предметов для продажи'];
    }
    
    $totalPrice = 0;
    $soldCount = 0;
    
    foreach ($items as $item) {
        $sellPrice = $item['price'] * 0.85;
        db()->execute(
            "UPDATE user_inventory SET is_sold = 1, sold_price = ? WHERE id = ?",
            [$sellPrice, $item['id']]
        );
        $totalPrice += $sellPrice;
        $soldCount++;
    }
    
    if ($totalPrice > 0) {
        updateBalance($userId, $totalPrice, 'item_sell', 'Массовая продажа ' . $soldCount . ' предметов');
    }
    
    return ['success' => true, 'total' => $totalPrice, 'count' => $soldCount];
}