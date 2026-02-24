<?php
require_once __DIR__ . '/../includes/functions.php';
startSession();

if (!isAdmin()) {
    setFlash('error', 'Доступ запрещен');
    redirect(SITE_URL);
}

$action = $_GET['action'] ?? 'list';
$id = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Неверный токен безопасности');
        redirect('marketplace.php');
    }

    if ($action === 'add' || $action === 'edit') {
        $name = trim($_POST['name'] ?? '');
        $color = $_POST['color'] ?? '#7b61ff';
        $sort_order = (int)($_POST['sort_order'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $slug = generateSlug($name);
        
        if ($action === 'add') {
            db()->insert(
                "INSERT INTO marketplaces (name, slug, color, sort_order, is_active) VALUES (?, ?, ?, ?, ?)",
                [$name, $slug, $color, $sort_order, $is_active]
            );
            setFlash('success', 'Маркетплейс успешно создан');
        } else {
            db()->execute(
                "UPDATE marketplaces SET name = ?, slug = ?, color = ?, sort_order = ?, is_active = ? WHERE id = ?",
                [$name, $slug, $color, $sort_order, $is_active, $id]
            );
            setFlash('success', 'Маркетплейс успешно обновлен');
        }
        redirect('marketplace.php');
    }

    if ($action === 'delete') {
        db()->execute("DELETE FROM marketplaces WHERE id = ?", [$id]);
        setFlash('success', 'Маркетплейс удален');
        redirect('marketplace.php');
    }
}

$pageTitle = 'Управление маркетплейсами';
include '../includes/header.php';

$marketplaces = db()->fetchAll("SELECT * FROM marketplaces ORDER BY sort_order");
?>

<div class="admin-page">
    <div class="container">
        <div class="admin-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h1 class="page-title">Управление маркетплейсами</h1>
            <a href="?action=add" class="btn btn-primary">
                <i class="fas fa-plus"></i> Добавить маркетплейс
            </a>
        </div>

        <?php if ($action === 'list'): ?>
            <div class="admin-table" style="background: var(--bg-card); border-radius: var(--radius-lg); overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: var(--bg-primary);">
                            <th style="padding: 15px;">ID</th>
                            <th style="padding: 15px;">Название</th>
                            <th style="padding: 15px;">Цвет</th>
                            <th style="padding: 15px;">Сортировка</th>
                            <th style="padding: 15px;">Статус</th>
                            <th style="padding: 15px;">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($marketplaces as $mp): ?>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 15px;"><?= $mp['id'] ?></td>
                            <td style="padding: 15px;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="width: 30px; height: 30px; background: <?= $mp['color'] ?>; border-radius: 8px;"></div>
                                    <?= e($mp['name']) ?>
                                </div>
                            </td>
                            <td style="padding: 15px;"><?= $mp['color'] ?></td>
                            <td style="padding: 15px;"><?= $mp['sort_order'] ?></td>
                            <td style="padding: 15px;">
                                <span style="background: <?= $mp['is_active'] ? '#4bff91' : '#ff4b4b' ?>20; color: <?= $mp['is_active'] ? '#4bff91' : '#ff4b4b' ?>; padding: 5px 10px; border-radius: 20px;">
                                    <?= $mp['is_active'] ? 'Активен' : 'Неактивен' ?>
                                </span>
                            </td>
                            <td style="padding: 15px;">
                                <a href="?action=edit&id=<?= $mp['id'] ?>" class="btn btn-sm btn-outline" style="margin-right: 5px;">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="?action=delete&id=<?= $mp['id'] ?>" class="btn btn-sm btn-outline" 
                                   onclick="return confirm('Удалить маркетплейс?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($action === 'add' || $action === 'edit'): ?>
            <?php
            $mp = null;
            if ($action === 'edit' && $id) {
                $mp = db()->fetch("SELECT * FROM marketplaces WHERE id = ?", [$id]);
                if (!$mp) {
                    setFlash('error', 'Маркетплейс не найден');
                    redirect('marketplace.php');
                }
            }
            ?>
            
            <form method="POST" style="background: var(--bg-card); padding: 30px; border-radius: var(--radius-lg); max-width: 500px; margin: 0 auto;">
                <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                
                <div class="form-group">
                    <label>Название маркетплейса</label>
                    <input type="text" name="name" class="form-input" value="<?= e($mp['name'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label>Цвет</label>
                    <input type="color" name="color" class="form-input" style="height: 50px;" value="<?= $mp['color'] ?? '#7b61ff' ?>">
                </div>

                <div class="form-group">
                    <label>Порядок сортировки</label>
                    <input type="number" name="sort_order" class="form-input" value="<?= $mp['sort_order'] ?? 0 ?>">
                </div>

                <div class="form-check" style="margin-bottom: 20px;">
                    <input type="checkbox" name="is_active" id="is_active" <?= !isset($mp) || $mp['is_active'] ? 'checked' : '' ?>>
                    <label for="is_active">Маркетплейс активен</label>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary">
                        <?= $action === 'add' ? 'Создать' : 'Сохранить' ?>
                    </button>
                    <a href="marketplace.php" class="btn btn-outline">Отмена</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>