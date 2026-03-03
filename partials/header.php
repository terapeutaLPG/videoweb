<?php $isAdmin = !empty($isAdmin);
$isUser = !empty($_SESSION['user_email']); ?>
<header class="topbar">
  <div class="topbar-left">
    <a class="topbar-title" href="/index.php" aria-label="Strona glowna">
      Filmy PL
      <?php if ($isAdmin): ?>
        <span class="admin-badge">Admin</span>
      <?php endif; ?>
    </a>
    <div class="topbar-sub">Biblioteka wideo</div>
  </div>

  <div class="topbar-actions">
    <a href="/app-release.apk" download="FilmyPL.apk" class="topbar-link topbar-link--download" title="Pobierz aplikację na Android">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" width="16" height="16">
        <path d="M12 3v13M7 11l5 5 5-5" />
        <path d="M5 21h14" />
      </svg>
      <span>Pobierz apkę</span>
    </a>
    <button type="button" id="tvToggle" class="topbar-link tv-toggle" aria-pressed="false" aria-label="Auto pelny ekran" title="Auto pelny ekran">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
        <rect x="3" y="6" width="18" height="12" rx="2" />
        <path d="M8 3l4 3 4-3" />
      </svg>
      <span class="tv-label">TV</span>
    </button>
    <?php if ($isAdmin): ?>
      <span class="topbar-user muted">Admin</span>
      <a href="/logout.php" class="topbar-link">Wyloguj</a>
    <?php elseif ($isUser): ?>
      <span class="topbar-user muted"><?= htmlspecialchars($_SESSION['user_email']) ?></span>
      <a href="/logout.php" class="topbar-link">Wyloguj</a>
    <?php else: ?>
      <button type="button" onclick="openLoginModal('register')" class="topbar-link topbar-link--register">Zarejestruj</button>
      <button type="button" id="openLogin" onclick="openLoginModal('login')" class="topbar-link">Zaloguj</button>
    <?php endif; ?>
  </div>
</header>

<style>
  .topbar-link--register {
    background: linear-gradient(135deg, rgba(111, 92, 255, 0.22), rgba(57, 211, 255, 0.16));
    border-color: rgba(111, 92, 255, 0.45);
    position: relative;
    overflow: hidden;
  }

  .topbar-link--register::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.07), transparent);
    background-size: 200% auto;
    animation: topbar-shimmer 2.2s linear infinite;
  }

  .topbar-link--register:hover {
    border-color: rgba(57, 211, 255, 0.6);
    box-shadow: 0 0 18px rgba(111, 92, 255, 0.35);
  }

  .topbar-user {
    font-size: 12px;
    padding: 0 4px;
    max-width: 140px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .topbar-link--download {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: linear-gradient(135deg, rgba(80, 220, 170, 0.18), rgba(57, 211, 255, 0.12));
    border-color: rgba(80, 220, 170, 0.4);
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.2px;
    position: relative;
    overflow: hidden;
    animation: apk-pulse 2.5s ease-in-out infinite;
  }

  .topbar-link--download:hover {
    border-color: rgba(80, 220, 170, 0.7);
    box-shadow: 0 0 18px rgba(80, 220, 170, 0.3);
    animation: none;
  }

  @keyframes apk-pulse {

    0%,
    100% {
      box-shadow: 0 0 0 0 rgba(80, 220, 170, 0);
    }

    50% {
      box-shadow: 0 0 10px 2px rgba(80, 220, 170, 0.25);
    }
  }

  @keyframes topbar-shimmer {
    0% {
      background-position: -200% center;
    }

    100% {
      background-position: 200% center;
    }
  }
</style>