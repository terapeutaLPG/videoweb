<?php
session_start();
require __DIR__ . '/db.php';

$login = $_POST['login'] ?? '';
$password = $_POST['password'] ?? '';

if ($login === $adminUser && $password === $adminPass) {
    session_regenerate_id(true);
    $_SESSION['is_admin'] = true;
    header('Location: /index.php');
    exit;
}

$_SESSION['login_error'] = 'Zly login lub haslo';
header('Location: /index.php#login');
exit;
