<?php
/**
 * CLI-only first administrator setup.
 * Run from Command Prompt: C:\xampp\php\php.exe create_admin.php
 * Do not expose this script through a web server.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit('Not found.');
}
require __DIR__ . '/config/db.php';

function ask($prompt)
{
    fwrite(STDOUT, $prompt);
    return trim((string) fgets(STDIN));
}

$username = ask('Admin username: ');
$fullName = ask('Admin full name: ');
$email = ask('Admin email (optional): ');
fwrite(STDOUT, 'Admin password (input may be visible in this terminal): ');
$password = trim((string) fgets(STDIN));

if (!preg_match('/^[A-Za-z0-9_.-]{3,60}$/', $username)) {
    fwrite(STDERR, "Username must be 3-60 characters and use letters, numbers, dot, underscore or hyphen.\n");
    exit(1);
}
if ($fullName === '' || strlen($fullName) > 120 || strlen($password) < 12) {
    fwrite(STDERR, "Full name is required and password must be at least 12 characters.\n");
    exit(1);
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Email format is invalid.\n");
    exit(1);
}

$exists = $pdo->prepare('SELECT id FROM users WHERE username = ?');
$exists->execute([$username]);
if ($exists->fetch()) {
    fwrite(STDERR, "That username already exists.\n");
    exit(1);
}

$stmt = $pdo->prepare("INSERT INTO users (full_name, username, email, password_hash, role, is_active) VALUES (?, ?, ?, ?, 'admin', 1)");
$stmt->execute([$fullName, $username, $email !== '' ? $email : null, password_hash($password, PASSWORD_DEFAULT)]);
fwrite(STDOUT, "Administrator created successfully. Delete or archive this script after setup.\n");
