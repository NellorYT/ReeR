<?php
require_once 'includes/functions.php';
startSession();

if (!isLoggedIn()) {
    setFlash('error', 'Войдите в аккаунт для просмотра профиля');
    redirect(SITE_URL . '/login.php');
}

$user = getCurrentUser();
$tab  = $_GET['tab'] ?? 'profile';
$pageTitle = 'Профиль — ' . $user['username'];

// Обработка форм
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Неверный токен безопасности');
        redirect(SITE_URL . '/profile.php');
    }

    $action = $_POST['action'] ?? '';

    // Продажа предмета
    if ($action === 'sell_item') {
        $invId  = (int)($_POST['inventory_id'] ?? 0);
        $result = sellInventoryItem($user['id'], $invId);
        if (isset($result['success'])) {
            setFlash('success', 'Предмет продан за ' . formatPrice($result['price']));
        } else {
            setFlash('error', $result['error'] ?? 'Ошибка продажи');
        }
        redirect(SITE_URL . '/profile.php?tab=inventory');
    }

    // Массовая продажа
    if ($action === 'sell_all') {
        $items = db()->fetchAll(
            "SELECT ui.id, i.price, i.name FROM user_inventory ui 
            JOIN items i ON ui.item_id = i.id 
            WHERE ui.user_id = ? AND ui.is_sold = 0",
            [$user['id']]
        );
        
        if (empty($items)) {
            setFlash('error', 'Нет предметов для продажи');
        } else {
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
                updateBalance($user['id'], $totalPrice, 'item_sell', 'Массовая продажа ' . $soldCount . ' предметов');
                setFlash('success', 'Продано ' . $soldCount . ' предметов на сумму ' . formatPrice($totalPrice));
            }
        }
        redirect(SITE_URL . '/profile.php?tab=inventory');
    }

    // Обновление профиля
    if ($action === 'update_profile') {
        $newUsername = trim($_POST['username'] ?? '');
        $newEmail    = trim($_POST['email'] ?? '');
        $errors = [];

        if (empty($newUsername) || mb_strlen($newUsername) < 3) $errors[] = 'Некорректное имя';
        if (empty($newEmail) || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) $errors[] = 'Некорректный email';

        if (empty($errors)) {
            $existU = db()->fetch("SELECT id FROM users WHERE username = ? AND id != ?", [$newUsername, $user['id']]);
            if ($existU) $errors[] = 'Имя уже занято';
            $existE = db()->fetch("SELECT id FROM users WHERE email = ? AND id != ?", [$newEmail, $user['id']]);
            if ($existE) $errors[] = 'Email уже занят';
        }

        if (empty($errors)) {
            // Загрузка аватара
            $avatar = $user['avatar'];
            if (!empty($_FILES['avatar']['name'])) {
                $uploaded = uploadImage($_FILES['avatar'], 'avatars');
                if ($uploaded) $avatar = $uploaded;
            }

            db()->execute(
                "UPDATE users SET username = ?, email = ?, avatar = ? WHERE id = ?",
                [$newUsername, $newEmail, $avatar, $user['id']]
            );
            $_SESSION['username'] = $newUsername;
            setFlash('success', 'Профиль обновлён!');
            redirect(SITE_URL . '/profile.php');
        } else {
            foreach ($errors as $e) setFlash('error', $e);
            redirect(SITE_URL . '/profile.php');
        }
    }

    // Смена пароля
    if ($action === 'change_password') {
        $oldPass = $_POST['old_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confPass = $_POST['confirm_password'] ?? '';

        if (!password_verify($oldPass, $user['password'])) {
            setFlash('error', 'Неверный текущий пароль');
        } elseif (strlen($newPass) < 6) {
            setFlash('error', 'Новый пароль должен быть не менее 6 символов');
        } elseif ($newPass !== $confPass) {
            setFlash('error', 'Пароли не совпадают');
        } else {
            $hashed = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
            db()->execute("UPDATE users SET password = ? WHERE id = ?", [$hashed, $user['id']]);
            setFlash('success', 'Пароль успешно изменён!');
        }
        redirect(SITE_URL . '/profile.php');
    }
}

// Инвентарь
$inventoryPage = max(1, (int)($_GET['ipage'] ?? 1));
$inventoryPerPage = 12;
$inventoryOffset = ($inventoryPage - 1) * $inventoryPerPage;

$inventoryTotal = db()->fetch(
    "SELECT COUNT(*) as cnt FROM user_inventory WHERE user_id = ? AND is_sold = 0",
    [$user['id']]
)['cnt'] ?? 0;

