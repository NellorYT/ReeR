<?php
require_once dirname(__FILE__) . '/functions.php';
startSession();
$currentUser = getCurrentUser();
$flash = getFlash();

// Определяем базовый URL сайта
$baseUrl = rtrim(SITE_URL, '/');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' — ' : '' ?><?= SITE_NAME ?></title>
    <meta name="description" content="<?= isset($pageDesc) ? e($pageDesc) : 'UnionCase — открывай кейсы с товарами лучших маркетплейсов' ?>">
    
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<!-- Скрытый CSRF токен для всех страниц -->
<?php if (isLoggedIn()): ?>
<input type="hidden" id="global-csrf-token" value="<?= getCsrfToken() ?>">
<?php endif; ?>

<!-- Шапка сайта -->
<header class="site-header">
    <div class="container">
        <div class="header-inner">
            <!-- Логотип -->
            <a href="<?= $baseUrl ?>" class="logo">
                <div class="logo-icon">
                    <i class="fas fa-cube"></i>
                </div>
                <span class="logo-text">Union<span class="logo-accent">Case</span></span>
            </a>

            <!-- Навигация -->
            <nav class="main-nav">
                <a href="<?= $baseUrl ?>" class="nav-link <?= (basename($_SERVER['PHP_SELF']) === 'index.php' && strpos($_SERVER['REQUEST_URI'], '/case/') === false && strpos($_SERVER['REQUEST_URI'], '/admin/') === false) ? 'active' : '' ?>">
                    <i class="fas fa-home"></i> Главная
                </a>
                <a href="<?= $baseUrl ?>/#cases" class="nav-link">
                    <i class="fas fa-box-open"></i> Кейсы
                </a>
                <?php if (isAdmin()): ?>
                <a href="<?= $baseUrl ?>/admin/" class="nav-link nav-admin">
                    <i class="fas fa-crown"></i> Админ
                </a>
                <?php endif; ?>
            </nav>

            <!-- Правая часть -->
            <div class="header-right">
                <?php if ($currentUser): ?>
                    <!-- Баланс -->
                    <div class="balance-widget">
                        <i class="fas fa-wallet"></i>
                        <span class="balance-amount" id="header-balance"><?= formatPrice($currentUser['balance']) ?></span>
                        <button class="btn-deposit" onclick="openDepositModal()">+</button>
                    </div>
                    <!-- Пользователь -->
                    <div class="user-menu">
                        <button class="user-btn" onclick="toggleUserMenu()">
                            <div class="user-avatar">
                                <?php if ($currentUser['avatar']): ?>
                                    <img src="<?= e(getImageUrl($currentUser['avatar'])) ?>" alt="Avatar">
                                <?php else: ?>
                                    <i class="fas fa-user"></i>
                                <?php endif; ?>
                            </div>
                            <span class="user-name"><?= e($currentUser['username']) ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="user-dropdown" id="user-dropdown">
                            <a href="<?= $baseUrl ?>/profile.php"><i class="fas fa-user"></i> Профиль</a>
                            <a href="<?= $baseUrl ?>/profile.php?tab=inventory"><i class="fas fa-archive"></i> Инвентарь</a>
                            <a href="<?= $baseUrl ?>/profile.php?tab=history"><i class="fas fa-history"></i> История</a>
                            <?php if (isAdmin()): ?>
                            <hr>
                            <a href="<?= $baseUrl ?>/admin/"><i class="fas fa-cog"></i> Панель управления</a>
                            <?php endif; ?>
                            <hr>
                            <a href="<?= $baseUrl ?>/logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Выйти</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?= $baseUrl ?>/login.php" class="btn btn-outline">Войти</a>
                    <a href="<?= $baseUrl ?>/register.php" class="btn btn-primary">Регистрация</a>
                <?php endif; ?>

                <!-- Мобильное меню -->
                <button class="mobile-menu-btn" onclick="toggleMobileMenu()">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Мобильное меню -->
    <div class="mobile-menu" id="mobile-menu">
        <a href="<?= $baseUrl ?>"><i class="fas fa-home"></i> Главная</a>
        <a href="<?= $baseUrl ?>/#cases"><i class="fas fa-box-open"></i> Кейсы</a>
        <?php if ($currentUser): ?>
            <a href="<?= $baseUrl ?>/profile.php"><i class="fas fa-user"></i> Профиль</a>
            <a href="<?= $baseUrl ?>/profile.php?tab=inventory"><i class="fas fa-archive"></i> Инвентарь</a>
            <?php if (isAdmin()): ?>
            <a href="<?= $baseUrl ?>/admin/"><i class="fas fa-crown"></i> Админ панель</a>
            <?php endif; ?>
            <a href="<?= $baseUrl ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Выйти</a>
        <?php else: ?>
            <a href="<?= $baseUrl ?>/login.php"><i class="fas fa-sign-in-alt"></i> Войти</a>
            <a href="<?= $baseUrl ?>/register.php"><i class="fas fa-user-plus"></i> Регистрация</a>
        <?php endif; ?>
    </div>
</header>

<!-- Flash-сообщение -->
<?php if ($flash): ?>
<div class="flash-message flash-<?= e($flash['type']) ?>" id="flash-msg">
    <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'error' ? 'times-circle' : 'info-circle') ?>"></i>
    <?= e($flash['message']) ?>
    <button onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
</div>
<?php endif; ?>

<!-- Модальное окно пополнения баланса -->
<?php if ($currentUser): ?>
<div class="modal-overlay" id="deposit-modal" onclick="if(event.target===this)closeDepositModal()">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-wallet"></i> Пополнение баланса</h3>
            <button class="modal-close" onclick="closeDepositModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <p class="modal-note">💡 Демо-режим: деньги начисляются мгновенно</p>
            <div class="deposit-amounts">
                <button class="deposit-btn" onclick="setDepositAmount(100)">100 ₽</button>
                <button class="deposit-btn" onclick="setDepositAmount(500)">500 ₽</button>
                <button class="deposit-btn" onclick="setDepositAmount(1000)">1 000 ₽</button>
                <button class="deposit-btn" onclick="setDepositAmount(5000)">5 000 ₽</button>
                <button class="deposit-btn" onclick="setDepositAmount(10000)">10 000 ₽</button>
            </div>
            <div class="deposit-custom">
                <input type="number" id="deposit-amount" placeholder="Введите сумму..." min="1" max="100000">
                <button class="btn btn-primary" onclick="processDeposit()">
                    <i class="fas fa-plus"></i> Пополнить
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<main class="main-content">