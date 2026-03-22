<?php
require __DIR__ . '/db.php';
$isAdmin = !empty($_SESSION['is_admin']);
$actionMsg = '';
$actionErr = '';

$metaTableReady = false;
// try {
//   $pdo->exec(
//     'CREATE TABLE IF NOT EXISTS video_meta (
//       file_name VARCHAR(255) PRIMARY KEY,
//       description TEXT NOT NULL,
//       updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
//     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
//   );
//   $metaTableReady = true;
// } catch (PDOException $e) {
//   $actionErr = 'Nie udalo sie przygotowac bazy opisow.';
// }
$metaTableReady = false;
try {
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS video_meta (
      file_name VARCHAR(255) PRIMARY KEY,
      description TEXT NOT NULL DEFAULT '',
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  ");
  $metaTableReady = true;
} catch (PDOException $e) {
  error_log('video_meta error: ' . $e->getMessage());
}

// $metaTableReady = false;
// try {
//   $stmt = $pdo->query("SHOW TABLES LIKE 'video_meta'");
//   $metaTableReady = $stmt->rowCount() > 0;
// } catch (PDOException $e) {
//   $metaTableReady = false;
// }


if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  $file = basename($_POST['file'] ?? '');
  $videoDirFs = __DIR__ . '/videos';
  $thumbDirFs = __DIR__ . '/thumbnails';
  $videoPath = $videoDirFs . '/' . $file;
  $baseName = pathinfo($file, PATHINFO_FILENAME);

  if (!is_file($videoPath)) {
    $actionErr = 'Nie znaleziono pliku wideo.';
  } else {
    if ($action === 'rename') {
      $new = trim($_POST['new_name'] ?? '');
      $new = preg_replace('/[^A-Za-z0-9 _-]/', '', $new);
      $new = trim(preg_replace('/\s+/', ' ', $new));
      if ($new === '') {
        $actionErr = 'Nowa nazwa jest pusta.';
      } else {
        $newFile = $new . '.mp4';
        $newPath = $videoDirFs . '/' . $newFile;
        if (is_file($newPath)) {
          $actionErr = 'Plik o tej nazwie już istnieje.';
        } elseif (@rename($videoPath, $newPath)) {
          foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
            $oldThumb = $thumbDirFs . '/' . $baseName . '.' . $ext;
            if (is_file($oldThumb)) {
              @rename($oldThumb, $thumbDirFs . '/' . $new . '.' . $ext);
            }
          }
          if ($metaTableReady) {
            $stmt = $pdo->prepare('UPDATE video_meta SET file_name = :new WHERE file_name = :old');
            $stmt->execute([':new' => $newFile, ':old' => $file]);
          }
          $actionMsg = 'Zmieniono nazwę pliku.';
        } else {
          $actionErr = 'Nie udało się zmienić nazwy.';
        }
      }
    } elseif ($action === 'delete') {
      if (@unlink($videoPath)) {
        foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
          $oldThumb = $thumbDirFs . '/' . $baseName . '.' . $ext;
          if (is_file($oldThumb)) @unlink($oldThumb);
        }
        if ($metaTableReady) {
          $stmt = $pdo->prepare('DELETE FROM video_meta WHERE file_name = :file');
          $stmt->execute([':file' => $file]);
        }
        $actionMsg = 'Usunięto plik.';
      } else {
        $actionErr = 'Nie udało się usunąć pliku.';
      }
    } elseif ($action === 'thumb') {
      if (!is_dir($thumbDirFs)) {
        @mkdir($thumbDirFs, 0755, true);
      }
      if (!is_dir($thumbDirFs) || !is_writable($thumbDirFs)) {
        $actionErr = 'Brak zapisu do katalogu /thumbnails.';
      } else {
        $f = $_FILES['thumb'] ?? null;
        if (!$f || !isset($f['error'])) {
          $actionErr = 'Brak pliku miniaturki.';
        } elseif ($f['error'] !== UPLOAD_ERR_OK) {
          $errMap = [
            UPLOAD_ERR_INI_SIZE => 'Plik za duży (limit serwera).',
            UPLOAD_ERR_FORM_SIZE => 'Plik za duży (limit formularza).',
            UPLOAD_ERR_PARTIAL => 'Plik wysłany częściowo.',
            UPLOAD_ERR_NO_FILE => 'Nie wybrano pliku.',
            UPLOAD_ERR_NO_TMP_DIR => 'Brak katalogu tymczasowego.',
            UPLOAD_ERR_CANT_WRITE => 'Brak zapisu na dysku.',
            UPLOAD_ERR_EXTENSION => 'Upload zablokowany przez rozszerzenie.',
          ];
          $actionErr = $errMap[$f['error']] ?? 'Błąd uploadu miniaturki.';
        } else {
          if (!isset($f['tmp_name']) || !is_string($f['tmp_name']) || !is_uploaded_file($f['tmp_name'])) {
            $actionErr = 'Nieprawidłowy plik.';
          } else {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($f['tmp_name']);
            $map = [
              'image/jpeg' => 'jpg',
              'image/png' => 'png',
              'image/webp' => 'webp',
            ];
            if (!isset($map[$mime])) {
              $actionErr = 'Dozwolone formaty: JPG/PNG/WEBP.';
            } else {
              foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
                $oldThumb = $thumbDirFs . '/' . $baseName . '.' . $ext;
                if (is_file($oldThumb)) @unlink($oldThumb);
              }
              $dest = $thumbDirFs . '/' . $baseName . '.' . $map[$mime];
              if (@move_uploaded_file($f['tmp_name'], $dest)) {
                $actionMsg = 'Dodano miniaturkę.';
              } else {
                $actionErr = 'Nie udało się zapisać miniaturki.';
              }
            }
          }
        }
      }
    } elseif ($action === 'desc') {
      if (!$metaTableReady) {
        $actionErr = 'Baza opisow jest niedostepna.';
      } else {
        $desc = trim($_POST['description'] ?? '');
        if (mb_strlen($desc) > 1200) {
          $desc = mb_substr($desc, 0, 1200);
        }
        $stmt = $pdo->prepare(
          'INSERT INTO video_meta (file_name, description) VALUES (:file, :desc)
           ON DUPLICATE KEY UPDATE description = VALUES(description)'
        );
        $stmt->execute([':file' => $file, ':desc' => $desc]);
        $actionMsg = 'Zapisano opis.';
      }
    }
  }
}

