<?php
require_once __DIR__ . '/includes/functions.php';
startSession();

if (isLoggedIn()) redirect(SITE_URL . '/profile.php');

$pageTitle = 'Вход';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Неверный токен безопасности';
    } else {
        $login = trim($_POST['login'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($login)) $errors[] = 'Введите логин или email';
        if (empty($password)) $errors[] = 'Введите пароль';

        if (empty($errors)) {
            $user = db()->fetch(
                "SELECT * FROM users WHERE (username = ? OR email = ?) LIMIT 1",
                [$login, $login]
            );

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['balance'] = $user['balance'];

                setFlash('success', '🎉 Добро пожаловать, ' . $user['username'] . '!');
                redirect(SITE_URL . '/');
            } else {
                $errors[] = 'Неверный логин или пароль';
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="auth-page">
    <!-- Декоративные элементы -->
    <div class="floating-cube" style="top: 10%; left: 5%; animation-delay: 0s;"></div>
    <div class="floating-cube" style="top: 70%; right: 5%; animation-delay: 2s;"></div>
    <div class="floating-cube" style="bottom: 20%; left: 10%; animation-delay: 4s;"></div>

    <div class="auth-container">
        <div class="auth-card">
            <!-- Анимированный логотип -->
            <div class="auth-logo">
                <i class="fas fa-cube"></i>
                <span>Union<span class="logo-accent">Case</span></span>
            </div>

            <h2 class="auth-title">С возвращением!</h2>
            <p class="auth-subtitle">
                <i class="fas fa-fire"></i> 
                Войди и продолжай открывать кейсы
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
                    <label for="login">
                        <i class="fas fa-user"></i>
                        Логин или Email
                    </label>
                    <input 
                        type="text" 
                        id="login" 
                        name="login" 
                        class="form-input" 
                        placeholder="например: admin или admin@unioncase.ru"
                        value="<?= e($_POST['login'] ?? '') ?>"
                        autocomplete="username"
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
                            placeholder="••••••••"
                            autocomplete="current-password"
                            required
                        >
                        <button type="button" class="toggle-password" onclick="togglePassword('password')">
                            <i class="fas fa-eye" id="password-icon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-full">
                    <i class="fas fa-sign-in-alt"></i>
                    Войти в аккаунт
                </button>
            </form>

            <div class="auth-divider">
                <span>или</span>
            </div>

            <div class="auth-links">
                <p>Нет аккаунта? <a href="<?= SITE_URL ?>/register.php">Создать аккаунт</a></p>
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

// Добавляем плавное появление формы
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
/* Дополнительные анимации для страницы входа */
.floating-cube {
    position: absolute;
    width: 60px;
    height: 60px;
    background: rgba(123, 97, 255, 0.1);
    border: 2px solid rgba(123, 97, 255, 0.3);
    border-radius: 15px;
    transform: rotate(45deg);
    animation: float-cube 8s ease-in-out infinite;
    z-index: 1;
    pointer-events: none;
}

@keyframes float-cube {
    0%, 100% { transform: rotate(45deg) translate(0, 0); }
    25% { transform: rotate(55deg) translate(20px, 20px); }
    50% { transform: rotate(45deg) translate(40px, 0); }
    75% { transform: rotate(35deg) translate(20px, -20px); }
}
</style>

<?php include 'includes/footer.php'; ?>