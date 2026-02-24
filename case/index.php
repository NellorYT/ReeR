<?php
require_once '../includes/functions.php';

// Получаем ID кейса из URL
$path = $_SERVER['REQUEST_URI'];
$parts = explode('/', rtrim($path, '/'));
$caseId = (int) end($parts);

if (!$caseId) {
    redirect(SITE_URL);
}

$case = db()->fetch(
    "SELECT c.*, m.name as marketplace_name, m.color as marketplace_color, m.slug as marketplace_slug 
     FROM cases c 
     LEFT JOIN marketplaces m ON c.marketplace_id = m.id 
     WHERE c.id = ? AND c.is_active = 1",
    [$caseId]
);

if (!$case) {
    redirect(SITE_URL);
}

// Получаем предметы кейса
$items = db()->fetchAll(
    "SELECT i.*, ci.chance 
     FROM items i 
     JOIN case_items ci ON i.id = ci.item_id 
     WHERE ci.case_id = ? AND i.is_active = 1
     ORDER BY i.price DESC",
    [$caseId]
);

// Статистика кейса
$stats = db()->fetch(
    "SELECT 
        COUNT(*) as total_opens,
        MAX(item_value) as best_win
     FROM case_opens 
     WHERE case_id = ?",
    [$caseId]
);

// Если нет открытий, лучший выигрыш = 0
if (!$stats['best_win']) {
    $stats['best_win'] = 0;
}

$pageTitle = $case['name'];
$pageDesc = $case['description'];

include '../includes/header.php';
?>

<div class="case-page">
    <div class="container">
        <!-- Заголовок -->
        <div class="case-header">
            <div class="case-marketplace-badge" style="background: <?= e($case['marketplace_color'] ?? '#7b61ff') ?>">
                <?= e($case['marketplace_name'] ?? 'UnionCase') ?>
            </div>
            <h1><?= e($case['name']) ?></h1>
            <p class="section-subtitle"><?= e($case['description']) ?></p>
        </div>

        <!-- Основной блок с кейсом -->
        <div class="case-open-section">
            <!-- Информация о кейсе -->
            <div class="case-info-card">
                <div class="case-image">
                    <?php if ($case['image']): ?>
                        <img src="<?= e(getImageUrl($case['image'])) ?>" alt="<?= e($case['name']) ?>">
                    <?php else: ?>
                        <i class="fas fa-box-open" style="font-size: 120px; color: <?= e($case['marketplace_color'] ?? '#7b61ff') ?>"></i>
                    <?php endif; ?>
                </div>

                <div class="case-stats">
                    <div class="case-stat">
                        <span><i class="fas fa-box-open"></i> Открытий</span>
                        <strong><?= number_format($stats['total_opens'] ?? 0) ?></strong>
                    </div>
                    <div class="case-stat">
                        <span><i class="fas fa-trophy"></i> Лучший выигрыш</span>
                        <strong style="color: var(--success)"><?= formatPrice($stats['best_win'] ?? 0) ?></strong>
                    </div>
                    <div class="case-stat">
                        <span><i class="fas fa-layer-group"></i> Предметов</span>
                        <strong><?= count($items) ?></strong>
                    </div>
                </div>

                <div class="case-price-tag">
                    <?= formatPrice($case['price']) ?>
                </div>

                <?php if (isLoggedIn()): ?>
                    <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                    <button onclick="openCase(<?= $case['id'] ?>)" class="case-open-btn-large" <?= ($currentUser['balance'] < $case['price']) ? 'disabled' : '' ?>>
                        <i class="fas fa-unlock"></i> 
                        <?= ($currentUser['balance'] < $case['price']) ? 'Недостаточно средств' : 'Открыть кейс' ?>
                    </button>
                <?php else: ?>
                    <a href="<?= SITE_URL ?>/login.php" class="case-open-btn-large" style="background: var(--accent-primary); text-decoration: none;">
                        <i class="fas fa-sign-in-alt"></i> Войдите чтобы открыть
                    </a>
                <?php endif; ?>
            </div>

            <!-- Барабан с предметами -->
            <div class="case-roulette">
            <div class="roulette-wrapper">
                <div class="roulette-container">
                    <div class="roulette-track" id="roulette-track"></div>
                </div>
                <div class="roulette-indicator"></div>
                <div class="roulette-glow"></div>
            </div>

                <?php if (!empty($items)): ?>
                <h3 style="margin: 30px 0 20px">Возможные выигрыши</h3>
                <div class="items-grid">
                    <?php foreach ($items as $item): ?>
                    <div class="item-card" style="--color: <?= e($item['color'] ?? getRarityColor($item['rarity'])) ?>">
                        <div class="item-rarity"><?= getRarityName($item['rarity']) ?></div>
                        <div class="item-image">
                            <?php if ($item['image']): ?>
                                <img src="<?= e(getImageUrl($item['image'])) ?>" alt="<?= e($item['name']) ?>">
                            <?php else: ?>
                                <i class="fas fa-gift"></i>
                            <?php endif; ?>
                        </div>
                        <div class="item-name"><?= e($item['name']) ?></div>
                        <div class="item-chance">Шанс: <?= number_format($item['chance'], 2) ?>%</div>
                        <div class="item-price"><?= formatPrice($item['price']) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно результата -->
<div class="modal-overlay" id="result-modal">
    <div class="modal modal-result">
        <div class="modal-header">
            <h3><i class="fas fa-trophy"></i> Вы выиграли!</h3>
            <button class="modal-close" onclick="closeResultModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body" id="result-content">
            <!-- Результат будет вставлен через JS -->
        </div>
    </div>
</div>

<script>
// Предметы для анимации
const caseItems = <?= json_encode($items) ?>;
console.log('Case items loaded:', caseItems);
</script>

<?php include '../includes/footer.php'; ?>
