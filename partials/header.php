<?php $isAdmin = !empty($isAdmin); ?>
<header class="topbar">
  <div class="topbar-left">
    <div class="topbar-title">
      Filmy PL
      <?php if ($isAdmin): ?>
        <span class="admin-badge">Admin</span>
      <?php endif; ?>
    </div>
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
      <a href="/logout.php" class="topbar-link">Wyloguj</a>
    <?php else: ?>
      <button type="button" id="openLogin" class="topbar-link">Zaloguj</button>
    <?php endif; ?>
  </div>
</header>