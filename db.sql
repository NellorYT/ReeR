-- UnionCase Database Schema
-- Создание базы данных
DROP DATABASE IF EXISTS unioncase;
CREATE DATABASE IF NOT EXISTS unioncase CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE unioncase;

-- Таблица пользователей
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    balance DECIMAL(10,2) DEFAULT 0.00,
    role ENUM('user', 'admin') DEFAULT 'user',
    avatar VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Таблица категорий маркетплейсов
CREATE TABLE IF NOT EXISTS marketplaces (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    icon VARCHAR(255) DEFAULT NULL,
    color VARCHAR(20) DEFAULT '#ffffff',
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Таблица кейсов
CREATE TABLE IF NOT EXISTS cases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    marketplace_id INT DEFAULT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    image VARCHAR(255) DEFAULT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    opens_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (marketplace_id) REFERENCES marketplaces(id) ON DELETE SET NULL
);

-- Таблица товаров/предметов
CREATE TABLE IF NOT EXISTS items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    image VARCHAR(255) DEFAULT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    rarity ENUM('common', 'uncommon', 'rare', 'epic', 'legendary') DEFAULT 'common',
    color VARCHAR(20) DEFAULT '#b0b0b0',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Таблица связи кейсов и предметов (с вероятностями)
CREATE TABLE IF NOT EXISTS case_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL,
    item_id INT NOT NULL,
    chance DECIMAL(8,4) NOT NULL DEFAULT 1.0000,
    FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
    UNIQUE KEY unique_case_item (case_id, item_id)
);

-- Таблица открытий кейсов (история)
CREATE TABLE IF NOT EXISTS case_opens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    case_id INT NOT NULL,
    item_id INT NOT NULL,
    price_paid DECIMAL(10,2) NOT NULL,
    item_value DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
);

-- Таблица инвентаря пользователя
CREATE TABLE IF NOT EXISTS user_inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    item_id INT NOT NULL,
    case_open_id INT DEFAULT NULL,
    is_sold TINYINT(1) DEFAULT 0,
    sold_price DECIMAL(10,2) DEFAULT NULL,
    obtained_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
    FOREIGN KEY (case_open_id) REFERENCES case_opens(id) ON DELETE SET NULL
);

-- Таблица транзакций баланса
CREATE TABLE IF NOT EXISTS balance_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('deposit', 'withdraw', 'case_open', 'item_sell', 'bonus') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    balance_before DECIMAL(10,2) NOT NULL,
    balance_after DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Вставка тестовых маркетплейсов
INSERT INTO marketplaces (name, slug, color, sort_order) VALUES
('Steam / CS2', 'steam', '#1b2838', 1),
('Wildberries', 'wildberries', '#8b00ff', 2),
('OZON', 'ozon', '#005bff', 3),
('AliExpress', 'aliexpress', '#ff4747', 4),
('Amazon', 'amazon', '#ff9900', 5);

-- Вставка администратора (пароль: admin123)
INSERT INTO users (username, email, password, balance, role) VALUES
('admin', 'admin@unioncase.ru', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 9999.99, 'admin');

