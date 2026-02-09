<?php
session_start();

$adminUser = 'admin';
$adminPass = 'password123';

$isAdmin = !empty($_SESSION['is_admin']);
$loginError = null;
$actionMsg = null;
$actionErr = null;

$videoDir = __DIR__ . '/videos';

function sanitizeVideoName(string $name): string
{
    $name = preg_replace('/[^a-zA-Z0-9 _-]+/', '', $name);
    $name = preg_replace('/\s+/', '_', $name);
    $name = trim($name, '_-');
    return $name;
}

function resolveVideoPath(string $videoDir, string $file): ?string
{
    $file = basename($file);
    $path = $videoDir . '/' . $file;
    if (!is_file($path)) {
        return null;
    }
    if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'mp4') {
        return null;
    }
    return $path;
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    $login = $_POST['login'] ?? '';
    $password = $_POST['password'] ?? '';
    if ($login === $adminUser && $password === $adminPass) {
        $_SESSION['is_admin'] = true;
        header('Location: /index.php?admin=1');
        exit;
    }
    $loginError = 'Zly login lub haslo';
}

if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_video') {
    $file = $_POST['file'] ?? '';
    $path = resolveVideoPath($videoDir, $file);
    if ($path && unlink($path)) {
        $actionMsg = 'Film zostal usuniety.';
    } else {
        $actionErr = 'Nie udalo sie usunac pliku.';
    }
}

if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'rename_video') {
    $file = $_POST['file'] ?? '';
    $newName = sanitizeVideoName($_POST['new_name'] ?? '');
    $path = resolveVideoPath($videoDir, $file);
    if (!$path) {
        $actionErr = 'Nie znaleziono pliku do zmiany nazwy.';
    } elseif ($newName === '') {
        $actionErr = 'Podaj nowa nazwe.';
    } else {
        $target = $videoDir . '/' . $newName . '.mp4';
        if (is_file($target)) {
            $actionErr = 'Plik o tej nazwie juz istnieje.';
        } elseif (rename($path, $target)) {
            $actionMsg = 'Nazwa filmu zostala zmieniona.';
        } else {
            $actionErr = 'Nie udalo sie zmienic nazwy pliku.';
        }
    }
}

$showLogin = (!$isAdmin && (isset($_GET['admin']) || $loginError));
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Moja Walentynka</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            min-height: 100vh;
            background: #0f172a;
            color: #e5e7eb;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        }
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 24px;
            background: #0b1224;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        .topbar-title { font-weight: 600; font-size: 18px; }
        .topbar-link { color: #fb7185; text-decoration: none; font-weight: 600; }
        .topbar-link:hover { text-decoration: underline; }
        .container { max-width: 1200px; margin: 24px auto; padding: 0 16px 40px; }
        .card {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .search-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
        }
        .search-icon {
            width: 16px;
            height: 16px;
            color: #94a3b8;
        }
        .search-input {
            flex: 1;
        }
        h2 { margin-bottom: 12px; font-size: 20px; }
        label { display: block; font-size: 13px; margin-bottom: 6px; color: #cbd5f5; }
        input[type="text"],
        input[type="password"],
        textarea {
            width: 100%;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid rgba(255,255,255,0.15);
            background: rgba(255,255,255,0.06);
            color: #e5e7eb;
            font-size: 14px;
            outline: none;
        }
        textarea { resize: vertical; }
        .field { margin-bottom: 14px; }
        .btn {
            padding: 10px 18px;
            border-radius: 9999px;
            border: none;
            background: linear-gradient(90deg, #fb7185, #f97316);
            color: white;
            font-weight: 600;
            cursor: pointer;
        }
        .btn:hover { opacity: 0.9; }
        .error {
            margin-top: 10px;
            padding: 10px;
            background: rgba(239, 68, 68, 0.2);
            border-radius: 8px;
            font-size: 13px;
            color: #fca5a5;
        }
        .notice {
            margin-top: 10px;
            padding: 10px;
            background: rgba(34, 197, 94, 0.15);
            border-radius: 8px;
            font-size: 13px;
            color: #bbf7d0;
        }
        .muted { color: #9ca3af; }
        .videos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 16px;
        }
        .video-card {
            background: rgba(0,0,0,0.25);
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.08);
        }
        .video-card video {
            display: block;
            width: 100%;
            height: 120px;
            object-fit: cover;
            background: #000;
        }
        .video-card-body { padding: 12px; }
        .video-title { font-size: 15px; font-weight: 600; margin-bottom: 4px; }
        .video-date { font-size: 11px; color: #9ca3af; margin-bottom: 6px; }
        .video-desc { font-size: 12px; color: #d1d5db; line-height: 1.4; }
        .video-actions { margin-top: 8px; display: flex; gap: 12px; }
        .action-link {
            background: none;
            border: none;
            color: #fda4af;
            font-size: 12px;
            text-decoration: underline;
            cursor: pointer;
            padding: 0;
        }
        .inline-form { display: inline; }
        .modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.75);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 50;
        }
        .modal {
            background: #0b1224;
            border-radius: 12px;
            max-width: 900px;
            width: 95%;
            padding: 12px 12px 16px;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        .close-btn {
            background: none;
            border: none;
            color: #9ca3af;
            font-size: 20px;
            cursor: pointer;
        }
        video { width: 100%; max-height: 70vh; background: #000; }
    </style>
</head>
<body>

<?php include __DIR__ . '/partials/header.php'; ?>

<div class="container">
    <?php if ($showLogin): ?>
        <?php include __DIR__ . '/partials/login_form.php'; ?>
    <?php endif; ?>

    <?php if ($isAdmin): ?>
        <?php include __DIR__ . '/partials/admin_panel.php'; ?>
    <?php endif; ?>

    <?php include __DIR__ . '/partials/videos_list.php'; ?>
</div>


</body>
</html>