$inventory = db()->fetchAll(
    "SELECT ui.*, i.name as item_name, i.price as item_price, i.rarity, i.image as item_image, i.color as item_color
     FROM user_inventory ui 
     JOIN items i ON ui.item_id = i.id 
     WHERE ui.user_id = ? AND ui.is_sold = 0
     ORDER BY ui.obtained_at DESC
     LIMIT ? OFFSET ?",
    [$user['id'], $inventoryPerPage, $inventoryOffset]
);

// История открытий
$historyPage = max(1, (int)($_GET['hpage'] ?? 1));
$historyPerPage = 15;
$historyOffset = ($historyPage - 1) * $historyPerPage;

$historyTotal = db()->fetch(
    "SELECT COUNT(*) as cnt FROM case_opens WHERE user_id = ?",
    [$user['id']]
)['cnt'] ?? 0;

$history = db()->fetchAll(
    "SELECT co.*, c.name as case_name, i.name as item_name, i.rarity, i.image as item_image, i.color as item_color
     FROM case_opens co
     JOIN cases c ON co.case_id = c.id
     JOIN items i ON co.item_id = i.id
     WHERE co.user_id = ?
     ORDER BY co.created_at DESC
     LIMIT ? OFFSET ?",
    [$user['id'], $historyPerPage, $historyOffset]
);

// Транзакции
$transactions = db()->fetchAll(
    "SELECT * FROM balance_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 20",
    [$user['id']]
);

// Статистика пользователя
$userStats = db()->fetch(
    "SELECT 
        COUNT(*) as total_opens,
        SUM(price_paid) as total_spent,
        SUM(item_value) as total_won,
        MAX(item_value) as best_win
     FROM case_opens WHERE user_id = ?",
    [$user['id']]
);

include 'includes/header.php';
?>

