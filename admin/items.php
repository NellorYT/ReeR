<?php
require_once __DIR__ . '/../includes/functions.php';
startSession();

if (!isAdmin()) {
    setFlash('error', 'Доступ запрещен');
    redirect(SITE_URL);
}

$action = $_GET['action'] ?? 'list';
$id = (int)($_GET['id'] ?? 0);

// Получаем список всех предметов
$items = db()->fetchAll("SELECT * FROM items ORDER BY id DESC");

// Массив с названиями редкости
$rarityNames = [
    'common' => 'Обычный',
    'uncommon' => 'Необычный',
    'rare' => 'Редкий',
    'epic' => 'Эпический',
    'legendary' => 'Легендарный'
];

// Обработка форм
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Неверный токен безопасности');
        redirect('items.php');
    }

    // Добавление предмета
    if ($_POST['form_action'] === 'add') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $rarity = $_POST['rarity'] ?? 'common';
        $color = $_POST['color'] ?? '';
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Если цвет не указан, используем цвет по умолчанию для редкости
        if (empty($color)) {
            $colors = [
                'common' => '#b0b0b0',
                'uncommon' => '#4bff91',
                'rare' => '#4b8bff',
                'epic' => '#b24bff',
                'legendary' => '#ffd700'
            ];
            $color = $colors[$rarity] ?? '#b0b0b0';
        }
        
        // Загрузка изображения
        $image = null;
        if (!empty($_FILES['image']['name'])) {
            $uploaded = uploadImage($_FILES['image'], 'items');
            if ($uploaded) $image = $uploaded;
        }

        db()->insert(
            "INSERT INTO items (name, description, image, price, rarity, color, is_active) 
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$name, $description, $image, $price, $rarity, $color, $is_active]
        );
        setFlash('success', 'Предмет успешно создан');
        redirect('items.php');
    }

    // Редактирование предмета
    if ($_POST['form_action'] === 'edit') {
        $item_id = (int)($_POST['item_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $rarity = $_POST['rarity'] ?? 'common';
        $color = $_POST['color'] ?? '';
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Если цвет не указан, используем цвет по умолчанию для редкости
        if (empty($color)) {
            $colors = [
                'common' => '#b0b0b0',
                'uncommon' => '#4bff91',
                'rare' => '#4b8bff',
                'epic' => '#b24bff',
                'legendary' => '#ffd700'
            ];
            $color = $colors[$rarity] ?? '#b0b0b0';
        }
        
        // Загрузка изображения
        $image = $_POST['current_image'] ?? null;
        if (!empty($_FILES['image']['name'])) {
            $uploaded = uploadImage($_FILES['image'], 'items');
            if ($uploaded) $image = $uploaded;
        }

        db()->execute(
            "UPDATE items SET name = ?, description = ?, image = ?, price = ?, rarity = ?, color = ?, is_active = ? WHERE id = ?",
            [$name, $description, $image, $price, $rarity, $color, $is_active, $item_id]
        );
        setFlash('success', 'Предмет успешно обновлен');
        redirect('items.php');
    }

    // Удаление предмета
    if ($_POST['form_action'] === 'delete') {
        $item_id = (int)($_POST['item_id'] ?? 0);
        db()->execute("DELETE FROM items WHERE id = ?", [$item_id]);
        setFlash('success', 'Предмет удален');
        redirect('items.php');
    }
}

$pageTitle = 'Управление предметами';
include '../includes/header.php';
?>

<div class="admin-page">
    <div class="container">
        <div class="admin-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h1 class="page-title">Управление предметами</h1>
            <a href="?action=add" class="btn btn-primary">
                <i class="fas fa-plus"></i> Добавить предмет
            </a>
        </div>

        <?php if ($action === 'list'): ?>
            <!-- Список предметов -->
            <div class="admin-table" style="background: var(--bg-card); border-radius: var(--radius-lg); overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: var(--bg-primary);">
                            <th style="padding: 15px; width: 50px;">#</th>
                            <th style="padding: 15px;">ID</th>
                            <th style="padding: 15px;">Изображение</th>
                            <th style="padding: 15px;">Название</th>
                            <th style="padding: 15px;">Цена</th>
                            <th style="padding: 15px;">Редкость</th>
                            <th style="padding: 15px;">Статус</th>
                            <th style="padding: 15px;">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $counter = 1;
                        foreach ($items as $item): 
                        ?>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 15px; font-weight: 700; color: var(--accent-primary);"><?= $counter++ ?></td>
                            <td style="padding: 15px; color: var(--text-secondary);">#<?= $item['id'] ?></td>
                            <td style="padding: 15px;">
                                <?php if ($item['image']): ?>
                                    <img src="<?= getImageUrl($item['image']) ?>" alt="" style="width: 50px; height: 50px; object-fit: contain; border-radius: 8px;">
                                <?php else: ?>
                                    <div style="width: 50px; height: 50px; background: <?= $item['color'] ?>20; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-gift" style="font-size: 24px; color: <?= $item['color'] ?>"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 15px; font-weight: 600;"><?= e($item['name']) ?></td>
                            <td style="padding: 15px; color: var(--success); font-weight: 700;"><?= formatPrice($item['price']) ?></td>
                            <td style="padding: 15px;">
                                <span style="background: <?= $item['color'] ?>20; color: <?= $item['color'] ?>; padding: 5px 10px; border-radius: 20px; font-weight: 600;">
                                    <?= $rarityNames[$item['rarity']] ?? 'Обычный' ?>
                                </span>
                            </td>
                            <td style="padding: 15px;">
                                <span style="background: <?= $item['is_active'] ? '#4bff91' : '#ff4b4b' ?>20; color: <?= $item['is_active'] ? '#4bff91' : '#ff4b4b' ?>; padding: 5px 10px; border-radius: 20px;">
                                    <?= $item['is_active'] ? 'Активен' : 'Неактивен' ?>
                                </span>
                            </td>
                            <td style="padding: 15px;">
                                <a href="?action=edit&id=<?= $item['id'] ?>" class="btn btn-sm btn-outline" style="margin-right: 5px;">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Удалить предмет? Он будет удален из всех кейсов.')">
                                    <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                                    <input type="hidden" name="form_action" value="delete">
                                    <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="8" style="padding: 50px; text-align: center; color: var(--text-secondary);">
                                <i class="fas fa-box-open" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
                                <h3>Нет предметов</h3>
                                <p>Создайте первый предмет, нажав кнопку "Добавить предмет"</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($action === 'add'): ?>
            <!-- Форма добавления предмета -->
            <div style="background: var(--bg-card); padding: 30px; border-radius: var(--radius-lg); max-width: 600px; margin: 0 auto;">
                <h2 style="margin-bottom: 20px;">Добавление нового предмета</h2>
                
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                    <input type="hidden" name="form_action" value="add">
                    
                    <div class="form-group">
                        <label>Название предмета</label>
                        <input type="text" name="name" class="form-input" required placeholder="Например: AK-47 | Redline">
                    </div>
                    
                    <div class="form-group">
                        <label>Описание (необязательно)</label>
                        <textarea name="description" class="form-input" rows="3" placeholder="Краткое описание предмета"></textarea>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>Цена (₽)</label>
                            <input type="number" name="price" class="form-input" step="0.01" min="0" value="0" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Редкость</label>
                            <select name="rarity" class="form-input" id="rarity-select" onchange="updateColorFromRarity()">
                                <option value="common">Обычный</option>
                                <option value="uncommon">Необычный</option>
                                <option value="rare">Редкий</option>
                                <option value="epic">Эпический</option>
                                <option value="legendary">Легендарный</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Цвет (HEX)</label>
                        <div style="display: flex; gap: 10px;">
                            <input type="color" name="color" id="color-picker" class="form-input" style="width: 80px; height: 50px;" value="#b0b0b0">
                            <input type="text" id="color-text" class="form-input" style="flex: 1;" placeholder="#b0b0b0" value="#b0b0b0" onchange="document.getElementById('color-picker').value = this.value">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Изображение предмета</label>
                        <input type="file" name="image" accept="image/*" class="form-input">
                        <small style="color: var(--text-secondary);">Рекомендуемый размер: 200x200px</small>
                    </div>
                    
                    <div class="form-check" style="margin-bottom: 20px;">
                        <input type="checkbox" name="is_active" id="is_active" checked>
                        <label for="is_active">Предмет активен</label>
                    </div>
                    
                    <div style="display: flex; gap: 10px;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Создать предмет
                        </button>
                        <a href="items.php" class="btn btn-outline">Отмена</a>
                    </div>
                </form>
            </div>

        <?php elseif ($action === 'edit'): ?>
            <?php
            $item = db()->fetch("SELECT * FROM items WHERE id = ?", [$id]);
            if (!$item) {
                setFlash('error', 'Предмет не найден');
                redirect('items.php');
            }
            ?>
            
            <!-- Форма редактирования предмета -->
            <div style="background: var(--bg-card); padding: 30px; border-radius: var(--radius-lg); max-width: 600px; margin: 0 auto;">
                <h2 style="margin-bottom: 20px;">Редактирование предмета</h2>
                
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                    <input type="hidden" name="form_action" value="edit">
                    <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                    <input type="hidden" name="current_image" value="<?= $item['image'] ?>">
                    
                    <div class="form-group">
                        <label>Название предмета</label>
                        <input type="text" name="name" class="form-input" value="<?= e($item['name']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Описание</label>
                        <textarea name="description" class="form-input" rows="3"><?= e($item['description'] ?? '') ?></textarea>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>Цена (₽)</label>
                            <input type="number" name="price" class="form-input" step="0.01" min="0" value="<?= $item['price'] ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Редкость</label>
                            <select name="rarity" class="form-input" id="rarity-select" onchange="updateColorFromRarity()">
                                <option value="common" <?= $item['rarity'] == 'common' ? 'selected' : '' ?>>Обычный</option>
                                <option value="uncommon" <?= $item['rarity'] == 'uncommon' ? 'selected' : '' ?>>Необычный</option>
                                <option value="rare" <?= $item['rarity'] == 'rare' ? 'selected' : '' ?>>Редкий</option>
                                <option value="epic" <?= $item['rarity'] == 'epic' ? 'selected' : '' ?>>Эпический</option>
                                <option value="legendary" <?= $item['rarity'] == 'legendary' ? 'selected' : '' ?>>Легендарный</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Цвет (HEX)</label>
                        <div style="display: flex; gap: 10px;">
                            <input type="color" name="color" id="color-picker" class="form-input" style="width: 80px; height: 50px;" value="<?= $item['color'] ?>">
                            <input type="text" id="color-text" class="form-input" style="flex: 1;" value="<?= $item['color'] ?>" onchange="document.getElementById('color-picker').value = this.value">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Изображение предмета</label>
                        <?php if ($item['image']): ?>
                            <div style="margin-bottom: 10px;">
                                <img src="<?= getImageUrl($item['image']) ?>" alt="" style="max-width: 100px; max-height: 100px; border-radius: 10px; border: 2px solid var(--accent-primary);">
                            </div>
                        <?php endif; ?>
                        <input type="file" name="image" accept="image/*" class="form-input">
                        <small style="color: var(--text-secondary);">Оставьте пустым, чтобы не менять изображение</small>
                    </div>
                    
                    <div class="form-check" style="margin-bottom: 20px;">
                        <input type="checkbox" name="is_active" id="is_active" <?= $item['is_active'] ? 'checked' : '' ?>>
                        <label for="is_active">Предмет активен</label>
                    </div>
                    
                    <div style="display: flex; gap: 10px;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Сохранить изменения
                        </button>
                        <a href="items.php" class="btn btn-outline">Отмена</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function updateColorFromRarity() {
    const rarity = document.getElementById('rarity-select').value;
    const colors = {
        'common': '#b0b0b0',
        'uncommon': '#4bff91',
        'rare': '#4b8bff',
        'epic': '#b24bff',
        'legendary': '#ffd700'
    };
    const colorPicker = document.getElementById('color-picker');
    const colorText = document.getElementById('color-text');
    colorPicker.value = colors[rarity];
    colorText.value = colors[rarity];
}

// Синхронизация color picker и текстового поля
document.addEventListener('DOMContentLoaded', function() {
    const colorPicker = document.getElementById('color-picker');
    const colorText = document.getElementById('color-text');
    
    if (colorPicker && colorText) {
        colorPicker.addEventListener('input', function() {
            colorText.value = this.value;
        });
    }
});
</script>

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
    background-clip: text;
}

