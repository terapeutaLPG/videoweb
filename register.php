<?php
header('Cache-Control: no-store, no-cache, must-revalidate');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

require __DIR__ . '/db.php';

$email    = trim($_POST['reg_email']    ?? '');
$password = trim($_POST['reg_password'] ?? '');
$password2 = trim($_POST['reg_password2'] ?? '');

// Walidacja
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['reg_error'] = 'Podaj poprawny adres email.';
    header('Location: /index.php#register');
    exit;
}
if (mb_strlen($password) < 6) {
    $_SESSION['reg_error'] = 'Hasło musi mieć minimum 6 znaków.';
    header('Location: /index.php#register');
    exit;
}
if ($password !== $password2) {
    $_SESSION['reg_error'] = 'Hasła nie są identyczne.';
    header('Location: /index.php#register');
    exit;
}
