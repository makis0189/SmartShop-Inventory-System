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

$fullName = trim($_POST['full_name'] ?? '');
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$role = $_POST['role'] ?? 'cashier';
$password = (string) ($_POST['password'] ?? '');
$confirmPassword = (string) ($_POST['confirm_password'] ?? '');

if ($fullName === '' || strlen($fullName) > 120) {
    set_flash('error', 'Enter a full name of up to 120 characters.');
    redirect_to('user_management.php');
}
if (!preg_match('/^[A-Za-z0-9_.-]{3,60}$/', $username)) {
    set_flash('error', 'Username must be 3–60 characters and use letters, numbers, dots, underscores or hyphens.');
    redirect_to('user_management.php');
}
if (!in_array($role, ['admin', 'manager', 'cashier'], true)) {
    set_flash('error', 'Select a valid user role.');
    redirect_to('user_management.php');
}
if ($email !== '' && (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150)) {
    set_flash('error', 'Enter a valid email address or leave it blank.');
    redirect_to('user_management.php');
}
if (strlen($password) < 12 || strlen($password) > 200) {
    set_flash('error', 'Password must contain at least 12 characters.');
    redirect_to('user_management.php');
}
if (!hash_equals($password, $confirmPassword)) {
    set_flash('error', 'The passwords do not match.');
    redirect_to('user_management.php');
}

try {
    // Use separate query paths for optional email. With native PDO prepares,
    // reusing the same named placeholder more than once can trigger HY093.
    if ($email !== '') {
        $check = $pdo->prepare(
            'SELECT id FROM users
             WHERE username = :username OR email = :email
             LIMIT 1'
        );
        $check->execute([
            ':username' => $username,
            ':email' => $email,
        ]);
    } else {
        $check = $pdo->prepare(
            'SELECT id FROM users
             WHERE username = :username
             LIMIT 1'
        );
        $check->execute([
            ':username' => $username,
        ]);
    }

    if ($check->fetch()) {
        set_flash('error', 'That username or email is already in use. Please choose different details.');
        redirect_to('user_management.php');
    }

    $insert = $pdo->prepare(
        'INSERT INTO users (full_name, username, email, password_hash, role, is_active)
         VALUES (:full_name, :username, :email, :password_hash, :role, 1)'
    );
    $insert->execute([
        ':full_name' => $fullName,
        ':username' => $username,
        ':email' => $email !== '' ? $email : null,
        ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ':role' => $role,
    ]);
    set_flash('success', 'User account created successfully. Share the temporary login details securely with the staff member.');
} catch (PDOException $e) {
    // Keep technical details in the server log; do not expose SQL errors to users.
    error_log('SmartShop user creation failed: ' . $e->getMessage());

    if ($e->getCode() === '23000') {
        set_flash('error', 'That username or email is already in use. Please choose different details.');
    } else {
        set_flash('error', 'The account could not be created. Check the server error log for details, then try again.');
    }
}
redirect_to('user_management.php');
