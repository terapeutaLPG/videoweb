<?php
session_start();
require __DIR__ . '/db.php';

$isAdmin = !empty($_SESSION['is_admin']);

$rootDir = __DIR__;
$videoDir = $rootDir . '/videos';
$thumbMapFile = $videoDir . '/.thumbs.json';

function loadThumbMap(string $file): array
{
    if (!is_file($file)) return [];
    $data = json_decode((string)file_get_contents($file), true);
    return is_array($data) ? $data : [];
}
function saveThumbMap(string $file, array $map): bool
{
    $payload = json_encode($map, JSON_UNESCAPED_SLASHES);
    if ($payload === false) return false;
    return file_put_contents($file, $payload, LOCK_EX) !== false;
}
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
    if (!is_file($path)) return null;
    if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'mp4') return null;
    return $path;
}
function sanitizeThumbPath(string $path): ?string
{
    $path = trim(str_replace('\\', '/', $path));
    if ($path === '') return null;
    if (str_starts_with($path, '/')) return null;
    if (preg_match('~^[a-z]+:~i', $path)) return null;
    if (strpos($path, '..') !== false) return null;
    return $path;
}
function resolveThumbPath(string $rootDir, string $relative): ?string
{
    $relative = sanitizeThumbPath($relative);
    if ($relative === null) return null;

    $fullPath = $rootDir . '/' . $relative;
    $realRoot = realpath($rootDir);
    $realPath = realpath($fullPath);

    if ($realRoot === false || $realPath === false) return null;
    if (strpos($realPath, $realRoot) !== 0) return null;
    if (!is_file($realPath)) return null;

    return $relative;
}

$thumbMap = loadThumbMap($thumbMapFile);

$actionMsg = $_SESSION['action_msg'] ?? null;
$actionErr = $_SESSION['action_err'] ?? null;
unset($_SESSION['action_msg'], $_SESSION['action_err']);

