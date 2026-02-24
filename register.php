<?php
require_once __DIR__ . '/includes/functions.php';
startSession();

if (isLoggedIn()) redirect(SITE_URL . '/profile.php');

$pageTitle = 'Регистрация';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Неверный токен безопасности';
    } else {
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        if (empty($username)) {
            $errors[] = 'Введите имя пользователя';
        } elseif (mb_strlen($username) < 3 || mb_strlen($username) > 30) {
            $errors[] = 'Имя пользователя должно быть от 3 до 30 символов';
        } elseif (!preg_match('/^[a-zA-Z0-9_\-а-яёА-ЯЁ]+$/u', $username)) {
            $errors[] = 'Имя пользователя содержит недопустимые символы';
        }

        if (empty($email)) {
            $errors[] = 'Введите email';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Некорректный email адрес';
        }

        if (empty($password)) {
            $errors[] = 'Введите пароль';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Пароль должен быть не менее 6 символов';
        }

        if ($password !== $confirm) {
            $errors[] = 'Пароли не совпадают';
        }

        if (empty($errors)) {
            $existUser = db()->fetch("SELECT id FROM users WHERE username = ?", [$username]);
            if ($existUser) $errors[] = 'Это имя пользователя уже занято';

            $existEmail = db()->fetch("SELECT id FROM users WHERE email = ?", [$email]);
            if ($existEmail) $errors[] = 'Этот email уже зарегистрирован';
        }

        if (empty($errors)) {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
            $userId = db()->insert(
                "INSERT INTO users (username, email, password, balance) VALUES (?, ?, ?, 0.00)",
                [$username, $email, $hashedPassword]
            );

            updateBalance($userId, 500, 'bonus', 'Приветственный бонус');

            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = 'user';
            $_SESSION['balance'] = 500.00;

            setFlash('success', '🎉 Добро пожаловать, ' . $username . '! Тебе начислено 500 ₽ бонуса!');
            redirect(SITE_URL . '/');
        }
    }
}

include 'includes/header.php';
?>

