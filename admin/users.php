<?php
require_once '../includes/functions.php';
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
        redirect('users.php');
    }

    if ($action === 'edit_balance') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);
        $type = $_POST['type'] ?? 'deposit';
        
        if ($amount != 0) {
            updateBalance($userId, $amount, $type, 'Администратор: ' . ($amount > 0 ? 'пополнение' : 'списание'));
            setFlash('success', 'Баланс пользователя изменен на ' . formatPrice($amount));
        }
        redirect('users.php');
    }

    if ($action === 'edit_role') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $role = $_POST['role'] ?? 'user';
        
        db()->execute("UPDATE users SET role = ? WHERE id = ?", [$role, $userId]);
        setFlash('success', 'Роль пользователя изменена');
        redirect('users.php');
    }

    if ($action === 'delete') {
        db()->execute("DELETE FROM users WHERE id = ?", [$id]);
        setFlash('success', 'Пользователь удален');
        redirect('users.php');
    }
}

$pageTitle = 'Управление пользователями';
include '../includes/header.php';

$users = db()->fetchAll("SELECT * FROM users ORDER BY id DESC");
?>

<div class="admin-page">
    <div class="container">
        <h1 class="page-title">Управление пользователями</h1>

        <div class="admin-table" style="background: var(--bg-card); border-radius: var(--radius-lg); overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: var(--bg-primary);">
                        <th style="padding: 15px;">ID</th>
                        <th style="padding: 15px;">Пользователь</th>
                        <th style="padding: 15px;">Email</th>
                        <th style="padding: 15px;">Баланс</th>
                        <th style="padding: 15px;">Роль</th>
                        <th style="padding: 15px;">Дата регистрации</th>
                        <th style="padding: 15px;">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td style="padding: 15px;"><?= $user['id'] ?></td>
                        <td style="padding: 15px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--accent-primary); display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                    <?php if ($user['avatar']): ?>
                                        <img src="<?= getImageUrl($user['avatar']) ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <i class="fas fa-user"></i>
                                    <?php endif; ?>
                                </div>
                                <?= e($user['username']) ?>
                            </div>
                        </td>
                        <td style="padding: 15px;"><?= e($user['email']) ?></td>
                        <td style="padding: 15px; color: var(--success); font-weight: 700;"><?= formatPrice($user['balance']) ?></td>
                        <td style="padding: 15px;">
                            <span style="background: <?= $user['role'] === 'admin' ? '#ffd700' : '#4b8bff' ?>20; color: <?= $user['role'] === 'admin' ? '#ffd700' : '#4b8bff' ?>; padding: 5px 10px; border-radius: 20px;">
                                <?= $user['role'] === 'admin' ? 'Админ' : 'Пользователь' ?>
                            </span>
                        </td>
                        <td style="padding: 15px;"><?= date('d.m.Y', strtotime($user['created_at'])) ?></td>
                        <td style="padding: 15px;">
                            <button onclick="showBalanceModal(<?= $user['id'] ?>, '<?= $user['username'] ?>')" class="btn btn-sm btn-outline" style="margin-right: 5px;" title="Изменить баланс">
                                <i class="fas fa-wallet"></i>
                            </button>
                            <button onclick="showRoleModal(<?= $user['id'] ?>, '<?= $user['username'] ?>', '<?= $user['role'] ?>')" class="btn btn-sm btn-outline" style="margin-right: 5px;" title="Изменить роль">
                                <i class="fas fa-user-tag"></i>
                            </button>
                            <a href="?action=delete&id=<?= $user['id'] ?>" class="btn btn-sm btn-outline" 
                               onclick="return confirm('Удалить пользователя?')" title="Удалить">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Модальное окно изменения баланса -->
<div class="modal-overlay" id="balance-modal" onclick="if(event.target===this) closeBalanceModal()">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-wallet"></i> Изменение баланса</h3>
            <button class="modal-close" onclick="closeBalanceModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <form method="POST" action="users.php?action=edit_balance">
                <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                <input type="hidden" name="user_id" id="balance-user-id">
                
                <p style="margin-bottom: 20px;">Пользователь: <strong id="balance-username"></strong></p>
                
                <div class="form-group">
                    <label>Сумма (положительная - пополнение, отрицательная - списание)</label>
                    <input type="number" name="amount" class="form-input" step="0.01" required>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary">Применить</button>
                    <button type="button" class="btn btn-outline" onclick="closeBalanceModal()">Отмена</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Модальное окно изменения роли -->
<div class="modal-overlay" id="role-modal" onclick="if(event.target===this) closeRoleModal()">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-user-tag"></i> Изменение роли</h3>
            <button class="modal-close" onclick="closeRoleModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <form method="POST" action="users.php?action=edit_role">
                <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                <input type="hidden" name="user_id" id="role-user-id">
                
                <p style="margin-bottom: 20px;">Пользователь: <strong id="role-username"></strong></p>
                
                <div class="form-group">
                    <label>Роль</label>
                    <select name="role" class="form-input" id="role-select">
                        <option value="user">Пользователь</option>
                        <option value="admin">Администратор</option>
                    </select>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                    <button type="button" class="btn btn-outline" onclick="closeRoleModal()">Отмена</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showBalanceModal(userId, username) {
    document.getElementById('balance-user-id').value = userId;
    document.getElementById('balance-username').textContent = username;
    document.getElementById('balance-modal').classList.add('active');
}

function closeBalanceModal() {
    document.getElementById('balance-modal').classList.remove('active');
}

function showRoleModal(userId, username, currentRole) {
    document.getElementById('role-user-id').value = userId;
    document.getElementById('role-username').textContent = username;
    document.getElementById('role-select').value = currentRole;
    document.getElementById('role-modal').classList.add('active');
}

function closeRoleModal() {
    document.getElementById('role-modal').classList.remove('active');
}
</script>

<?php include '../includes/footer.php'; ?>