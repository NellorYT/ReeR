<?php
require_once '/includes/functions.php';
$pageTitle = 'Главная';
$pageDesc = 'UnionCase — открывай кейсы с товарами лучших маркетплейсов. Steam, Wildberries, OZON, AliExpress';

// Получаем маркетплейсы
$marketplaces = db()->fetchAll("SELECT * FROM marketplaces WHERE is_active = 1 ORDER BY sort_order ASC");

// Получаем все активные кейсы
$allCases = db()->fetchAll(
    "SELECT c.*, m.name as marketplace_name, m.color as marketplace_color, m.slug as marketplace_slug
     FROM cases c 
     LEFT JOIN marketplaces m ON c.marketplace_id = m.id 
     WHERE c.is_active = 1 
     ORDER BY c.sort_order ASC, c.id ASC"
);

// Группируем кейсы по маркетплейсам
$casesByMarket = [];
foreach ($allCases as $case) {
    $mId = $case['marketplace_id'] ?? 0;
    $casesByMarket[$mId][] = $case;
}

// Последние выигрыши
$recentWins = db()->fetchAll(
    "SELECT co.*, u.username, i.name as item_name, i.rarity, i.image as item_image, i.price as item_price, i.color as item_color, c.name as case_name
     FROM case_opens co
     JOIN users u ON co.user_id = u.id
     JOIN items i ON co.item_id = i.id
     JOIN cases c ON co.case_id = c.id
     ORDER BY co.created_at DESC
     LIMIT 10"
);

// Статистика
$stats = db()->fetch("SELECT COUNT(*) as total_opens, COUNT(DISTINCT user_id) as total_users FROM case_opens");
$totalCases = db()->fetch("SELECT COUNT(*) as cnt FROM cases WHERE is_active = 1");

include 'includes/header.php';
?>

<!-- Герой секция -->
<section class="hero-section">
    <div class="hero-bg">
        <div class="hero-particle"></div>
        <div class="hero-particle"></div>
        <div class="hero-particle"></div>
    </div>
    <div class="container">
        <div class="hero-content">
            <div class="hero-badge">
                <i class="fas fa-fire"></i> Лучшая площадка для открытия кейсов
            </div>
            <h1 class="hero-title">
                Открывай кейсы<br>
                <span class="gradient-text">и выигрывай призы!</span>
            </h1>
            <p class="hero-desc">
                Steam, Wildberries, OZON, AliExpress и Amazon — 
                все лучшие маркетплейсы в одном месте
            </p>
            <div class="hero-actions">
                <a href="#cases" class="btn btn-primary btn-lg">
                    <i class="fas fa-box-open"></i> Открыть кейс
                </a>
                <?php if (!isLoggedIn()): ?>
                <a href="<?= SITE_URL ?>/register.php" class="btn btn-outline btn-lg">
                    <i class="fas fa-user-plus"></i> Регистрация
                </a>
                <?php endif; ?>
            </div>
            <div class="hero-stats">
                <div class="hero-stat">
                    <span class="stat-value"><?= number_format($stats['total_opens'] ?? 0) ?></span>
                    <span class="stat-label">открытий</span>
                </div>
                <div class="hero-stat">
                    <span class="stat-value"><?= number_format($stats['total_users'] ?? 0) ?></span>
                    <span class="stat-label">игроков</span>
                </div>
                <div class="hero-stat">
                    <span class="stat-value"><?= $totalCases['cnt'] ?? 0 ?></span>
                    <span class="stat-label">кейсов</span>
                </div>
            </div>
        </div>
        <!-- Плавающие кейсы -->
        <div class="hero-cases-preview">
            <?php foreach (array_slice($allCases, 0, 3) as $i => $hcase): ?>
            <div class="floating-case floating-case-<?= $i+1 ?>">
                <div class="floating-case-inner" style="background: linear-gradient(135deg, <?= e($hcase['marketplace_color'] ?? '#1a1a2e') ?>, #1a1a2e)">
                    <?php if ($hcase['image']): ?>
                        <img src="<?= e(getImageUrl($hcase['image'])) ?>" alt="<?= e($hcase['name']) ?>">
                    <?php else: ?>
                        <i class="fas fa-box case-icon"></i>
                    <?php endif; ?>
                    <span><?= e($hcase['name']) ?></span>
                    <strong><?= formatPrice($hcase['price']) ?></strong>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Лента последних выигрышей -->
