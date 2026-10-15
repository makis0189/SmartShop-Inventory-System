<?php

require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';
require_role(['admin', 'manager']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to('products.php');
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    set_flash('error', 'Security validation failed. Refresh the page and try again.');
    redirect_to('products.php');
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$action = $_POST['action'] ?? '';

if (!$id || !in_array($action, ['activate', 'deactivate'], true)) {
    set_flash('error', 'Invalid product action.');
    redirect_to('products.php');
}

$stmt = $pdo->prepare('UPDATE products SET is_active = ? WHERE id = ?');
$stmt->execute([$action === 'activate' ? 1 : 0, $id]);

set_flash(
    'success',
    $stmt->rowCount() ? 'Product status updated.' : 'No changes were needed.'
);

redirect_to('products.php');