.admin-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.admin-table {
    background: var(--bg-card);
    border-radius: var(--radius-lg);
    overflow-x: auto;
    border: 1px solid var(--border);
}

.admin-table table {
    width: 100%;
    border-collapse: collapse;
}

.admin-table th {
    padding: 15px;
    text-align: left;
    background: var(--bg-primary);
    color: var(--text-secondary);
    font-weight: 600;
    font-size: 14px;
    border-bottom: 1px solid var(--border);
}

.admin-table td {
    padding: 15px;
    border-bottom: 1px solid var(--border);
}

.admin-table tr:last-child td {
    border-bottom: none;
}

.admin-table tr:hover {
    background: rgba(255, 255, 255, 0.02);
}

.btn-sm {
    padding: 8px 12px;
    font-size: 14px;
}

.btn-outline {
    background: transparent;
    border: 1px solid var(--border);
    color: var(--text-secondary);
}

.btn-outline:hover {
    border-color: var(--accent-primary);
    color: white;
    background: rgba(123, 97, 255, 0.1);
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: var(--text-secondary);
    font-weight: 500;
}

.form-input {
    width: 100%;
    padding: 12px 15px;
    background: var(--bg-primary);
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    color: white;
    font-size: 15px;
    transition: all 0.3s ease;
}

.form-input:focus {
    outline: none;
    border-color: var(--accent-primary);
    box-shadow: 0 0 0 3px rgba(123, 97, 255, 0.2);
}

.form-check {
    display: flex;
    align-items: center;
    gap: 10px;
}

.form-check input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
    accent-color: var(--accent-primary);
}
</style>

<?php include '../includes/footer.php'; ?>