$videoDescriptions = [];
if ($metaTableReady) {
  $rows = $pdo->query('SELECT file_name, description FROM video_meta')->fetchAll();
  foreach ($rows as $row) {
    $videoDescriptions[$row['file_name']] = $row['description'];
  }
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/');
$siteUrl = $scheme . '://' . $host . ($basePath ? $basePath . '/' : '/');
$ogImageUrl = $siteUrl . 'og-image.svg';
$iconUrl = $siteUrl . 'favicon.svg';
?>
<!DOCTYPE html>
<html lang="pl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Filmy PL</title>
  <meta name="description" content="Biblioteka wideo Filmy PL.">
  <link rel="icon" href="<?php echo htmlspecialchars($iconUrl, ENT_QUOTES); ?>" type="image/svg+xml">
  <link rel="shortcut icon" href="<?php echo htmlspecialchars($iconUrl, ENT_QUOTES); ?>">
  <meta property="og:title" content="Filmy PL">
  <meta property="og:description" content="Biblioteka wideo Filmy PL.">
  <meta property="og:type" content="website">
  <meta property="og:url" content="<?php echo htmlspecialchars($siteUrl, ENT_QUOTES); ?>">
  <meta property="og:image" content="<?php echo htmlspecialchars($ogImageUrl, ENT_QUOTES); ?>">
  <meta property="og:image:type" content="image/svg+xml">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="Filmy PL">
  <meta name="twitter:description" content="Biblioteka wideo Filmy PL.">
  <meta name="twitter:image" content="<?php echo htmlspecialchars($ogImageUrl, ENT_QUOTES); ?>">

  <style>
    @import url('https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap');

    :root {
      --bg-0: #0a0c12;
      --bg-1: #0e1320;
      --panel: rgba(18, 24, 38, 0.78);
      --panel-strong: #111827;
      --card: rgba(19, 26, 42, 0.85);
      --glass: rgba(20, 28, 46, 0.62);
      --text: #e7e9ef;
      --muted: #a2a8b8;
      --border: rgba(255, 255, 255, 0.10);
      --accent: #39d3ff;
      --accent-soft: rgba(57, 211, 255, 0.18);
      --danger: #ff6b7a;
      --danger-soft: rgba(255, 107, 122, 0.15);
      --shadow: 0 18px 40px rgba(0, 0, 0, 0.35);
      --radius-lg: 18px;
      --radius-md: 14px;
      --radius-sm: 10px;
      --focus: 0 0 0 3px rgba(57, 211, 255, 0.25);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    html,
    body {
      height: 100%;
    }

    body {
      min-height: calc(100vh + 160px);
      color: var(--text);
      font-family: "Manrope", "Segoe UI", sans-serif;
      background:
        radial-gradient(1200px 600px at 12% 8%, rgba(57, 211, 255, 0.16), transparent 60%),
        radial-gradient(900px 500px at 88% 12%, rgba(111, 92, 255, 0.14), transparent 55%),
        radial-gradient(800px 420px at 50% 92%, rgba(255, 107, 122, 0.10), transparent 55%),
        linear-gradient(180deg, var(--bg-0), var(--bg-1));
      position: relative;
      overflow-x: hidden;
      overflow-y: auto;
    }

    body.is-locked {
      overflow: hidden;
    }

    body::before {
      content: "";
      position: fixed;
      inset: 0;
      pointer-events: none;
      background-image: url("data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgdmlld0JveD0iMCAwIDIwMCAyMDAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGZpbHRlciBpZD0ibm9pc2UiPjxmZVR1cmJ1bGVuY2UgdHlwZT0iZnJhY3RhbE5vaXNlIiBiYXNlRnJlcXVlbmN5PSIwLjgiIG51bU9jdGF2ZXM9IjIiIHN0aXRjaFRpbGVzPSJzdGl0Y2giLz48L2ZpbHRlcj48cmVjdCB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgZmlsdGVyPSJ1cmwoI25vaXNlKSIgb3BhY2l0eT0iMC4xOCIvPjwvc3ZnPg==");
      opacity: 0.22;
      mix-blend-mode: soft-light;
      z-index: 0;
    }

    body>* {
      position: relative;
      z-index: 1;
    }

    a {
      color: inherit;
      text-decoration: none;
    }

    .container {
      max-width: 1280px;
      margin: 0 auto;
      padding: 22px 18px 140px;
    }

    .topbar {
      position: sticky;
      top: 0;
      z-index: 50;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      padding: 18px 28px;
      background: rgba(14, 18, 30, 0.86);
      border-bottom: 1px solid rgba(255, 255, 255, 0.06);
      backdrop-filter: blur(12px);
      box-shadow: 0 12px 30px rgba(0, 0, 0, 0.35);
    }

    .topbar-left {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }

    .topbar-title {
      font-weight: 800;
      font-size: 20px;
      letter-spacing: 0.3px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .topbar-sub {
      font-size: 12px;
      color: var(--muted);
      letter-spacing: 0.2px;
    }

    .admin-badge {
      padding: 3px 10px;
      border-radius: 999px;
      font-size: 11px;
      letter-spacing: 0.4px;
      text-transform: uppercase;
      background: rgba(80, 220, 170, 0.14);
      border: 1px solid rgba(80, 220, 170, 0.3);
      color: #9ef1d1;
    }

    .topbar-actions {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .topbar-link {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      color: var(--text);
      padding: 8px 14px;
      border-radius: 999px;
      border: 1px solid rgba(255, 255, 255, 0.1);
      background: rgba(255, 255, 255, 0.04);
      font-size: 13px;
      transition: transform 0.2s ease, border-color 0.2s ease, background 0.2s ease;
      cursor: pointer;
      font: inherit;
    }

    .tv-toggle {
      gap: 6px;
      padding: 7px 10px;
    }

    .tv-toggle svg {
      width: 18px;
      height: 18px;
    }

    .tv-label {
      font-size: 11px;
      letter-spacing: 0.5px;
      font-weight: 700;
    }

    .tv-toggle.is-on {
      border-color: rgba(57, 211, 255, 0.55);
      background: rgba(57, 211, 255, 0.2);
      box-shadow: 0 0 0 2px rgba(57, 211, 255, 0.12) inset;
    }

    .tv-toggle.is-pressed {
      animation: tv-press 0.35s ease;
    }


    .tv-toast {
      position: fixed;
      top: 86px;
      right: 24px;
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 14px;
      border-radius: 12px;
      border: 1px solid rgba(57, 211, 255, 0.35);
      background: rgba(12, 18, 30, 0.92);
      color: var(--text);
      box-shadow: 0 16px 30px rgba(0, 0, 0, 0.35);
      opacity: 0;
      transform: translateY(-8px) scale(0.98);
      pointer-events: none;
      z-index: 130;
    }

    .tv-toast.show {
      animation: tv-toast 2.2s ease forwards;
    }

    .tv-toast-icon {
      width: 26px;
      height: 26px;
      border-radius: 9px;
      display: grid;
      place-items: center;
      background: rgba(57, 211, 255, 0.18);
      color: var(--accent);
      border: 1px solid rgba(57, 211, 255, 0.35);
    }

    .tv-toast-icon svg {
      width: 16px;
      height: 16px;
    }

    .tv-toast-title {
      font-weight: 700;
      font-size: 13px;
      letter-spacing: 0.2px;
    }

    .tv-toast-sub {
      font-size: 11px;
      color: var(--muted);
    }

    .topbar-link:hover {
      transform: translateY(-1px);
      border-color: rgba(57, 211, 255, 0.35);
      background: rgba(57, 211, 255, 0.12);
    }

    .card {
      background: var(--glass);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 18px 20px;
      box-shadow: var(--shadow);
    }

    .section-title {
      font-size: 18px;
      font-weight: 700;
      margin-bottom: 6px;
    }

    .muted {
      color: var(--muted);
    }

    .pill {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 12px;
      border-radius: 999px;
      border: 1px solid rgba(57, 211, 255, 0.25);
      background: rgba(57, 211, 255, 0.12);
      font-size: 12px;
      color: var(--text);
    }

    .dot {
      opacity: 0.5;
      margin: 0 6px;
    }

    .videos-section {
      margin-top: 18px;
    }

    .videos-head {
      display: flex;
      align-items: flex-end;
      justify-content: space-between;
      gap: 18px;
      margin-bottom: 18px;
    }

    .videos-title {
      font-size: 26px;
      font-weight: 800;
      letter-spacing: 0.2px;
    }

    .videos-sub {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 13px;
      color: var(--muted);
      margin-top: 6px;
    }

    .recent-section {
      margin-top: 12px;
      margin-bottom: 22px;
      display: grid;
      gap: 16px;
    }

    .recent-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 14px;
    }

    .recent-title {
      font-size: 22px;
      font-weight: 800;
      letter-spacing: 0.2px;
    }

    .recent-sub {
      margin-top: 6px;
      font-size: 13px;
    }

    .recent-card {
      display: grid;
      grid-template-columns: minmax(220px, 34%) 1fr;
      gap: 18px;
      padding: 18px;
      border-radius: var(--radius-lg);
      border: 1px solid rgba(255, 255, 255, 0.12);
      background: rgba(12, 18, 30, 0.8);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.35);
      cursor: pointer;
      transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .recent-card:hover,
    .recent-card:focus-visible {
      transform: translateY(-2px);
      border-color: rgba(57, 211, 255, 0.4);
      box-shadow: 0 24px 44px rgba(0, 0, 0, 0.45);
      outline: none;
    }

    .recent-thumb {
      width: 100%;
      aspect-ratio: 16 / 9;
      border-radius: var(--radius-md);
      overflow: hidden;
      background: rgba(57, 211, 255, 0.16);
      border: 1px solid rgba(255, 255, 255, 0.12);
      display: grid;
      place-items: center;
      color: rgba(255, 255, 255, 0.72);
      font-size: 12px;
      text-align: center;
      padding: 10px;
    }

    .recent-thumb img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    .recent-body {
      display: grid;
      gap: 10px;
      align-content: start;
    }

    .recent-name {
      font-size: 18px;
      font-weight: 700;
      line-height: 1.3;
    }

    .recent-desc {
      color: var(--muted);
      font-size: 13px;
      line-height: 1.6;
      display: -webkit-box;
      -webkit-line-clamp: 3;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .recent-meta {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
      font-size: 12px;
    }

    .recent-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
    }

    .search-wrap {
      width: min(460px, 100%);
    }

    .search-field {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 14px;
      border-radius: 999px;
      border: 1px solid rgba(255, 255, 255, 0.12);
      background: rgba(15, 19, 33, 0.8);
      transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
    }

    .search-field:focus-within {
      border-color: rgba(57, 211, 255, 0.6);
      box-shadow: var(--focus);
      background: rgba(18, 23, 40, 0.9);
    }

    .search-icon {
      width: 18px;
      height: 18px;
      color: var(--muted);
    }

    .search-input {
      width: 100%;
      background: transparent;
      border: 0;
      outline: none;
      color: var(--text);
      font-size: 14px;
    }

    .videos-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 28px;
    }

    .video-card {
      position: relative;
      background: var(--card);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: var(--radius-lg);
      overflow: hidden;
      box-shadow: 0 16px 30px rgba(0, 0, 0, 0.28);
      transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
      content-visibility: auto;
      contain-intrinsic-size: 340px 260px;
    }

    .hover-preview {
      position: fixed;
      inset: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px;
      background: rgba(5, 8, 16, 0.55);
      opacity: 0;
      visibility: hidden;
      pointer-events: none;
      transition: opacity 0.25s ease, visibility 0.25s ease;
      z-index: 35;
    }

    .hover-preview.is-open {
      opacity: 1;
      visibility: visible;
    }

    .hover-preview-panel {
      width: min(1040px, 92vw);
      height: min(70vh, 620px);
      display: grid;
      grid-template-columns: minmax(260px, 45%) 1fr;
      gap: 24px;
      padding: 26px;
      border-radius: 22px;
      border: 1px solid rgba(255, 255, 255, 0.18);
      background: rgba(6, 10, 20, 0.97);
      box-shadow: 0 28px 70px rgba(0, 0, 0, 0.6);
      transform: translateY(12px) scale(0.98);
      transition: transform 0.25s ease;
    }

    .hover-preview.is-open .hover-preview-panel {
      transform: translateY(0) scale(1);
    }

    .hover-preview-thumb {
      width: 100%;
      height: 100%;
      border-radius: 16px;
      overflow: hidden;
      background: rgba(57, 211, 255, 0.12);
      border: 1px solid rgba(255, 255, 255, 0.12);
      display: grid;
      place-items: center;
      color: rgba(255, 255, 255, 0.7);
      font-size: 13px;
      text-align: center;
      padding: 10px;
    }

    .hover-preview-thumb img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    .hover-preview-title {
      font-size: 20px;
      font-weight: 700;
      margin-bottom: 12px;
    }

    .hover-preview-desc {
      font-size: 15px;
      color: var(--muted);
      line-height: 1.65;
      display: -webkit-box;
      -webkit-line-clamp: 12;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .video-card:focus-visible {
      outline: none;
      border-color: rgba(57, 211, 255, 0.5);
      box-shadow: var(--focus), 0 16px 30px rgba(0, 0, 0, 0.28);
    }

    .video-card:hover {
      transform: translateY(-4px);
      border-color: rgba(57, 211, 255, 0.35);
      box-shadow: 0 22px 40px rgba(0, 0, 0, 0.4);
    }

    .video-media {
      position: relative;
      aspect-ratio: 16 / 9;
      background: #0b0f1a;
      overflow: hidden;
    }

    .video-poster,
    .video-placeholder {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
    }

    .video-poster {
      object-fit: cover;
      transition: transform 0.4s ease;
    }

    .video-card:hover .video-poster {
      transform: scale(1.03);
    }

    .video-placeholder {
      display: grid;
      place-items: center;
      text-align: center;
      color: rgba(255, 255, 255, 0.7);
      background: linear-gradient(135deg, rgba(57, 211, 255, 0.2), rgba(111, 92, 255, 0.2));
    }

    .video-placeholder svg {
      width: 36px;
      height: 36px;
    }

    .video-placeholder span {
      display: block;
      font-size: 12px;
      margin-top: 6px;
    }

    .video-badge {
      position: absolute;
      top: 12px;
      left: 12px;
      z-index: 2;
      padding: 4px 8px;
      border-radius: 999px;
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 0.3px;
      text-transform: uppercase;
      background: rgba(10, 12, 18, 0.72);
      border: 1px solid rgba(255, 255, 255, 0.12);
      color: #e7e9ef;
    }

    .video-play {
      position: absolute;
      inset: 0;
      z-index: 2;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      background: linear-gradient(0deg, rgba(8, 10, 16, 0.75), rgba(8, 10, 16, 0.1));
      border: 0;
      color: #f8f9fc;
      font-size: 14px;
      font-weight: 600;
      letter-spacing: 0.2px;
      cursor: pointer;
      transition: opacity 0.2s ease, background 0.2s ease;
    }

    .video-play:hover {
      background: linear-gradient(0deg, rgba(8, 10, 16, 0.85), rgba(8, 10, 16, 0.2));
    }

    .video-play:focus-visible {
      outline: none;
      box-shadow: var(--focus);
    }

    .video-play svg {
      width: 20px;
      height: 20px;
    }

    .video-meta {
      padding: 18px 18px 14px;
      display: grid;
      gap: 8px;
    }

    .video-name {
      font-weight: 700;
      font-size: 15px;
      line-height: 1.3;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .video-name-btn {
      background: none;
      border: 0;
      padding: 0;
      margin: 0;
      color: inherit;
      text-align: left;
      cursor: pointer;
    }

    .video-name-btn:focus-visible {
      outline: none;
      box-shadow: var(--focus);
      border-radius: 6px;
    }

    .video-info {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-top: 2px;
      font-size: 12px;
      color: var(--muted);
    }

    .video-meta-actions {
      margin-top: 10px;
    }

    .video-info span {
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .video-player {
      padding: 0 16px 16px;
      display: none;
    }

    .video-player video {
      width: 100%;
      border-radius: var(--radius-md);
      background: #000;
      border: 1px solid rgba(255, 255, 255, 0.08);
    }

    .video-overlay {
      position: fixed;
      inset: 0;
      display: none;
      align-items: stretch;
      justify-content: stretch;
      padding: 0;
      background: rgba(6, 8, 14, 0.9);
      backdrop-filter: blur(10px);
      z-index: 120;
      overflow-y: auto;
    }

    .video-overlay.is-open {
      display: flex;
    }

    .overlay-content {
      width: 100%;
      min-height: 100%;
      background: rgba(12, 16, 26, 0.95);
      border: 0;
      border-radius: 0;
      padding: 24px 22px 32px;
      box-shadow: var(--shadow);
      display: flex;
      flex-direction: column;
      gap: 14px;
      max-height: none;
      overflow: visible;
    }

    .overlay-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      position: sticky;
      top: 0;
      z-index: 2;
      padding: 10px 0 12px;
      background: linear-gradient(180deg, rgba(12, 16, 26, 0.98), rgba(12, 16, 26, 0.8));
    }

    .overlay-title {
      font-size: 20px;
      font-weight: 800;
      letter-spacing: 0.2px;
    }

    .overlay-desc {
      color: var(--muted);
      font-size: 14px;
      line-height: 1.6;
      white-space: pre-wrap;
    }

    .overlay-video {
      width: 100%;
      flex: 1 1 auto;
      min-height: 240px;
      max-height: 68vh;
      border-radius: var(--radius-md);
      background: #000;
      border: 1px solid rgba(255, 255, 255, 0.08);
      object-fit: cover;
    }

    .overlay-video:fullscreen {
      width: 100vw;
      height: 100vh;
      max-height: none;
      border-radius: 0;
      border: 0;
      object-fit: cover;
      background: #000;
    }

    .overlay-video:-webkit-full-screen {
      width: 100vw;
      height: 100vh;
      max-height: none;
      border-radius: 0;
      border: 0;
      object-fit: cover;
      background: #000;
    }

    .overlay-actions {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
    }

    .tv-mode .video-overlay {
      background: #000;
      backdrop-filter: none;
    }

    .tv-mode .overlay-content {
      padding: 0;
      gap: 0;
      height: 100vh;
    }

    .tv-mode .overlay-head,
    .tv-mode .overlay-desc,
    .tv-mode .overlay-actions {
      display: none;
    }

    .tv-mode .overlay-video {
      min-height: 100vh;
      max-height: none;
      height: 100vh;
      border-radius: 0;
      border: 0;
      object-fit: cover;
    }

    .tv-mode {
      background: #0a0c12;
    }


    .tv-mode .topbar,
    .tv-mode .recent-card,
    .tv-mode .video-card,
    .tv-mode .card {
      backdrop-filter: none;
      box-shadow: none;
      transition: none;
    }

    .tv-mode .video-card:hover,
    .tv-mode .recent-card:hover {
      transform: none;
    }

    .tv-mode .video-poster,
    .tv-mode .video-play {
      transition: none;
    }

    .tv-mode .hover-preview {
      display: none;
    }

    @media (prefers-reduced-motion: reduce) {
      * {
        animation: none !important;
        transition: none !important;
        scroll-behavior: auto !important;
      }

      .hover-preview {
        display: none;
      }
    }

    .overlay-close {
      background: rgba(255, 255, 255, 0.08);
    }

    .video-card.is-open .video-player {
      display: block;
      animation: fadeIn 0.2s ease;
    }

    .video-card.is-open .video-play {
      opacity: 0;
      pointer-events: none;
    }

    .video-card.is-open .video-media::after {
      content: "";
      position: absolute;
      inset: 0;
      background: linear-gradient(0deg, rgba(8, 10, 16, 0.3), transparent 60%);
    }

    .video-actions {
      display: grid;
      gap: 10px;
      margin-top: 6px;
      padding: 0 16px 16px;
    }

    .admin-box {
      display: grid;
      gap: 12px;
    }

    .admin-head {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .admin-icon {
      width: 36px;
      height: 36px;
      border-radius: 12px;
      display: grid;
      place-items: center;
      background: rgba(57, 211, 255, 0.16);
      border: 1px solid rgba(57, 211, 255, 0.3);
      color: var(--accent);
    }

    .admin-notes {
      display: grid;
      gap: 6px;
      color: var(--muted);
      font-size: 13px;
    }

    .admin-group {
      display: grid;
      gap: 8px;
      padding-top: 6px;
      border-top: 1px solid rgba(255, 255, 255, 0.06);
    }

    .video-actions .admin-group:first-child {
      border-top: 0;
      padding-top: 0;
    }

    .admin-group-title {
      font-size: 12px;
      letter-spacing: 0.4px;
      text-transform: uppercase;
      color: var(--muted);
    }

    .admin-row {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      align-items: center;
    }

    .admin-row.stack {
      flex-direction: column;
      align-items: stretch;
    }

    .admin-row.stack .btn {
      align-self: flex-start;
    }

    .admin-row .input {
      flex: 1 1 240px;
      min-width: 180px;
    }

    .admin-row .file-btn {
      flex: 1 1 200px;
    }

    .admin-row .btn {
      flex: 0 0 auto;
    }

    .btn {
      padding: 10px 14px;
      border-radius: 999px;
      border: 1px solid rgba(255, 255, 255, 0.14);
      background: rgba(57, 211, 255, 0.16);
      color: var(--text);
      cursor: pointer;
      font-size: 13px;
      font-weight: 600;
      letter-spacing: 0.2px;
      transition: transform 0.12s ease, border-color 0.2s ease, background 0.2s ease, opacity 0.2s ease;
    }

    .btn:hover {
      transform: translateY(-1px);
      border-color: rgba(57, 211, 255, 0.45);
      background: rgba(57, 211, 255, 0.24);
    }

    .btn:focus-visible {
      outline: none;
      box-shadow: var(--focus);
    }

    .btn-secondary {
      background: rgba(255, 255, 255, 0.08);
    }

    .btn-danger {
      background: var(--danger-soft);
      border-color: rgba(255, 107, 122, 0.45);
      color: #ffd2d8;
    }

    .btn-danger:hover {
      background: rgba(255, 107, 122, 0.28);
      border-color: rgba(255, 107, 122, 0.6);
    }

    .btn-full {
      width: 100%;
      justify-content: center;
    }

    .btn[disabled],
    .btn.is-loading {
      opacity: 0.65;
      cursor: progress;
      pointer-events: none;
    }

    .btn.is-loading::after {
      content: "";
      width: 12px;
      height: 12px;
      border-radius: 50%;
      border: 2px solid rgba(255, 255, 255, 0.6);
      border-right-color: transparent;
      display: inline-block;
      margin-left: 8px;
      animation: spin 0.8s linear infinite;
    }

    .input,
    .file-input,
    input[type="text"],
    input[type="password"] {
      width: 100%;
      padding: 10px 12px;
      border-radius: var(--radius-sm);
      border: 1px solid rgba(255, 255, 255, 0.12);
      background: rgba(11, 15, 27, 0.7);
      color: var(--text);
      outline: none;
      transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .textarea {
      min-height: 90px;
      resize: vertical;
    }

    .input:focus,
    .file-input:focus,
    input[type="text"]:focus,
    input[type="password"]:focus {
      border-color: rgba(57, 211, 255, 0.6);
      box-shadow: var(--focus);
    }

    .file-input {
      display: none;
    }

    .file-btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 10px 14px;
      border-radius: 999px;
      border: 1px dashed rgba(255, 255, 255, 0.2);
      color: var(--text);
      background: rgba(255, 255, 255, 0.04);
      cursor: pointer;
      font-size: 13px;
      transition: border-color 0.2s ease, background 0.2s ease;
    }

    .file-btn:hover {
      border-color: rgba(57, 211, 255, 0.5);
      background: rgba(57, 211, 255, 0.12);
    }

    .notice,
    .error {
      padding: 10px 12px;
      border-radius: var(--radius-md);
      font-size: 13px;
    }

    .notice {
      background: rgba(57, 211, 255, 0.16);
      border: 1px solid rgba(57, 211, 255, 0.3);
    }

    .error {
      background: var(--danger-soft);
      border: 1px solid rgba(255, 107, 122, 0.4);
    }

    .empty {
      border-radius: var(--radius-lg);
      border: 1px dashed rgba(255, 255, 255, 0.16);
      background: rgba(13, 18, 32, 0.6);
      padding: 20px;
    }

    .empty-title {
      font-weight: 800;
      margin-bottom: 6px;
    }

    .modal-backdrop {
      position: fixed;
      inset: 0;
      display: none;
      align-items: center;
      justify-content: center;
      padding: 18px;
      background: rgba(6, 8, 12, 0.6);
      backdrop-filter: blur(8px);
      z-index: 9999;
    }

    .modal-backdrop.show {
      display: flex;
    }

    .modal {
      width: min(420px, 100%);
      border-radius: var(--radius-lg);
      background: rgba(18, 24, 38, 0.9);
      border: 1px solid rgba(255, 255, 255, 0.12);
      box-shadow: var(--shadow);
      overflow: hidden;
    }

    .modal-content {
      padding: 20px;
      position: relative;
    }

    .modal h2 {
      font-size: 18px;
      margin-bottom: 12px;
    }

    .close {
      position: absolute;
      right: 14px;
      top: 10px;
      font-size: 24px;
      color: var(--muted);
      cursor: pointer;
    }

    .form-group {
      display: grid;
      gap: 6px;
      margin-bottom: 12px;
    }

    .login-error {
      margin-bottom: 10px;
      padding: 10px 12px;
      border-radius: var(--radius-md);
      background: var(--danger-soft);
      border: 1px solid rgba(255, 107, 122, 0.4);
    }

    label {
      font-size: 12px;
      color: var(--muted);
    }

    .no-js .video-player {
      display: block;
      padding: 0 16px 16px;
    }

    .no-js .video-play {
      display: none;
    }

    @keyframes spin {
      to {
        transform: rotate(360deg);
      }
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(4px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes tv-press {
      0% {
        transform: translateY(0) scale(1);
      }

      35% {
        transform: translateY(1px) scale(0.96);
      }

      70% {
        transform: translateY(-1px) scale(1.03);
      }

      100% {
        transform: translateY(0) scale(1);
      }
    }


    @keyframes tv-toast {
      0% {
        opacity: 0;
        transform: translateY(-8px) scale(0.98);
      }

      15% {
        opacity: 1;
        transform: translateY(0) scale(1);
      }

      70% {
        opacity: 1;
      }

      100% {
        opacity: 0;
        transform: translateY(-6px) scale(0.98);
      }
    }

    @media (max-width: 1100px) {
      .videos-grid {
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 22px;
      }
    }

    @media (max-width: 860px) {
      .topbar {
        padding: 16px 20px;
      }

      .videos-head {
        flex-direction: column;
        align-items: stretch;
      }

      .search-wrap {
        width: 100%;
      }

      .recent-card {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 640px) {
      .topbar {
        flex-direction: column;
        align-items: flex-start;
      }

      .topbar-actions {
        width: 100%;
        justify-content: flex-end;
      }

      .container {
        padding: 18px 14px 48px;
      }

      .videos-title {
        font-size: 22px;
      }

      .video-card {
        border-radius: var(--radius-md);
      }
    }
  </style>
</head>

<body class="no-js">
  <?php include __DIR__ . '/partials/header.php'; ?>
  <div class="container">
    <?php if ($isAdmin): ?>
      <?php include __DIR__ . '/partials/admin_panel.php'; ?>
    <?php endif; ?>

    <?php include __DIR__ . '/partials/videos_list.php'; ?>

  </div>
  <?php if (!$isAdmin): ?>
    <?php include __DIR__ . '/partials/login_form.php'; ?>
  <?php endif; ?>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      document.body.classList.remove('no-js');
      const btn = document.getElementById('openLogin');
      if (btn && typeof openLoginModal === 'function') {
        btn.addEventListener('click', openLoginModal);
      }
    });
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && typeof closeLoginModal === 'function') {
        closeLoginModal();
      }
    });
  </script>

</body>

</html>