<?php
header('Cache-Control: no-store, no-cache, must-revalidate');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

require __DIR__ . '/db.php';

$username = trim($_POST['username'] ?? '');
$password = (string)($_POST['password'] ?? '');

function adminPasswordMatches(string $inputPassword, string $storedPassword): bool
{
    if (password_get_info($storedPassword)['algo'] !== null && password_verify($inputPassword, $storedPassword)) {
        return true;
    }

    if (str_starts_with($storedPassword, '$2y$') || str_starts_with($storedPassword, '$2b$') || str_starts_with($storedPassword, '$2a$')) {
        $candidates = [$storedPassword];
        if (str_starts_with($storedPassword, '$2y$')) {
            $candidates[] = '$2b$' . substr($storedPassword, 4);
            $candidates[] = '$2a$' . substr($storedPassword, 4);
        } elseif (str_starts_with($storedPassword, '$2b$')) {
            $candidates[] = '$2y$' . substr($storedPassword, 4);
            $candidates[] = '$2a$' . substr($storedPassword, 4);
        } else {
            $candidates[] = '$2y$' . substr($storedPassword, 4);
            $candidates[] = '$2b$' . substr($storedPassword, 4);
        }
        foreach (array_unique($candidates) as $candidate) {
            if (password_verify($inputPassword, $candidate)) {
                return true;
            }
        }
    }

    // Legacy fallback: md5/sha1/plaintext. If used, it will be upgraded after login.
    if (strlen($storedPassword) === 32 && ctype_xdigit($storedPassword) && hash_equals(strtolower($storedPassword), md5($inputPassword))) {
        return true;
    }
    if (strlen($storedPassword) === 40 && ctype_xdigit($storedPassword) && hash_equals(strtolower($storedPassword), sha1($inputPassword))) {
        return true;
    }
    if (hash_equals($storedPassword, $inputPassword)) {
        return true;
    }

    return false;
}

try {
    $stmt = $pdo->prepare('SELECT id, login, password FROM admins WHERE login = ? LIMIT 1');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    if ($admin) {
        $stored = (string)$admin['password'];
        if (adminPasswordMatches($password, $stored)) {
            session_regenerate_id(true);
            $_SESSION['is_admin'] = true;
            $_SESSION['admin_login'] = (string)$admin['login'];
            unset($_SESSION['user_id'], $_SESSION['user_email']);
            unset($_SESSION['login_error']);

            if (password_get_info($stored)['algo'] === null || password_needs_rehash($stored, PASSWORD_DEFAULT)) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $up = $pdo->prepare('UPDATE admins SET password = ? WHERE id = ?');
                $up->execute([$newHash, (int)$admin['id']]);
            }

            header('Location: index.php');
            exit;
        }
    }
} catch (PDOException $e) {
    error_log('ADMIN LOGIN DB ERROR');
}

try {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id']    = (int)$user['id'];
        $_SESSION['user_email'] = $user['email'];
        unset($_SESSION['is_admin'], $_SESSION['admin_login']);
        unset($_SESSION['login_error']);
        header('Location: index.php');
        exit;
    }
} catch (PDOException $e) {
    error_log('USER LOGIN DB ERROR');
}

$_SESSION['login_error'] = 'Błędny login lub hasło.';
header('Location: index.php#login');
exit;