// admin akcje (rename/delete/set_thumb) – tylko jeśli zalogowany
if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete_video') {
        $file = $_POST['file'] ?? '';
        $path = resolveVideoPath($videoDir, $file);

        if ($path && @unlink($path)) {
            if (isset($thumbMap[$file])) {
                unset($thumbMap[$file]);
                saveThumbMap($thumbMapFile, $thumbMap);
            }
            $_SESSION['action_msg'] = 'Film zostal usuniety.';
        } else {
            $_SESSION['action_err'] = 'Nie udalo sie usunac pliku.';
        }

        header('Location: /index.php');
        exit;
    }

    if ($action === 'rename_video') {
        $file = $_POST['file'] ?? '';
        $newName = sanitizeVideoName($_POST['new_name'] ?? '');
        $path = resolveVideoPath($videoDir, $file);

        if (!$path) {
            $_SESSION['action_err'] = 'Nie znaleziono pliku do zmiany nazwy.';
        } elseif ($newName === '') {
            $_SESSION['action_err'] = 'Podaj nowa nazwe.';
        } else {
            $newFile = $newName . '.mp4';
            $target = $videoDir . '/' . $newFile;

            if (is_file($target)) {
                $_SESSION['action_err'] = 'Plik o tej nazwie juz istnieje.';
            } elseif (@rename($path, $target)) {
                if (isset($thumbMap[$file])) {
                    $thumbMap[$newFile] = $thumbMap[$file];
                    unset($thumbMap[$file]);
                    saveThumbMap($thumbMapFile, $thumbMap);
                }
                $_SESSION['action_msg'] = 'Nazwa filmu zostala zmieniona.';
            } else {
                $_SESSION['action_err'] = 'Nie udalo sie zmienic nazwy pliku.';
            }
        }

        header('Location: /index.php');
        exit;
    }

    if ($action === 'set_thumb') {
        $file = $_POST['file'] ?? '';
        $thumbPath = $_POST['thumb_path'] ?? '';
        $videoPath = resolveVideoPath($videoDir, $file);
        $thumbRel = resolveThumbPath($rootDir, $thumbPath);

        if (!$videoPath) {
            $_SESSION['action_err'] = 'Nie znaleziono filmu do ustawienia miniaturki.';
        } elseif ($thumbRel === null) {
            $_SESSION['action_err'] = 'Podaj poprawna sciezke do miniaturki.';
        } else {
            $thumbMap[$file] = $thumbRel;
            if (saveThumbMap($thumbMapFile, $thumbMap)) {
                $_SESSION['action_msg'] = 'Miniaturka zostala ustawiona.';
            } else {
                $_SESSION['action_err'] = 'Nie udalo sie zapisac miniaturki.';
            }
        }

        header('Location: /index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Moja Walentynka</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { min-height: 100vh; background: #0f172a; color: #e5e7eb; font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
        .container { max-width: 1100px; margin: 0 auto; padding: 18px 14px 40px; }
        .topbar { display:flex; justify-content:space-between; align-items:center; padding:16px 24px; background:#0b1224; border-bottom:1px solid rgba(255,255,255,0.08); }
        .topbar-title { font-weight: 700; font-size: 18px; }
        .admin-badge { margin-left: 8px; padding: 2px 8px; border-radius: 9999px; background: rgba(34,197,94,0.15); border: 1px solid rgba(34,197,94,0.25); color:#86efac; font-size: 12px; }
        .topbar-link { color:#93c5fd; text-decoration:none; padding: 8px 10px; border-radius: 10px; }
        .topbar-link:hover { background: rgba(147,197,253,0.12); }
        .card { background:#0b1224; border:1px solid rgba(255,255,255,0.08); border-radius: 14px; padding: 14px; margin: 14px 0; }
        .muted { color:#9ca3af; }
        .notice { margin-top: 10px; padding: 10px; border-radius: 12px; background: rgba(34,197,94,0.12); border: 1px solid rgba(34,197,94,0.25); }
        .error { margin-top: 10px; padding: 10px; border-radius: 12px; background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.25); }
        .field { margin-top: 10px; }
        label { display:block; font-size: 13px; color:#cbd5e1; margin-bottom: 6px; }
        input { width:100%; padding:10px 12px; border-radius:12px; border:1px solid rgba(255,255,255,0.12); background:#0f172a; color:#e5e7eb; outline: none; }
        .btn { margin-top: 12px; padding: 10px 12px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.12); background: rgba(147,197,253,0.12); color:#e5e7eb; cursor:pointer; }
        .btn:hover { background: rgba(147,197,253,0.18); }
        .videos-grid { display:grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 14px; margin-top: 12px; }
        .video-card { background:#0f172a; border:1px solid rgba(255,255,255,0.08); border-radius: 14px; overflow:hidden; }
        .video-card video { width: 100%; height: 160px; background:#000; }
        .video-card-body { padding: 10px 10px 12px; }
        .video-title { font-weight: 650; margin-bottom: 4px; }
        .video-date { color:#9ca3af; font-size: 12px; }
        .video-actions { margin-top: 10px; display:flex; flex-direction:column; gap: 8px; }
        .action-link { padding: 8px 10px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.10); background: rgba(255,255,255,0.06); color: #e5e7eb; cursor:pointer; text-align:left; }
        .action-link:hover { background: rgba(255,255,255,0.10); }
        .inline-form { display:block; }
        .search-bar { display:flex; align-items:center; gap:10px; margin-top: 10px; }
        .search-input { flex:1; }
    </style>
</head>
<body>

<?php include __DIR__ . '/partials/header.php'; ?>

<div class="container">
    <?php if (!$isAdmin): ?>
        <?php include __DIR__ . '/partials/login_form.php'; ?>
    <?php endif; ?>

    <?php if ($isAdmin): ?>
        <?php include __DIR__ . '/partials/admin_panel.php'; ?>
    <?php endif; ?>

    <?php include __DIR__ . '/partials/videos_list.php'; ?>
</div>

</body>
</html>
