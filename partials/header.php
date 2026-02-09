<?php $isAdmin = !empty($isAdmin); ?>
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
    <button type="button" id="lightToggle" class="topbar-link" aria-pressed="false" aria-label="Lekki wyglad" title="Lekki wyglad">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
        <circle cx="12" cy="12" r="4" />
        <path d="M12 2v3M12 19v3M4.2 4.2l2.1 2.1M17.7 17.7l2.1 2.1M2 12h3M19 12h3M4.2 19.8l2.1-2.1M17.7 6.3l2.1-2.1" />
      </svg>
      <span class="tv-label">Lekki</span>
    </button>
    <?php if ($isAdmin): ?>
      <a href="/logout.php" class="topbar-link">Wyloguj</a>
    <?php else: ?>
      <button type="button" id="openLogin" class="topbar-link">Zaloguj</button>
    <?php endif; ?>
  </div>
</header>