<div class="auth-page">
    <!-- Декоративные элементы -->
    <div class="floating-gift" style="top: 15%; right: 10%; animation-delay: 0s;"></div>
    <div class="floating-gift" style="bottom: 20%; left: 5%; animation-delay: 2s;"></div>
    <div class="floating-gift" style="top: 60%; left: 15%; animation-delay: 4s;"></div>

    <div class="auth-container">
        <div class="auth-card">
            <!-- Бонусный баннер -->
            <div class="bonus-badge">
                <i class="fas fa-gift"></i>
                За регистрацию — 500 ₽ на счёт!
            </div>

            <div class="auth-logo">
                <i class="fas fa-cube"></i>
                <span>Union<span class="logo-accent">Case</span></span>
            </div>

            <h2 class="auth-title">Создать аккаунт</h2>
            <p class="auth-subtitle">
                <i class="fas fa-star"></i>
                Присоединяйся к тысячам победителей
            </p>

            <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $error): ?>
                <p><i class="fas fa-exclamation-circle"></i> <?= e($error) ?></p>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">

                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i>
                        Имя пользователя
                    </label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="form-input" 
                        placeholder="Придумайте никнейм"
                        value="<?= e($_POST['username'] ?? '') ?>"
                        autocomplete="username"
                        required
                        minlength="3"
                        maxlength="30"
                    >
                </div>

                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i>
                        Email адрес
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-input" 
                        placeholder="example@mail.com"
                        value="<?= e($_POST['email'] ?? '') ?>"
                        autocomplete="email"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i>
                        Пароль
                    </label>
                    <div class="input-password">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-input" 
                            placeholder="Минимум 6 символов"
                            autocomplete="new-password"
                            required
                            minlength="6"
                            oninput="updatePasswordStrength(this.value)"
                        >
                        <button type="button" class="toggle-password" onclick="togglePassword('password')">
                            <i class="fas fa-eye" id="password-icon"></i>
                        </button>
                    </div>
                    <div class="password-strength" id="password-strength"></div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">
                        <i class="fas fa-lock"></i>
                        Подтвердите пароль
                    </label>
                    <div class="input-password">
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            class="form-input" 
                            placeholder="Повторите пароль"
                            autocomplete="new-password"
                            required
                        >
                        <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">
                            <i class="fas fa-eye" id="confirm_password-icon"></i>
                        </button>
                    </div>
                </div>

                <div class="form-check">
                    <input type="checkbox" id="agree" name="agree" required>
                    <label for="agree">
                        Я принимаю <a href="#">правила сайта</a> и подтверждаю, что мне есть 18 лет
                    </label>
                </div>

                <button type="submit" class="btn-full">
                    <i class="fas fa-user-plus"></i>
                    Создать аккаунт и получить 500 ₽
                </button>
            </form>

            <div class="auth-divider">
                <span>или</span>
            </div>

            <div class="auth-links">
                <p>Уже есть аккаунт? <a href="<?= SITE_URL ?>/login.php">Войти</a></p>
            </div>

            <!-- Преимущества -->
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 20px; text-align: center;">
                <div>
                    <i class="fas fa-bolt" style="color: #ffd700; font-size: 20px;"></i>
                    <p style="font-size: 12px; color: var(--text-secondary);">Мгновенные выплаты</p>
                </div>
                <div>
                    <i class="fas fa-shield-alt" style="color: #4bff91; font-size: 20px;"></i>
                    <p style="font-size: 12px; color: var(--text-secondary);">100% честно</p>
                </div>
                <div>
                    <i class="fas fa-headset" style="color: #4b8bff; font-size: 20px;"></i>
                    <p style="font-size: 12px; color: var(--text-secondary);">Поддержка 24/7</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword(id) {
    const input = document.getElementById(id);
    const icon = document.getElementById(id + '-icon');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
}

function updatePasswordStrength(password) {
    const bar = document.getElementById('password-strength');
    let strength = 0;
    
    if (password.length >= 6) strength++;
    if (password.length >= 10) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^a-zA-Z0-9]/.test(password)) strength++;
    
    const labels = ['', 'Слабый', 'Слабый', 'Средний', 'Хороший', 'Отличный'];
    const colors = ['', '#ff4444', '#ff8800', '#ffcc00', '#88cc00', '#00cc44'];
    
    if (password) {
        bar.innerHTML = `
            <div class="strength-bar" style="width: ${strength * 20}%; background: ${colors[strength]}"></div>
            <span style="color: ${colors[strength]}">${labels[strength]}</span>
        `;
    } else {
        bar.innerHTML = '';
    }
}

// Плавное появление
document.addEventListener('DOMContentLoaded', function() {
    const card = document.querySelector('.auth-card');
    card.style.opacity = '0';
    setTimeout(() => {
        card.style.transition = 'opacity 0.5s ease';
        card.style.opacity = '1';
    }, 100);
});
</script>

<style>
/* Стили для декоративных элементов на странице регистрации */
.floating-gift {
    position: absolute;
    width: 50px;
    height: 50px;
    background: rgba(255, 215, 0, 0.1);
    border: 2px solid rgba(255, 215, 0, 0.3);
    border-radius: 10px;
    transform: rotate(15deg);
    animation: float-gift 10s ease-in-out infinite;
    z-index: 1;
    pointer-events: none;
}

.floating-gift::before {
    content: '🎁';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 24px;
    opacity: 0.5;
}

@keyframes float-gift {
    0%, 100% { transform: rotate(15deg) translate(0, 0); }
    25% { transform: rotate(25deg) translate(20px, 20px); }
    50% { transform: rotate(15deg) translate(40px, 0); }
    75% { transform: rotate(5deg) translate(20px, -20px); }
}
</style>

<?php include 'includes/footer.php'; ?>