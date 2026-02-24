<?php
require_once '../includes/functions.php';
startSession();

if (!isAdmin()) {
    setFlash('error', 'Доступ запрещен');
    redirect(SITE_URL);
}

$pageTitle = 'Панель управления';
include '../includes/header.php';

$stats = [
    'users' => db()->fetch("SELECT COUNT(*) as cnt FROM users")['cnt'],
    'cases' => db()->fetch("SELECT COUNT(*) as cnt FROM cases")['cnt'],
    'items' => db()->fetch("SELECT COUNT(*) as cnt FROM items")['cnt'],
    'opens' => db()->fetch("SELECT COUNT(*) as cnt FROM case_opens")['cnt'],
    'total_spent' => db()->fetch("SELECT SUM(price_paid) as total FROM case_opens")['total'] ?? 0,
    'total_won' => db()->fetch("SELECT SUM(item_value) as total FROM case_opens")['total'] ?? 0,
];
?>

<div class="admin-page">
    <div class="container">
        <h1 class="page-title">Панель управления</h1>
        
        <div class="admin-stats">
            <div class="stat-card">
                <i class="fas fa-users"></i>
                <div>
                    <span class="stat-value"><?= $stats['users'] ?></span>
                    <span class="stat-label">Пользователей</span>
                </div>
            </div>
            <div class="stat-card">
                <i class="fas fa-box"></i>
                <div>
                    <span class="stat-value"><?= $stats['cases'] ?></span>
                    <span class="stat-label">Кейсов</span>
                </div>
            </div>
            <div class="stat-card">
                <i class="fas fa-cube"></i>
                <div>
                    <span class="stat-value"><?= $stats['items'] ?></span>
                    <span class="stat-label">Предметов</span>
                </div>
            </div>
            <div class="stat-card">
                <i class="fas fa-box-open"></i>
                <div>
                    <span class="stat-value"><?= $stats['opens'] ?></span>
                    <span class="stat-label">Открытий</span>
                </div>
            </div>
            <div class="stat-card">
                <i class="fas fa-arrow-down"></i>
                <div>
                    <span class="stat-value"><?= formatPrice($stats['total_spent']) ?></span>
                    <span class="stat-label">Потрачено</span>
                </div>
            </div>
            <div class="stat-card">
                <i class="fas fa-arrow-up"></i>
                <div>
                    <span class="stat-value"><?= formatPrice($stats['total_won']) ?></span>
                    <span class="stat-label">Выиграно</span>
                </div>
            </div>
        </div>

        <div class="admin-menu">
            <a href="cases.php" class="admin-menu-item">
                <i class="fas fa-box"></i>
                <h3>Управление кейсами</h3>
                <p>Добавление, редактирование и удаление кейсов</p>
            </a>
            <a href="items.php" class="admin-menu-item">
                <i class="fas fa-cube"></i>
                <h3>Управление предметами</h3>
                <p>Добавление товаров с ценами и редкостью</p>
            </a>
            <a href="marketplace.php" class="admin-menu-item">
                <i class="fas fa-store"></i>
                <h3>Маркетплейсы</h3>
                <p>Управление категориями маркетплейсов</p>
            </a>
            <a href="users.php" class="admin-menu-item">
                <i class="fas fa-users"></i>
                <h3>Пользователи</h3>
                <p>Управление пользователями и балансом</p>
            </a>
        </div>
    </div>
</div>

<style>
.admin-page {
    padding: 40px 0;
}

.page-title {
    font-size: 36px;
    font-weight: 800;
    margin-bottom: 40px;
    background: linear-gradient(135deg, white, var(--text-secondary));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.admin-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.stat-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 20px;
}

.stat-card i {
    font-size: 40px;
    color: var(--accent-primary);
}

.stat-value {
    display: block;
    font-size: 28px;
    font-weight: 800;
    color: white;
}

.stat-label {
    color: var(--text-secondary);
    font-size: 14px;
}

.admin-menu {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.admin-menu-item {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 30px;
    text-decoration: none;
    color: white;
    transition: var(--transition);
}

.admin-menu-item:hover {
    transform: translateY(-5px);
    border-color: var(--accent-primary);
    box-shadow: var(--shadow);
}

.admin-menu-item i {
    font-size: 48px;
    color: var(--accent-primary);
    margin-bottom: 20px;
}

.admin-menu-item h3 {
    font-size: 20px;
    margin-bottom: 10px;
}

.admin-menu-item p {
    color: var(--text-secondary);
    font-size: 14px;
}
</style>

<?php include '../includes/footer.php'; ?>
