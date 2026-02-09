<?php
require __DIR__ . '/db.php';
$isAdmin = !empty($_SESSION['is_admin']);
$actionMsg = '';
$actionErr = '';

if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  $file = basename($_POST['file'] ?? '');
  $videoDirFs = __DIR__ . '/videos';
  $thumbDirFs = __DIR__ . '/thumbnails';

  if (!is_dir($thumbDirFs)) {
    @mkdir($thumbDirFs, 0755, true);
  }
  if (!is_dir($thumbDirFs) || !is_writable($thumbDirFs)) {
    $actionErr = 'Brak zapisu do katalogu /thumbnails.';
  } else {
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
          $actionMsg = 'Usunięto plik.';
        } else {
          $actionErr = 'Nie udało się usunąć pliku.';
        }
      } elseif ($action === 'thumb') {
        $f = $_FILES['thumb'] ?? null;
        if (!$f) {
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
  }
}
?>
<!DOCTYPE html>
<html lang="pl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Filmy PL</title>

  <style>
    :root {
      --bg: #0f172a;
      --panel: #0b1224;
      --panel2: #0f172a;
      --text: #e5e7eb;
      --muted: #9ca3af;
      --border: rgba(255, 255, 255, 0.08);
      --link: #93c5fd;
      --linkBg: rgba(147, 197, 253, 0.12);
      --dangerBg: rgba(239, 68, 68, 0.12);
      --dangerBd: rgba(239, 68, 66, 0.25);
      --radius: 14px;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      min-height: 100vh;
      background: var(--bg);
      color: var(--text);
      font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
      letter-spacing: 0.1px;
    }

    .container {
      max-width: 1100px;
      margin: 0 auto;
      padding: 18px 14px 40px;
    }

    /* topbar (jeśli masz własne style w header.php, to one nadal działają) */
    .topbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 16px 24px;
      background: var(--panel);
      border-bottom: 1px solid var(--border);
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.18);
    }

    .topbar-title {
      font-weight: 700;
      font-size: 18px;
      display: flex;
      align-items: center;
      gap: 10px;
      text-transform: none;
    }

    .admin-badge {
      padding: 2px 10px;
      border-radius: 9999px;
      font-size: 12px;
      background: rgba(34, 197, 94, 0.14);
      border: 1px solid rgba(34, 197, 94, 0.25);
      color: #86efac;
    }

    a.topbar-link,
    button.topbar-link {
      color: var(--link);
      text-decoration: none;
      padding: 8px 10px;
      border-radius: 10px;
      border: 1px solid transparent;
    }

    a.topbar-link:hover,
    button.topbar-link:hover {
      background: var(--linkBg);
      border-color: rgba(147, 197, 253, 0.35);
    }

    /* jeśli w header.php masz button zamiast <a>, to ten CSS zachowa wygląd */
    button.topbar-link {
      background: transparent;
      border: 0;
      cursor: pointer;
      font: inherit;
      color: var(--link);
      text-decoration: none;
      padding: 8px 10px;
      border-radius: 10px;
    }

    button.topbar-link:hover {
      background: var(--linkBg);
    }

    /* karty */
    .card {
      background: var(--panel);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 14px;
      margin: 14px 0;
      box-shadow: 0 10px 24px rgba(0, 0, 0, 0.18);
    }

    .muted {
      color: var(--muted);
    }

    /* filmy */
    .videos-section {
      margin-top: 8px;
    }

    .videos-title {
      font-size: 20px;
      margin-bottom: 12px;
      display: flex;
      align-items: baseline;
      gap: 8px;
    }

    .videos-count {
      font-size: 14px;
      color: var(--muted);
    }

    .videos-empty {
      color: var(--muted);
      margin-top: 10px;
    }

    .videos-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
      gap: 16px;
      margin-top: 12px;
    }

    .video-card {
      background: var(--panel2);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      overflow: hidden;
    }

    .video-card video {
      width: 100%;
      height: auto;
      display: block;
      background: #000;
    }

    .video-meta {
      padding: 10px 12px 12px;
    }

    /* tytuł: czytelny, ale nie wielki, dopasowuje się do ekranu */
    .video-name {
      font-weight: 650;
      font-size: clamp(13px, 1.6vw, 15px);
      line-height: 1.25;
      margin-bottom: 6px;

      /* maks 2 linie i utnij */
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
      word-break: break-word;
    }

    .video-admin {
      font-size: 12px;
      color: var(--muted);
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    /* modal logowania (jeśli login_form.php ma modal) */
    .modal-backdrop {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.55);
      display: none;
      align-items: center;
      justify-content: center;
      padding: 16px;
      z-index: 9999;
    }

    .modal-backdrop.show {
      display: flex;
    }

    .modal {
      width: min(420px, 100%);
      background: var(--panel);
      border: 1px solid rgba(255, 255, 255, 0.10);
      border-radius: var(--radius);
      padding: 14px;
      box-shadow: 0 18px 40px rgba(0, 0, 0, 0.35);
    }

    .modal-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 10px;
    }

    .modal-title {
      font-weight: 700;
    }

    .modal-close {
      background: transparent;
      border: 0;
      color: var(--text);
      cursor: pointer;
      font-size: 18px;
      padding: 6px 8px;
    }

    .field {
      margin-top: 10px;
    }

    label {
      display: block;
      font-size: 13px;
      color: #cbd5e1;
      margin-bottom: 6px;
    }

    input {
      width: 100%;
      padding: 10px 12px;
      border-radius: 12px;
      border: 1px solid rgba(255, 255, 255, 0.12);
      background: var(--bg);
      color: var(--text);
      outline: none;
    }

    .btn {
      margin-top: 12px;
      width: 100%;
      padding: 10px 12px;
      border-radius: 12px;
      border: 1px solid rgba(255, 255, 255, 0.12);
      background: var(--linkBg);
      color: var(--text);
      cursor: pointer;
    }

    .btn:hover {
      background: rgba(147, 197, 253, 0.18);
    }

    .error {
      margin-top: 10px;
      padding: 10px;
      border-radius: 12px;
      background: var(--dangerBg);
      border: 1px solid var(--dangerBd);
    }

    @media (max-width: 520px) {
      .topbar {
        padding: 14px 16px;
      }

      .videos-grid {
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
      }
    }

    /* tło i ogólny vibe */
    body {
      background:
        radial-gradient(900px 600px at 15% 10%, rgba(147, 197, 253, 0.18), transparent 55%),
        radial-gradient(900px 600px at 85% 15%, rgba(34, 197, 94, 0.12), transparent 55%),
        radial-gradient(900px 600px at 50% 95%, rgba(244, 114, 182, 0.10), transparent 55%),
        #0f172a;
    }

    /* topbar lekko "szklany" */
    .topbar {
      background: rgba(11, 18, 36, 0.72);
      backdrop-filter: blur(10px);
      position: sticky;
      top: 0;
      z-index: 50;
    }

    /* wspólne drobiazgi */
    .pill {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 6px 10px;
      border-radius: 999px;
      border: 1px solid rgba(255, 255, 255, 0.10);
      background: rgba(255, 255, 255, 0.06);
      font-size: 12px;
      color: #e5e7eb;
    }

    .dot {
      opacity: .55;
      margin: 0 6px;
    }

    .muted {
      color: #9ca3af;
    }

    /* sekcja filmów */
    .videos-section {
      margin-top: 14px;
    }

    .videos-head {
      display: flex;
      align-items: flex-end;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 14px;
    }

    .videos-title {
      font-size: 22px;
      letter-spacing: .2px;
    }

    .videos-sub {
      margin-top: 6px;
      font-size: 13px;
      color: #9ca3af;
    }

    /* search */
    .search-wrap {
      width: min(420px, 100%);
    }

    .search-input {
      width: 100%;
      padding: 10px 12px;
      border-radius: 14px;
      border: 1px solid rgba(255, 255, 255, 0.12);
      background: rgba(15, 23, 42, 0.70);
      color: #e5e7eb;
      transition: border-color .2s ease, box-shadow .2s ease, background .2s ease;
    }

    .search-input:focus {
      border-color: rgba(147, 197, 253, 0.35);
      box-shadow: 0 0 0 3px rgba(147, 197, 253, 0.12);
    }

    /* grid */
    .videos-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
      gap: 16px;
    }

    /* karty */
    .video-card {
      border-radius: 16px;
      overflow: hidden;
      border: 1px solid rgba(255, 255, 255, 0.08);
      background: rgba(11, 18, 36, 0.75);
      transition: transform .15s ease, border-color .15s ease, box-shadow .15s ease;
    }

    .video-card:hover {
      transform: translateY(-2px);
      border-color: rgba(147, 197, 253, 0.20);
      box-shadow: 0 10px 28px rgba(0, 0, 0, 0.28);
    }

    .video-media {
      position: relative;
      background: #000;
    }

    .video-poster {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
      transition: opacity .2s ease;
    }

    .video-media.playing .video-poster {
      opacity: 0;
      pointer-events: none;
    }

    .video-meta {
      padding: 12px 12px 14px;
    }

    .video-name {
      font-weight: 700;
      font-size: clamp(13px, 1.6vw, 15px);
      line-height: 1.25;
      margin-bottom: 8px;

      /* max 2 linie */
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
      word-break: break-word;
    }

    .video-info {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 12px;
      color: #9ca3af;
      overflow: hidden;
    }

    .video-info span {
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    /* empty states */
    .empty {
      border-radius: 16px;
      border: 1px dashed rgba(255, 255, 255, 0.16);
      background: rgba(11, 18, 36, 0.55);
      padding: 18px;
    }

    .empty-title {
      font-weight: 800;
      margin-bottom: 6px;
    }

    /* mobile */
    @media (max-width: 720px) {
      .videos-head {
        flex-direction: column;
        align-items: stretch;
      }

      .search-wrap {
        width: 100%;
      }

      .videos-grid {
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      }
    }

    .modal-backdrop {
      z-index: 999999;
    }

    .modal {
      position: relative;
    }

    /* === premium UI layer (override) === */
    .topbar {
      border-bottom: 1px solid rgba(255, 255, 255, 0.06);
      box-shadow: 0 12px 32px rgba(0, 0, 0, 0.28);
    }

    .topbar-title {
      font-size: 19px;
      letter-spacing: .3px;
    }

    .topbar-sub {
      font-size: 12px;
      color: #9ca3af;
      font-weight: 500;
    }

    .card {
      background: linear-gradient(180deg, rgba(15, 23, 42, .9), rgba(11, 18, 36, .85));
      border: 1px solid rgba(255, 255, 255, .08);
    }

    .videos-grid {
      gap: 18px;
    }

    .video-card {
      border-radius: 18px;
      background: linear-gradient(180deg, rgba(11, 18, 36, .9), rgba(11, 18, 36, .65));
    }

    .video-meta {
      display: grid;
      gap: 8px;
    }

    .video-actions {
      display: grid;
      gap: 8px;
      margin-top: 6px;
    }

    .btn {
      padding: 8px 12px;
      border-radius: 10px;
      border: 1px solid rgba(255, 255, 255, 0.12);
      background: rgba(147, 197, 253, 0.20);
      color: #e5e7eb;
      cursor: pointer;
      transition: transform .12s ease, background .2s ease, border-color .2s ease;
    }

    .btn:hover {
      transform: translateY(-1px);
      background: rgba(147, 197, 253, 0.28);
      border-color: rgba(147, 197, 253, 0.35);
    }

    .btn-secondary {
      background: rgba(255, 255, 255, 0.08);
    }

    .btn-danger {
      background: rgba(239, 68, 68, 0.18);
      border-color: rgba(239, 68, 68, 0.35);
    }

    .input,
    .file-input {
      width: 100%;
      padding: 8px 10px;
      border-radius: 10px;
      border: 1px solid rgba(255, 255, 255, 0.12);
      background: rgba(15, 23, 42, 0.7);
      color: #e5e7eb;
    }

    .modal-backdrop {
      backdrop-filter: blur(6px);
    }

    .modal {
      border-radius: 16px;
    }

    .video-overlay {
      position: absolute;
      inset: 0;
      display: grid;
      place-items: center;
      font-size: 36px;
      color: rgba(255, 255, 255, 0.85);
      background: radial-gradient(circle at center, rgba(0, 0, 0, 0.15), rgba(0, 0, 0, 0.55));
      pointer-events: none;
    }

    .video-media.playing .video-overlay {
      opacity: 0;
    }
  </style>
</head>

<body>
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