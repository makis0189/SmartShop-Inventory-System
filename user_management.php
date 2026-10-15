<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';
require_role(['admin']);

$users = $pdo->query(
    'SELECT id, full_name, username, email, role, is_active, created_at
     FROM users
     ORDER BY is_active DESC, full_name ASC'
)->fetchAll();
$flash = get_flash();
$lowCount = (int) $pdo->query(
    'SELECT COUNT(*) FROM products WHERE is_active = 1 AND stock_quantity <= minimum_stock'
)->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Users | SmartShop</title>
    <link rel="stylesheet" href="Assets/css/style.css">
    <link rel="stylesheet" href="Assets/css/inventory.css">
    <link rel="stylesheet" href="Assets/css/users.css">
  <link rel="stylesheet" href="Assets/css/enterprise-overrides.css">
</head>
<body class="inventory-page">
<header class="inv-topbar">
    <a class="inv-brand" href="dashboard.php">SmartShop <span>Inventory</span></a>
    <?php require __DIR__ . '/includes/nav.php'; ?>
</header>
<main class="inv-container">
    <div class="inv-heading">
        <div>
            <p class="eyebrow">ACCESS CONTROL</p>
            <h1>Manage Users</h1>
            <p class="muted">Create staff accounts and control access to SmartShop.</p>
        </div>
    </div>

    <?php if ($flash): ?>
        <div class="alert <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <div class="user-grid">
        <section class="user-panel">
            <h2 class="section-heading">Create account</h2>
            <p class="muted user-form-intro">Use a unique username and a strong password. Passwords are stored as hashes.</p>
            <form class="user-form" action="save_user.php" method="post">
                <?= csrf_input() ?>
                <label for="full_name">Full name
                    <input id="full_name" name="full_name" maxlength="120" required autocomplete="name">
                </label>
                <label for="username">Username
                    <input id="username" name="username" maxlength="60" pattern="[A-Za-z0-9_.-]{3,60}" required autocomplete="off" title="Use 3-60 letters, numbers, dots, underscores or hyphens.">
                </label>
                <label for="email">Email (optional)
                    <input id="email" name="email" type="email" maxlength="150" autocomplete="email">
                </label>
                <label for="role">Role
                    <select id="role" name="role" required>
                        <option value="cashier">Cashier — sales and receipts</option>
                        <option value="manager">Manager — products, sales and reports</option>
                        <option value="admin">Admin — full system access</option>
                    </select>
                </label>
                <label for="password">Temporary password
                    <input id="password" name="password" type="password" minlength="12" maxlength="200" required autocomplete="new-password">
                </label>
                <label for="confirm_password">Confirm password
                    <input id="confirm_password" name="confirm_password" type="password" minlength="12" maxlength="200" required autocomplete="new-password">
                </label>
                <button class="btn btn-primary" type="submit">Create user</button>
            </form>
        </section>

        <section class="user-panel">
            <h2 class="section-heading">System users <span class="muted user-count">(<?= count($users) ?>)</span></h2>
            <div class="table-wrap">
                <table class="user-table">
                    <thead>
                    <tr><th>User</th><th>Role</th><th>Status</th><th>Created</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                    <?php if (!$users): ?>
                        <tr><td colspan="5" class="empty-state">No users found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <strong><?= e($user['full_name']) ?></strong>
                                <small><?= e($user['username']) ?><?= $user['email'] ? ' · ' . e($user['email']) : '' ?></small>
                                <?php if ((int)$user['id'] === (int)current_user()['id']): ?><small>Current account</small><?php endif; ?>
                            </td>
                            <td><span class="role-pill"><?= e($user['role']) ?></span></td>
                            <td><span class="status-pill <?= (int)$user['is_active'] === 1 ? 'active' : 'inactive' ?>"><?= (int)$user['is_active'] === 1 ? 'Active' : 'Inactive' ?></span></td>
                            <td><?= e(date('d M Y', strtotime($user['created_at']))) ?></td>
                            <td>
                                <?php if ((int)$user['id'] === (int)current_user()['id']): ?>
                                    <span class="muted">Your account</span>
                                <?php else: ?>
                                    <form action="toggle_user_status.php" method="post" onsubmit="return confirm('Are you sure you want to <?= (int)$user['is_active'] === 1 ? 'deactivate' : 'activate' ?> this account?');">
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
                                        <button class="btn <?= (int)$user['is_active'] === 1 ? 'btn-plain' : 'btn-secondary' ?>" type="submit"><?= (int)$user['is_active'] === 1 ? 'Deactivate' : 'Activate' ?></button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="muted user-footnote">Accounts are deactivated instead of deleted so that historical sales records remain intact.</p>
        </section>
    </div>
</main>
</body>
</html>