<?php if (!empty($recentWins)): ?>
<div class="wins-ticker">
    <div class="ticker-label"><i class="fas fa-trophy"></i> Последние выигрыши</div>
    <div class="ticker-track" id="wins-ticker">
        <?php for ($repeat = 0; $repeat < 2; $repeat++): ?>
        <?php foreach ($recentWins as $win): ?>
        <div class="ticker-item" style="--rarity-color: <?= e($win['item_color']) ?>">
            <div class="ticker-item-img">
                <?php if ($win['item_image']): ?>
                    <img src="<?= e(getImageUrl($win['item_image'])) ?>" alt="">
                <?php else: ?>
                    <i class="fas fa-gift"></i>
                <?php endif; ?>
            </div>
            <div class="ticker-item-info">
                <span class="ticker-user"><?= e($win['username']) ?></span>
                <span class="ticker-item-name"><?= e($win['item_name']) ?></span>
                <span class="ticker-price"><?= formatPrice($win['item_price']) ?></span>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endfor; ?>
    </div>
</div>
<?php endif; ?>

<!-- Секция кейсов -->
<section class="cases-section" id="cases">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Все кейсы</h2>
            <p class="section-subtitle">Выбери кейс и испытай удачу!</p>
        </div>

        <!-- Фильтр по маркетплейсам -->
        <div class="marketplace-filter">
            <button class="filter-btn active" data-filter="all">
                <i class="fas fa-th-large"></i> Все
            </button>
            <?php foreach ($marketplaces as $mp): ?>
            <?php if (isset($casesByMarket[$mp['id']])): ?>
            <button class="filter-btn" data-filter="<?= e($mp['slug']) ?>" style="--mp-color: <?= e($mp['color']) ?>">
                <?= e($mp['name']) ?>
            </button>
            <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <!-- Сетка кейсов -->
        <div class="cases-grid" id="cases-grid">
            <?php foreach ($allCases as $case): ?>
            <div class="case-card" data-marketplace="<?= e($case['marketplace_slug'] ?? 'other') ?>">
                <div class="case-card-glow" style="--glow-color: <?= e($case['marketplace_color'] ?? '#7b61ff') ?>"></div>
                <a href="<?= SITE_URL ?>/case/<?= $case['id'] ?>" class="case-card-link">
                    <div class="case-card-badge" style="background: <?= e($case['marketplace_color'] ?? '#7b61ff') ?>">
                        <?= e($case['marketplace_name'] ?? 'Other') ?>
                    </div>
                    <div class="case-card-img">
                        <?php if ($case['image']): ?>
                            <img src="<?= e(getImageUrl($case['image'])) ?>" alt="<?= e($case['name']) ?>">
                        <?php else: ?>
                            <div class="case-default-img" style="background: linear-gradient(135deg, <?= e($case['marketplace_color'] ?? '#7b61ff') ?>22, <?= e($case['marketplace_color'] ?? '#7b61ff') ?>44)">
                                <i class="fas fa-box-open" style="color: <?= e($case['marketplace_color'] ?? '#7b61ff') ?>"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="case-card-info">
                        <h3 class="case-card-name"><?= e($case['name']) ?></h3>
                        <?php if ($case['description']): ?>
                        <p class="case-card-desc"><?= e($case['description']) ?></p>
                        <?php endif; ?>
                        <div class="case-card-footer">
                            <div class="case-price">
                                <i class="fas fa-tag"></i>
                                <strong><?= formatPrice($case['price']) ?></strong>
                            </div>
                            <div class="case-opens">
                                <i class="fas fa-box-open"></i>
                                <?= number_format($case['opens_count']) ?>
                            </div>
                        </div>
                    </div>
                </a>
                <a href="<?= SITE_URL ?>/case/<?= $case['id'] ?>" class="case-open-btn" style="background: linear-gradient(135deg, <?= e($case['marketplace_color'] ?? '#7b61ff') ?>, <?= e($case['marketplace_color'] ?? '#7b61ff') ?>99)">
                    <i class="fas fa-unlock"></i> Открыть
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Секция "Как это работает" -->
<section class="how-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Как это работает?</h2>
        </div>
        <div class="how-grid">
            <div class="how-step">
                <div class="how-icon"><i class="fas fa-user-plus"></i></div>
                <div class="how-num">1</div>
                <h3>Регистрация</h3>
                <p>Создай аккаунт бесплатно за несколько секунд</p>
            </div>
            <div class="how-arrow"><i class="fas fa-arrow-right"></i></div>
            <div class="how-step">
                <div class="how-icon"><i class="fas fa-wallet"></i></div>
                <div class="how-num">2</div>
                <h3>Пополни баланс</h3>
                <p>Пополни счёт удобным способом</p>
            </div>
            <div class="how-arrow"><i class="fas fa-arrow-right"></i></div>
            <div class="how-step">
                <div class="how-icon"><i class="fas fa-box-open"></i></div>
                <div class="how-num">3</div>
                <h3>Открой кейс</h3>
                <p>Выбери понравившийся кейс и открой его</p>
            </div>
            <div class="how-arrow"><i class="fas fa-arrow-right"></i></div>
            <div class="how-step">
                <div class="how-icon"><i class="fas fa-gift"></i></div>
                <div class="how-num">4</div>
                <h3>Получи приз!</h3>
                <p>Получи предмет или продай его обратно</p>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>