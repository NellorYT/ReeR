<?php
require_once __DIR__ . '/../includes/functions.php';
startSession();

if (!isAdmin()) {
    setFlash('error', 'Доступ запрещен');
    redirect(SITE_URL);
}

$action = $_GET['action'] ?? 'list';
$id = (int)($_GET['id'] ?? 0);

// Получаем список тем из таблицы marketplaces
$themes = db()->fetchAll("SELECT * FROM marketplaces ORDER BY sort_order");

// Получаем список всех предметов
$allItems = db()->fetchAll("SELECT * FROM items WHERE is_active = 1 ORDER BY name");

// Обработка форм
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Неверный токен безопасности');
        redirect('cases.php');
    }

    // Добавление или редактирование кейса
    if ($_POST['form_action'] === 'save_case') {
        $name = trim($_POST['name'] ?? '');
        $theme_id = !empty($_POST['theme_id']) ? (int)$_POST['theme_id'] : null;
        $price = (float)($_POST['price'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $sort_order = (int)($_POST['sort_order'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $slug = generateSlug($name);
        
        // Загрузка изображения
        $image = null;
        if (!empty($_FILES['image']['name'])) {
            $uploaded = uploadImage($_FILES['image'], 'cases');
            if ($uploaded) $image = $uploaded;
        }

        if ($action === 'add') {
            db()->insert(
                "INSERT INTO cases (marketplace_id, name, slug, description, image, price, sort_order, is_active) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [$theme_id, $name, $slug, $description, $image, $price, $sort_order, $is_active]
            );
            setFlash('success', 'Кейс успешно создан');
        } else {
            if (!$image && isset($_POST['current_image'])) {
                $image = $_POST['current_image'];
            }
            db()->execute(
                "UPDATE cases SET marketplace_id = ?, name = ?, slug = ?, description = ?, image = ?, price = ?, sort_order = ?, is_active = ? WHERE id = ?",
                [$theme_id, $name, $slug, $description, $image, $price, $sort_order, $is_active, $id]
            );
            setFlash('success', 'Кейс успешно обновлен');
        }
        redirect('cases.php');
    }

    // Сохранение предметов в кейсе
    if ($_POST['form_action'] === 'save_items') {
        $case_id = (int)($_POST['case_id'] ?? 0);
        
        // Проверяем, что кейс существует
        $case = db()->fetch("SELECT * FROM cases WHERE id = ?", [$case_id]);
        if (!$case) {
            setFlash('error', 'Кейс не найден');
            redirect('cases.php');
        }
        
        // Удаляем старые связи
        db()->execute("DELETE FROM case_items WHERE case_id = ?", [$case_id]);
        
        // Добавляем новые
        $items = $_POST['items'] ?? [];
        $hasItems = false;
        $totalChance = 0;
        
        foreach ($items as $item_id => $data) {
            $chance = floatval($data['chance'] ?? 0);
            if ($chance > 0) {
                $hasItems = true;
                $totalChance += $chance;
                db()->insert(
                    "INSERT INTO case_items (case_id, item_id, chance) VALUES (?, ?, ?)",
                    [$case_id, $item_id, $chance]
                );
            }
        }
        
        if (!$hasItems) {
            setFlash('warning', 'Предупреждение: не добавлено ни одного предмета');
        } elseif (abs($totalChance - 100) > 0.01) {
            setFlash('warning', 'Внимание: сумма шансов (' . number_format($totalChance, 2) . '%) не равна 100%');
        } else {
            setFlash('success', 'Предметы в кейсе успешно обновлены');
        }
        
        redirect('cases.php?action=edit&id=' . $case_id);
    }

    // Удаление кейса
    if ($_POST['form_action'] === 'delete') {
        $case_id = (int)($_POST['case_id'] ?? 0);
        db()->execute("DELETE FROM cases WHERE id = ?", [$case_id]);
        setFlash('success', 'Кейс удален');
        redirect('cases.php');
    }
}

$pageTitle = 'Управление кейсами';
include '../includes/header.php';

// Получаем список всех кейсов для отображения
$cases = db()->fetchAll(
    "SELECT c.*, m.name as theme_name, m.color as theme_color 
     FROM cases c 
     LEFT JOIN marketplaces m ON c.marketplace_id = m.id 
     ORDER BY c.sort_order, c.id DESC"
);
?>

<div class="admin-page">
    <div class="container">
        <div class="admin-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h1 class="page-title">Управление кейсами</h1>
            <a href="?action=add" class="btn btn-primary">
                <i class="fas fa-plus"></i> Добавить кейс
            </a>
        </div>

        <?php if ($action === 'list'): ?>
            <!-- Список кейсов -->
            <div class="admin-table" style="background: var(--bg-card); border-radius: var(--radius-lg); overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: var(--bg-primary);">
                            <th style="padding: 15px;">ID</th>
                            <th style="padding: 15px;">Изображение</th>
                            <th style="padding: 15px;">Название</th>
                            <th style="padding: 15px;">Тема</th>
                            <th style="padding: 15px;">Цена</th>
                            <th style="padding: 15px;">Открытий</th>
                            <th style="padding: 15px;">Статус</th>
                            <th style="padding: 15px;">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cases as $c): ?>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 15px;"><?= $c['id'] ?></td>
                            <td style="padding: 15px;">
                                <?php if ($c['image']): ?>
                                    <img src="<?= getImageUrl($c['image']) ?>" alt="" style="width: 50px; height: 50px; object-fit: contain;">
                                <?php else: ?>
                                    <div style="width: 50px; height: 50px; background: <?= $c['theme_color'] ?? '#7b61ff' ?>20; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-box" style="color: <?= $c['theme_color'] ?? '#7b61ff' ?>; font-size: 24px;"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 15px; font-weight: 600;"><?= e($c['name']) ?></td>
                            <td style="padding: 15px;">
                                <?php if ($c['theme_name']): ?>
                                    <span style="background: <?= $c['theme_color'] ?>20; color: <?= $c['theme_color'] ?>; padding: 5px 10px; border-radius: 20px;">
                                        <?= e($c['theme_name']) ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: var(--text-secondary);">—</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 15px; color: var(--success); font-weight: 700;"><?= formatPrice($c['price']) ?></td>
                            <td style="padding: 15px;"><?= number_format($c['opens_count']) ?></td>
                            <td style="padding: 15px;">
                                <span style="background: <?= $c['is_active'] ? '#4bff91' : '#ff4b4b' ?>20; color: <?= $c['is_active'] ? '#4bff91' : '#ff4b4b' ?>; padding: 5px 10px; border-radius: 20px;">
                                    <?= $c['is_active'] ? 'Активен' : 'Неактивен' ?>
                                </span>
                            </td>
                            <td style="padding: 15px;">
                                <a href="?action=edit&id=<?= $c['id'] ?>" class="btn btn-sm btn-outline" style="margin-right: 5px;">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Удалить кейс? Все связанные данные будут удалены.')">
                                    <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                                    <input type="hidden" name="form_action" value="delete">
                                    <input type="hidden" name="case_id" value="<?= $c['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($action === 'add' || $action === 'edit'): ?>
            <?php
            $case = null;
            $case_items = [];
            $case_items_index = [];
            
            if ($action === 'edit' && $id) {
                $case = db()->fetch("SELECT * FROM cases WHERE id = ?", [$id]);
                if (!$case) {
                    setFlash('error', 'Кейс не найден');
                    redirect('cases.php');
                }
                $case_items = db()->fetchAll(
                    "SELECT * FROM case_items WHERE case_id = ?",
                    [$id]
                );
                foreach ($case_items as $ci) {
                    $case_items_index[$ci['item_id']] = $ci;
                }
            }
            ?>
            
            <!-- Форма добавления/редактирования кейса -->
            <form method="POST" enctype="multipart/form-data" style="background: var(--bg-card); padding: 30px; border-radius: var(--radius-lg); margin-bottom: 30px;">
                <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                <input type="hidden" name="form_action" value="save_case">
                
                <h2 style="margin-bottom: 20px;"><?= $action === 'add' ? 'Создание нового кейса' : 'Редактирование кейса' ?></h2>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div class="form-group">
                        <label>Название кейса</label>
                        <input type="text" name="name" class="form-input" value="<?= e($case['name'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Тема кейса</label>
                        <select name="theme_id" class="form-input">
                            <option value="">Без темы</option>
                            <?php foreach ($themes as $theme): ?>
                            <option value="<?= $theme['id'] ?>" <?= ($case['marketplace_id'] ?? 0) == $theme['id'] ? 'selected' : '' ?>>
                                <?= e($theme['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Описание</label>
                    <textarea name="description" class="form-input" rows="3"><?= e($case['description'] ?? '') ?></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div class="form-group">
                        <label>Цена (₽)</label>
                        <input type="number" name="price" class="form-input" step="0.01" value="<?= $case['price'] ?? 0 ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Порядок сортировки</label>
                        <input type="number" name="sort_order" class="form-input" value="<?= $case['sort_order'] ?? 0 ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Изображение кейса</label>
                    <?php if ($action === 'edit' && $case['image']): ?>
                        <div style="margin-bottom: 10px;">
                            <img src="<?= getImageUrl($case['image']) ?>" alt="" style="max-width: 100px; max-height: 100px; border-radius: 10px; border: 2px solid var(--accent-primary);">
                        </div>
                        <input type="hidden" name="current_image" value="<?= $case['image'] ?>">
                    <?php endif; ?>
                    <input type="file" name="image" accept="image/*" class="form-input">
                    <small style="color: var(--text-secondary);">Рекомендуемый размер: 200x200px</small>
                </div>

                <div class="form-check" style="margin-bottom: 20px;">
                    <input type="checkbox" name="is_active" id="is_active" <?= !isset($case) || $case['is_active'] ? 'checked' : '' ?>>
                    <label for="is_active">Кейс активен (отображается на сайте)</label>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary">
                        <?= $action === 'add' ? 'Создать кейс' : 'Сохранить изменения' ?>
                    </button>
                    <a href="cases.php" class="btn btn-outline">Отмена</a>
                </div>
            </form>

            <?php if ($action === 'edit'): ?>
                <!-- Форма добавления предметов в кейс -->
                <div style="background: var(--bg-card); padding: 30px; border-radius: var(--radius-lg);">
                    <h2 style="margin-bottom: 20px;">Предметы в кейсе</h2>
                    <p style="color: var(--text-secondary); margin-bottom: 20px;">
                        Укажите шанс выпадения для каждого предмета (в процентах). Сумма всех шансов должна быть 100%.
                    </p>
                    
                    <form method="POST" id="items-form">
                        <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                        <input type="hidden" name="form_action" value="save_items">
                        <input type="hidden" name="case_id" value="<?= $id ?>">
                        
                        <div style="max-height: 600px; overflow-y: auto; border: 1px solid var(--border); border-radius: var(--radius-md); padding: 20px; margin-bottom: 20px; background: var(--bg-primary);">
                            <?php 
                            $totalChanceDisplay = 0;
                            foreach ($allItems as $item): 
                                $checked = isset($case_items_index[$item['id']]);
                                $chance = $case_items_index[$item['id']]['chance'] ?? 0;
                                if ($checked) $totalChanceDisplay += $chance;
                            ?>
                            <div style="display: flex; align-items: center; gap: 15px; padding: 12px; border-bottom: 1px solid var(--border); background: <?= $checked ? 'rgba(123, 97, 255, 0.05)' : 'transparent' ?>; transition: background 0.3s ease;">
                                <input type="checkbox" 
                                       id="item_<?= $item['id'] ?>" 
                                       onchange="toggleItem(<?= $item['id'] ?>)"
                                       <?= $checked ? 'checked' : '' ?>
                                       style="width: 20px; height: 20px; cursor: pointer;">
                                       
                                <div style="width: 60px; height: 60px; background: <?= $item['color'] ?>20; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                    <?php if ($item['image']): ?>
                                        <img src="<?= getImageUrl($item['image']) ?>" alt="" style="max-width: 50px; max-height: 50px; object-fit: contain;">
                                    <?php else: ?>
                                        <i class="fas fa-gift" style="color: <?= $item['color'] ?>; font-size: 30px;"></i>
                                    <?php endif; ?>
                                </div>
                                
                                <div style="flex: 1;">
                                    <div style="font-weight: 700; font-size: 16px; margin-bottom: 5px;"><?= e($item['name']) ?></div>
                                    <div style="display: flex; gap: 15px; font-size: 13px;">
                                        <span style="color: var(--success); font-weight: 600;"><?= formatPrice($item['price']) ?></span>
                                        <span style="color: <?= $item['color'] ?>; background: <?= $item['color'] ?>20; padding: 2px 8px; border-radius: 12px;">
                                            <?= getRarityName($item['rarity']) ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <input type="number" 
                                           name="items[<?= $item['id'] ?>][chance]" 
                                           id="chance_<?= $item['id'] ?>"
                                           class="form-input"
                                           step="0.01"
                                           min="0"
                                           max="100"
                                           value="<?= $chance ?>"
                                           style="width: 100px; text-align: center; font-weight: 600;"
                                           onchange="updateTotalChance()"
                                           <?= !$checked ? 'disabled' : '' ?>>
                                    <span style="color: var(--text-secondary); width: 20px; font-weight: 600;">%</span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div style="background: linear-gradient(135deg, var(--bg-card), var(--bg-primary)); padding: 20px; border-radius: var(--radius-md); margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; border: 1px solid var(--border);">
                            <div style="display: flex; align-items: center; gap: 20px;">
                                <strong style="font-size: 18px;">Сумма шансов:</strong>
                                <span id="total-chance" style="font-size: 32px; font-weight: 900; <?= abs($totalChanceDisplay - 100) < 0.01 ? 'color: var(--success);' : 'color: var(--warning);' ?>">
                                    <?= number_format($totalChanceDisplay, 2) ?>%
                                </span>
                            </div>
                            <div style="color: var(--text-secondary); font-size: 14px; background: rgba(0,0,0,0.3); padding: 8px 15px; border-radius: 30px;">
                                <i class="fas fa-info-circle"></i> Должно быть 100%
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                            <button type="submit" class="btn btn-primary" style="padding: 15px 30px; font-size: 16px;">
                                <i class="fas fa-save"></i> Сохранить предметы
                            </button>
                            <button type="button" class="btn btn-outline" onclick="selectAllItems()">
                                <i class="fas fa-check-square"></i> Выбрать все
                            </button>
                            <button type="button" class="btn btn-outline" onclick="deselectAllItems()">
                                <i class="fas fa-square"></i> Снять все
                            </button>
                            <button type="button" class="btn btn-outline" onclick="distributeEvenly()">
                                <i class="fas fa-equals"></i> Распределить равномерно
                            </button>
                        </div>
                    </form>
                </div>

                <script>
                // Функция для включения/выключения предмета
                function toggleItem(itemId) {
                    const checkbox = document.getElementById('item_' + itemId);
                    const chanceInput = document.getElementById('chance_' + itemId);
                    const itemDiv = checkbox.closest('div[style*="display: flex"]');
                    
                    chanceInput.disabled = !checkbox.checked;
                    
                    if (checkbox.checked && !chanceInput.value) {
                        chanceInput.value = '0';
                    }
                    
                    if (checkbox.checked) {
                        itemDiv.style.background = 'rgba(123, 97, 255, 0.05)';
                    } else {
                        itemDiv.style.background = 'transparent';
                        chanceInput.value = '0';
                    }
                    
                    updateTotalChance();
                }
                
                // Функция для обновления общей суммы шансов
                function updateTotalChance() {
                    let total = 0;
                    const items = document.querySelectorAll('[id^="chance_"]');
                    items.forEach(input => {
                        if (!input.disabled) {
                            total += parseFloat(input.value) || 0;
                        }
                    });
                    
                    const totalSpan = document.getElementById('total-chance');
                    totalSpan.textContent = total.toFixed(2) + '%';
                    
                    if (Math.abs(total - 100) < 0.01) {
                        totalSpan.style.color = 'var(--success)';
                    } else {
                        totalSpan.style.color = 'var(--warning)';
                    }
                }
                
                // Функция для выбора всех предметов
                function selectAllItems() {
                    const checkboxes = document.querySelectorAll('[id^="item_"]');
                    checkboxes.forEach(checkbox => {
                        checkbox.checked = true;
                        const itemId = checkbox.id.replace('item_', '');
                        const chanceInput = document.getElementById('chance_' + itemId);
                        chanceInput.disabled = false;
                        if (!chanceInput.value) {
                            chanceInput.value = '0';
                        }
                        checkbox.closest('div[style*="display: flex"]').style.background = 'rgba(123, 97, 255, 0.05)';
                    });
                    updateTotalChance();
                }
                
                // Функция для снятия всех предметов
                function deselectAllItems() {
                    const checkboxes = document.querySelectorAll('[id^="item_"]');
                    checkboxes.forEach(checkbox => {
                        checkbox.checked = false;
                        const itemId = checkbox.id.replace('item_', '');
                        const chanceInput = document.getElementById('chance_' + itemId);
                        chanceInput.disabled = true;
                        chanceInput.value = '0';
                        checkbox.closest('div[style*="display: flex"]').style.background = 'transparent';
                    });
                    updateTotalChance();
                }
                
                // Функция для равномерного распределения шансов
                function distributeEvenly() {
                    const checkboxes = document.querySelectorAll('[id^="item_"]:checked');
                    if (checkboxes.length === 0) {
                        alert('Сначала выберите предметы');
                        return;
                    }
                    
                    const chancePerItem = (100 / checkboxes.length).toFixed(2);
                    
                    checkboxes.forEach(checkbox => {
                        const itemId = checkbox.id.replace('item_', '');
                        const chanceInput = document.getElementById('chance_' + itemId);
                        chanceInput.value = chancePerItem;
                    });
                    
                    updateTotalChance();
                }
                
                // Инициализация при загрузке
                document.addEventListener('DOMContentLoaded', function() {
                    updateTotalChance();
                });
                </script>
            <?php endif; ?>
        <?php endif; ?>
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