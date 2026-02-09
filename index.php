<?php
require __DIR__ . '/db.php';
$isAdmin = !empty($_SESSION['is_admin']);

// jedna nazwa sesji wszędzie
#$isAdmin = !empty($_SESSION['is_admin']);
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
      --dangerBd: rgba(239, 68, 68, 0.25);
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
    }

    .topbar-title {
      font-weight: 700;
      font-size: 18px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .admin-badge {
      padding: 2px 10px;
      border-radius: 9999px;
      font-size: 12px;
      background: rgba(34, 197, 94, 0.14);
      border: 1px solid rgba(34, 197, 94, 0.25);
      color: #86efac;
    }

    a.topbar-link {
      color: var(--link);
      text-decoration: none;
      padding: 8px 10px;
      border-radius: 10px;
    }

    a.topbar-link:hover {
      background: var(--linkBg);
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
      outline: none;
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
      background: #000;
    }

    .video-card video {
      width: 100%;
      height: auto;
      display: block;
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
  </style>
</head>

<body>
  <div class="container">
    <?php if ($isAdmin): ?>
      <?php include __DIR__ . '/partials/admin_panel.php'; ?>
    <?php endif; ?>

    <?php include __DIR__ . '/partials/videos_list.php'; ?>

  </div>
  <?php if (!$isAdmin): ?>
    <?php include __DIR__ . '/partials/login_form.php'; ?>
  <?php endif; ?>


</body>

</html>