<div class="profile-page">
    <div class="container">
        <!-- Шапка профиля -->
        <div class="profile-header">
            <div class="profile-avatar-wrap">
                <div class="profile-avatar">
                    <?php if ($user['avatar']): ?>
                        <img src="<?= e(getImageUrl($user['avatar'])) ?>" alt="Аватар">
                    <?php else: ?>
                        <i class="fas fa-user"></i>
                    <?php endif; ?>
                </div>
                <?php if ($user['role'] === 'admin'): ?>
                <div class="profile-badge admin-badge"><i class="fas fa-crown"></i> Admin</div>
                <?php endif; ?>
            </div>
            <div class="profile-info">
                <h1 class="profile-username"><?= e($user['username']) ?></h1>
                <p class="profile-email"><i class="fas fa-envelope"></i> <?= e($user['email']) ?></p>
                <p class="profile-since"><i class="fas fa-calendar"></i> На сайте с <?= date('d.m.Y', strtotime($user['created_at'])) ?></p>
            </div>
            <div class="profile-balance-card">
                <div class="pb-label"><i class="fas fa-wallet"></i> Баланс</div>
                <div class="pb-amount"><?= formatPrice($user['balance']) ?></div>
                <button class="btn btn-primary btn-sm" onclick="openDepositModal()">
                    <i class="fas fa-plus"></i> Пополнить
                </button>
            </div>
        </div>

        <!-- Статистика -->
        <div class="profile-stats">
            <div class="pstat-card">
                <i class="fas fa-box-open"></i>
                <div>
                    <span class="pstat-val"><?= number_format($userStats['total_opens'] ?? 0) ?></span>
                    <span class="pstat-label">Открытий</span>
                </div>
            </div>
            <div class="pstat-card">
                <i class="fas fa-arrow-down" style="color:#ff4444"></i>
                <div>
                    <span class="pstat-val"><?= formatPrice($userStats['total_spent'] ?? 0) ?></span>
                    <span class="pstat-label">Потрачено</span>
                </div>
            </div>
            <div class="pstat-card">
                <i class="fas fa-arrow-up" style="color:#4bff91"></i>
                <div>
                    <span class="pstat-val"><?= formatPrice($userStats['total_won'] ?? 0) ?></span>
                    <span class="pstat-label">Выиграно</span>
                </div>
            </div>
            <div class="pstat-card">
                <i class="fas fa-trophy" style="color:#ffd700"></i>
                <div>
                    <span class="pstat-val"><?= formatPrice($userStats['best_win'] ?? 0) ?></span>
                    <span class="pstat-label">Лучший выигрыш</span>
                </div>
            </div>
        </div>

        <!-- Вкладки -->
        <div class="profile-tabs">
            <a href="?tab=profile" class="tab-btn <?= $tab === 'profile' ? 'active' : '' ?>">
                <i class="fas fa-user"></i> Профиль
            </a>
            <a href="?tab=inventory" class="tab-btn <?= $tab === 'inventory' ? 'active' : '' ?>">
                <i class="fas fa-archive"></i> Инвентарь
                <?php if ($inventoryTotal > 0): ?>
                <span class="tab-count"><?= $inventoryTotal ?></span>
                <?php endif; ?>
            </a>
            <a href="?tab=history" class="tab-btn <?= $tab === 'history' ? 'active' : '' ?>">
                <i class="fas fa-history"></i> История
            </a>
            <a href="?tab=transactions" class="tab-btn <?= $tab === 'transactions' ? 'active' : '' ?>">
                <i class="fas fa-exchange-alt"></i> Транзакции
            </a>
        </div>

        <!-- Контент вкладок -->
        <div class="profile-content">

            <!-- Вкладка "Профиль" -->
            <?php if ($tab === 'profile'): ?>
            <div class="tab-content active">
                <div class="profile-forms">
                    <!-- Редактирование профиля -->
                    <div class="pform-card">
                        <h3><i class="fas fa-edit"></i> Редактировать профиль</h3>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                            <input type="hidden" name="action" value="update_profile">
                            <div class="form-group">
                                <label>Имя пользователя</label>
                                <input type="text" name="username" class="form-input" value="<?= e($user['username']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" class="form-input" value="<?= e($user['email']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Аватар</label>
                                <div class="file-upload-wrap">
                                    <input type="file" name="avatar" id="avatar-upload" accept="image/*" onchange="previewAvatar(this)">
                                    <label for="avatar-upload" class="file-upload-label">
                                        <i class="fas fa-upload"></i> Выбрать фото
                                    </label>
                                    <img id="avatar-preview" style="display:none;width:60px;height:60px;border-radius:50%;object-fit:cover;margin-left:10px">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Сохранить
                            </button>
                        </form>
                    </div>

                    <!-- Смена пароля -->
                    <div class="pform-card">
                        <h3><i class="fas fa-lock"></i> Сменить пароль</h3>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                            <input type="hidden" name="action" value="change_password">
                            <div class="form-group">
                                <label>Текущий пароль</label>
                                <input type="password" name="old_password" class="form-input" required>
                            </div>
                            <div class="form-group">
                                <label>Новый пароль</label>
                                <input type="password" name="new_password" class="form-input" minlength="6" required>
                            </div>
                            <div class="form-group">
                                <label>Подтвердите пароль</label>
                                <input type="password" name="confirm_password" class="form-input" required>
                            </div>
                            <button type="submit" class="btn btn-secondary">
                                <i class="fas fa-key"></i> Изменить пароль
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Вкладка "Инвентарь" -->
            <?php elseif ($tab === 'inventory'): ?>
            <div class="tab-content active">
                <?php if (empty($inventory)): ?>
                <div class="empty-state">
                    <i class="fas fa-archive"></i>
                    <h3>Инвентарь пуст</h3>
                    <p>Открой кейс и получи предмет!</p>
                    <a href="<?= SITE_URL ?>/#cases" class="btn btn-primary">Открыть кейс</a>
                </div>
                <?php else: ?>
                <div class="inventory-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3>Мои предметы <span style="color: var(--text-secondary);">(<?= $inventoryTotal ?>)</span></h3>
                    <button onclick="sellAllItems()" class="btn btn-primary" style="background: linear-gradient(135deg, #ffd700, #ffb84b);">
                        <i class="fas fa-coins"></i> Продать всё
                    </button>
                </div>
                
                <div class="inventory-grid">
                    <?php foreach ($inventory as $inv): ?>
                    <div class="inv-item" style="--rarity-color: <?= e($inv['item_color']) ?>">
                        <div class="inv-item-rarity"><?= getRarityName($inv['rarity']) ?></div>
                        <div class="inv-item-img">
                            <?php if ($inv['item_image']): ?>
                                <img src="<?= e(getImageUrl($inv['item_image'])) ?>" alt="">
                            <?php else: ?>
                                <i class="fas fa-gift"></i>
                            <?php endif; ?>
                        </div>
                        <div class="inv-item-name"><?= e($inv['item_name']) ?></div>
                        <div class="inv-item-price"><?= formatPrice($inv['item_price']) ?></div>
                        <form method="POST" onsubmit="return confirm('Продать за <?= formatPrice($inv['item_price'] * 0.85) ?>?')">
                            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                            <input type="hidden" name="action" value="sell_item">
                            <input type="hidden" name="inventory_id" value="<?= $inv['id'] ?>">
                            <button type="submit" class="btn-sell">
                                <i class="fas fa-coins"></i> Продать за <?= formatPrice($inv['item_price'] * 0.85) ?>
                            </button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Скрытая форма для массовой продажи -->
                <form id="sell-all-form" method="POST" style="display: none;">
                    <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                    <input type="hidden" name="action" value="sell_all">
                </form>
                
                <?php echo paginate($inventoryTotal, $inventoryPerPage, $inventoryPage, SITE_URL . '/profile.php?tab=inventory'); ?>
                <?php endif; ?>
            </div>

            <!-- Скрытое поле с общей суммой -->
            <?php 
            $totalSellPrice = 0;
            foreach ($inventory as $inv) {
                $totalSellPrice += $inv['item_price'] * 0.85;
            }
            ?>
            <input type="hidden" id="sell-all-total" value="<?= $totalSellPrice ?>">

            <!-- Вкладка "История" -->
            <?php elseif ($tab === 'history'): ?>
            <div class="tab-content active">
                <?php if (empty($history)): ?>
                <div class="empty-state">
                    <i class="fas fa-history"></i>
                    <h3>История пуста</h3>
                    <p>Ты ещё не открывал кейсы</p>
                    <a href="<?= SITE_URL ?>/#cases" class="btn btn-primary">Открыть первый кейс</a>
                </div>
                <?php else: ?>
                <div class="history-table-wrap">
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Предмет</th>
                                <th>Кейс</th>
                                <th>Редкость</th>
                                <th>Стоимость</th>
                                <th>Уплачено</th>
                                <th>Дата</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history as $h): ?>
                            <tr>
                                <td>
                                    <div class="history-item-cell">
                                        <div class="history-item-img" style="border-color: <?= e($h['item_color']) ?>">
                                            <?php if ($h['item_image']): ?>
                                                <img src="<?= e(getImageUrl($h['item_image'])) ?>" alt="">
                                            <?php else: ?>
                                                <i class="fas fa-gift"></i>
                                            <?php endif; ?>
                                        </div>
                                        <?= e($h['item_name']) ?>
                                    </div>
                                </td>
                                <td><?= e($h['case_name']) ?></td>
                                <td>
                                    <span class="rarity-badge" style="background: <?= e($h['item_color']) ?>22; color: <?= e($h['item_color']) ?>; border: 1px solid <?= e($h['item_color']) ?>44">
                                        <?= getRarityName($h['rarity']) ?>
                                    </span>
                                </td>
                                <td class="price-cell"><?= formatPrice($h['item_value']) ?></td>
                                <td class="price-cell minus"><?= formatPrice($h['price_paid']) ?></td>
                                <td class="date-cell"><?= date('d.m.Y H:i', strtotime($h['created_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php echo paginate($historyTotal, $historyPerPage, $historyPage, SITE_URL . '/profile.php?tab=history'); ?>
                <?php endif; ?>
            </div>

            <!-- Вкладка "Транзакции" -->
            <?php elseif ($tab === 'transactions'): ?>
            <div class="tab-content active">
                <?php if (empty($transactions)): ?>
                <div class="empty-state">
                    <i class="fas fa-exchange-alt"></i>
                    <h3>Транзакций нет</h3>
                </div>
                <?php else: ?>
                <div class="history-table-wrap">
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Тип</th>
                                <th>Описание</th>
                                <th>Сумма</th>
                                <th>Баланс до</th>
                                <th>Баланс после</th>
                                <th>Дата</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $typeNames = [
                                'deposit'   => ['Пополнение', '#4bff91', 'fa-arrow-up'],
                                'withdraw'  => ['Вывод', '#ff4444', 'fa-arrow-down'],
                                'case_open' => ['Открытие кейса', '#ff8844', 'fa-box-open'],
                                'item_sell' => ['Продажа предмета', '#4b8bff', 'fa-coins'],
                                'bonus'     => ['Бонус', '#ffd700', 'fa-gift'],
                            ];
                            foreach ($transactions as $t):
                                $typeInfo = $typeNames[$t['type']] ?? ['Операция', '#ffffff', 'fa-exchange-alt'];
                                $isPos = $t['amount'] > 0;
                            ?>
                            <tr>
                                <td>
                                    <span style="color: <?= $typeInfo[1] ?>">
                                        <i class="fas <?= $typeInfo[2] ?>"></i> <?= $typeInfo[0] ?>
                                    </span>
                                </td>
                                <td><?= e($t['description'] ?? '') ?></td>
                                <td class="price-cell <?= $isPos ? 'plus' : 'minus' ?>">
                                    <?= $isPos ? '+' : '' ?><?= formatPrice($t['amount']) ?>
                                </td>
                                <td><?= formatPrice($t['balance_before']) ?></td>
                                <td><?= formatPrice($t['balance_after']) ?></td>
                                <td class="date-cell"><?= date('d.m.Y H:i', strtotime($t['created_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script>
function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            const img = document.getElementById('avatar-preview');
            img.src = e.target.result;
            img.style.display = 'inline-block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function sellAllItems() {
    if (confirm('Продать все предметы из инвентаря? Сумма: примерно <?= formatPrice(array_sum(array_column($inventory, 'item_price')) * 0.85) ?>')) {
        document.getElementById('sell-all-form').submit();
    }
}
</script>

<?php include 'includes/footer.php'; ?>