-- Тестовые предметы
INSERT INTO items (name, description, image, price, rarity, color) VALUES
('AK-47 | Redline', 'Легендарный скин для AK-47', NULL, 1500.00, 'legendary', '#ffd700'),
('AWP | Dragon Lore', 'Редчайший скин AWP', NULL, 15000.00, 'legendary', '#ffd700'),
('Нож-бабочка | Fade', 'Красивый нож с градиентом', NULL, 8000.00, 'epic', '#b24bff'),
('Glock-18 | Water Elemental', 'Водный элемент', NULL, 200.00, 'rare', '#4b8bff'),
('USP-S | Kill Confirmed', 'Уникальный пистолет', NULL, 800.00, 'rare', '#4b8bff'),
('MP5-SD | Acid Wash', 'Необычный MP5', NULL, 50.00, 'uncommon', '#4bff91'),
('P250 | Sand Dune', 'Обычный скин', NULL, 10.00, 'common', '#b0b0b0'),
('FAMAS | Commemoration', 'Памятный скин', NULL, 150.00, 'uncommon', '#4bff91'),
('Desert Eagle | Blaze', 'Огненный Eagle', NULL, 3000.00, 'epic', '#b24bff'),
('M4A4 | Howl', 'Запрещённый Howl', NULL, 12000.00, 'legendary', '#ffd700'),
('Наушники Sony WH-1000XM5', 'Топовые наушники с ANC', NULL, 25000.00, 'legendary', '#ffd700'),
('iPhone 15 Pro', 'Смартфон Apple', NULL, 90000.00, 'legendary', '#ffd700'),
('Airpods Pro 2', 'Беспроводные наушники Apple', NULL, 18000.00, 'epic', '#b24bff'),
('Механическая клавиатура HyperX', 'Игровая клавиатура', NULL, 5000.00, 'rare', '#4b8bff'),
('Игровая мышь Logitech G Pro', 'Профессиональная мышь', NULL, 3500.00, 'rare', '#4b8bff'),
('USB Flash 64GB', 'Флешка', NULL, 400.00, 'common', '#b0b0b0'),
('Коврик для мыши XL', 'Большой игровой коврик', NULL, 800.00, 'uncommon', '#4bff91'),
('Кружка UnionCase', 'Брендовая кружка', NULL, 500.00, 'common', '#b0b0b0');

-- Тестовые кейсы
INSERT INTO cases (marketplace_id, name, slug, description, price, sort_order) VALUES
(1, 'Кейс Оружия CS2', 'cs2-weapons', 'Открой и получи редкое оружие из CS2!', 299.00, 1),
(1, 'Нож-кейс', 'cs2-knives', 'Шанс получить легендарный нож!', 599.00, 2),
(2, 'Техно-бокс', 'tech-box', 'Гаджеты и электроника от Wildberries', 999.00, 3),
(3, 'Премиум Бокс', 'premium-box', 'Только премиум товары от OZON', 1499.00, 4);

-- Предметы в кейсах с вероятностями
-- Кейс оружия CS2 (case_id=1)
INSERT INTO case_items (case_id, item_id, chance) VALUES
(1, 1, 15.00),   -- AK-47 | Redline 15%
(1, 4, 25.00),   -- Glock-18 | Water Elemental 25%
(1, 5, 20.00),   -- USP-S | Kill Confirmed 20%
(1, 6, 20.00),   -- MP5-SD | Acid Wash 20%
(1, 7, 15.00),   -- P250 | Sand Dune 15%
(1, 8, 5.00);    -- FAMAS | Commemoration 5%

-- Нож-кейс (case_id=2)
INSERT INTO case_items (case_id, item_id, chance) VALUES
(2, 2, 1.00),    -- AWP | Dragon Lore 1%
(2, 3, 5.00),    -- Нож-бабочка | Fade 5%
(2, 9, 10.00),   -- Desert Eagle | Blaze 10%
(2, 10, 2.00),   -- M4A4 | Howl 2%
(2, 1, 20.00),   -- AK-47 | Redline 20%
(2, 5, 30.00),   -- USP-S | Kill Confirmed 30%
(2, 7, 32.00);   -- P250 | Sand Dune 32%

-- Техно-бокс (case_id=3)
INSERT INTO case_items (case_id, item_id, chance) VALUES
(3, 11, 5.00),   -- Наушники Sony 5%
(3, 12, 1.00),   -- iPhone 15 Pro 1%
(3, 13, 8.00),   -- Airpods Pro 2 8%
(3, 14, 15.00),  -- Клавиатура HyperX 15%
(3, 15, 20.00),  -- Мышь Logitech 20%
(3, 16, 25.00),  -- USB Flash 25%
(3, 17, 26.00);  -- Коврик 26%

-- Премиум бокс (case_id=4)
INSERT INTO case_items (case_id, item_id, chance) VALUES
(4, 12, 2.00),   -- iPhone 15 Pro 2%
(4, 11, 8.00),   -- Наушники Sony 8%
(4, 13, 15.00),  -- Airpods Pro 2 15%
(4, 14, 20.00),  -- Клавиатура HyperX 20%
(4, 15, 25.00),  -- Мышь Logitech 25%
(4, 18, 30.00);  -- Кружка 30%

-- Проверяем, что всё создалось
SELECT 'База данных успешно создана!' as message;