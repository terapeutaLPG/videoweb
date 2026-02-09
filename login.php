<?php
header('Cache-Control: no-store, no-cache, must-revalidate');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

require __DIR__ . '/db.php';

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($username === $adminUser && $password === $adminPass) {
    session_regenerate_id(true);
    $_SESSION['is_admin'] = true;
    unset($_SESSION['login_error']);
    header('Location: /index.php');
    exit;
}

$_SESSION['login_error'] = 'Błędny login lub hasło';
header('Location: /index.php#login');
exit;
