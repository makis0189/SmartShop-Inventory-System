<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';
require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to('user_management.php');
}
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    set_flash('error', 'Security validation failed. Refresh the page and try again.');
    redirect_to('user_management.php');
}

$userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
if (!$userId || $userId < 1) {
    set_flash('error', 'Invalid user account selected.');
    redirect_to('user_management.php');
}
if ((int) $userId === (int) current_user()['id']) {
    set_flash('error', 'You cannot deactivate the account you are currently using.');
    redirect_to('user_management.php');
}

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('SELECT id, role, is_active FROM users WHERE id = :id FOR UPDATE');
    $stmt->execute([':id' => $userId]);
    $target = $stmt->fetch();
    if (!$target) {
        $pdo->rollBack();
        set_flash('error', 'User account not found.');
        redirect_to('user_management.php');
    }

    $newStatus = (int) $target['is_active'] === 1 ? 0 : 1;
    if ($newStatus === 0 && $target['role'] === 'admin') {
        $adminRows = $pdo->query("SELECT id FROM users WHERE role = 'admin' AND is_active = 1 FOR UPDATE")->fetchAll();
        if (count($adminRows) <= 1) {
            $pdo->rollBack();
            set_flash('error', 'You cannot deactivate the last active administrator. Create or activate another administrator first.');
            redirect_to('user_management.php');
        }
    }

    $update = $pdo->prepare('UPDATE users SET is_active = :status WHERE id = :id');
    $update->execute([':status' => $newStatus, ':id' => $userId]);
    $pdo->commit();
    set_flash('success', $newStatus === 1 ? 'User account activated.' : 'User account deactivated.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('SmartShop user status update failed: ' . $e->getMessage());
    set_flash('error', 'The account status could not be changed. Please try again.');
}
redirect_to('user_management.php');
