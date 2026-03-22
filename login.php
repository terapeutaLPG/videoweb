<?php
header('Cache-Control: no-store, no-cache, must-revalidate');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

require __DIR__ . '/db.php';

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

try {
    $stmt = $pdo->prepare('SELECT id, password FROM admins WHERE login = ?');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    if ($admin && password_verify($password, $admin['password'])) {
        session_regenerate_id(true);
        $_SESSION['is_admin'] = true;
        unset($_SESSION['login_error']);
        header('Location: /index.php');
        exit;
    }
} catch (PDOException $e) {
}

try {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id']    = (int)$user['id'];
        $_SESSION['user_email'] = $user['email'];
        unset($_SESSION['login_error']);
        header('Location: /index.php');
        exit;
    }
} catch (PDOException $e) {
}

$_SESSION['login_error'] = 'Błędny login lub hasło.';
header('Location: /index.php#login');
exit;
