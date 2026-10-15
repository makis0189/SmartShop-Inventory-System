<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/helpers.php';

if (!empty($_SESSION['user'])) {
    redirect_to(($_SESSION['user']['role'] ?? '') === 'cashier' ? 'sales.php' : 'dashboard.php');
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Security validation failed. Refresh the page and try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '' || strlen($username) > 60) {
            $error = 'Enter your username and password.';
        } else {
            $stmt = $pdo->prepare('SELECT id, full_name, username, password_hash, role, is_active FROM users WHERE username = ? LIMIT 1');
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && (int) $user['is_active'] === 1 && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['user'] = [
                    'id' => (int) $user['id'],
                    'full_name' => $user['full_name'],
                    'username' => $user['username'],
                    'role' => $user['role'],
                ];
                unset($_SESSION['csrf_token']);
                csrf_token();
                redirect_to($user['role'] === 'cashier' ? 'sales.php' : 'dashboard.php');
            }

            // Same message for unknown users, inactive users, and wrong passwords.
            $error = 'Invalid username or password, or the account is inactive.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sign in | SmartShop</title>
  <link rel="stylesheet" href="Assets/css/style.css">
  <link rel="stylesheet" href="Assets/css/inventory.css">
  <link rel="stylesheet" href="Assets/css/auth.css">
  <link rel="stylesheet" href="Assets/css/enterprise-overrides.css">
</head>
<body class="inventory-page">
  <main class="login-wrap">
    <section class="login-card">
      <p class="eyebrow">SMARTSHOP INVENTORY</p>
      <h1>Sign in</h1>
      <p class="muted">Sign in to manage products, sales and reports.</p>
      <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
      <?php $flash = get_flash(); if ($flash): ?><div class="alert <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endif; ?>
      <form method="post" action="login.php">
        <?= csrf_input() ?>
        <label for="username">Username</label>
        <input id="username" name="username" maxlength="60" autocomplete="username" required autofocus>
        <label for="password">Password</label>
        <input id="password" name="password" type="password" autocomplete="current-password" required>
        <button class="btn btn-primary" type="submit">Sign in</button>
      </form>
      <p class="muted login-account-note">Need an account? Ask the system administrator.</p>
    </section>
  </main>
</body>
</html>
