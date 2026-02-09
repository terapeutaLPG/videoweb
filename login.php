<?php
session_start();
require __DIR__ . '/db.php';

$login = $_POST['login'] ?? '';
$password = $_POST['password'] ?? '';

// Debug - sprawdź co przychodzi
error_log("Login attempt - user: " . $login);

if ($login === $adminUser && $password === $adminPass) {
    session_regenerate_id(true);
    $_SESSION['is_admin'] = true;
    
    // Użyj pełnego URL zamiast względnego
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $redirectUrl = $protocol . $host . '/index.php';
    
    error_log("Login successful, redirecting to: " . $redirectUrl);
    
    header('Location: ' . $redirectUrl);
    exit;
}

// Logowanie nie powiodło się
$_SESSION['login_error'] = 'Błędny login lub hasło';
error_log("Login failed for user: " . $login);

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$redirectUrl = $protocol . $host . '/index.php#login';

header('Location: ' . $redirectUrl);
exit;