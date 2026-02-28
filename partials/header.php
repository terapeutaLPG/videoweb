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
      <button type="button" id="openLogin" class="topbar-link">Zaloguj</button>
    <?php endif; ?>
  </div>
</header>