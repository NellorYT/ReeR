</main>

<!-- Футер -->
<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <a href="<?= SITE_URL ?>" class="logo">
                    <div class="logo-icon"><i class="fas fa-cube"></i></div>
                    <span class="logo-text">Union<span class="logo-accent">Case</span></span>
                </a>
                <p>Открывай кейсы с товарами лучших маркетплейсов. Выигрывай ценные призы каждый день!</p>
                <div class="social-links">
                    <a href="#" class="social-link"><i class="fab fa-telegram"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-vk"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-discord"></i></a>
                </div>
            </div>
            <div class="footer-col">
                <h4>Маркетплейсы</h4>
                <ul>
                    <li><a href="<?= SITE_URL ?>/#steam"><i class="fab fa-steam"></i> Steam / CS2</a></li>
                    <li><a href="<?= SITE_URL ?>/#wildberries">🫐 Wildberries</a></li>
                    <li><a href="<?= SITE_URL ?>/#ozon">🔵 OZON</a></li>
                    <li><a href="<?= SITE_URL ?>/#aliexpress">🛒 AliExpress</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Навигация</h4>
                <ul>
                    <li><a href="<?= SITE_URL ?>">Главная</a></li>
                    <li><a href="<?= SITE_URL ?>/#cases">Все кейсы</a></li>
                    <?php if (isLoggedIn()): ?>
                    <li><a href="<?= SITE_URL ?>/profile.php">Профиль</a></li>
                    <li><a href="<?= SITE_URL ?>/profile.php?tab=inventory">Инвентарь</a></li>
                    <?php else: ?>
                    <li><a href="<?= SITE_URL ?>/login.php">Войти</a></li>
                    <li><a href="<?= SITE_URL ?>/register.php">Регистрация</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Поддержка</h4>
                <ul>
                    <li><a href="#">Правила сайта</a></li>
                    <li><a href="#">Политика конфиденциальности</a></li>
                    <li><a href="#">Контакты</a></li>
                    <li><a href="#">FAQ</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> <?= SITE_NAME ?>. Все права защищены.</p>
            <p class="footer-warning">⚠️ Сайт предназначен для развлечения. 18+</p>
        </div>
    </div>
</footer>

<script src="<?= SITE_URL ?>/assets/script.js"></script>
</body>
</html>
