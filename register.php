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
try {
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $_SESSION['reg_error'] = 'Ten adres email jest już zajęty.';
        header('Location: /index.php#register');
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (email, password) VALUES (?, ?)');
    $stmt->execute([$email, $hash]);
    $userId = $pdo->lastInsertId();

    session_regenerate_id(true);
    $_SESSION['user_id']    = (int)$userId;
    $_SESSION['user_email'] = $email;
    unset($_SESSION['reg_error']);

    header('Location: /index.php');
    exit;
} catch (PDOException $e) {
    $_SESSION['reg_error'] = 'Błąd serwera. Spróbuj ponownie.';
    header('Location: /index.php#register');
    exit;
}
