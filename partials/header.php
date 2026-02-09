<header class="topbar">
  <div class="topbar-title">
    Moja Walentynka
    <?php if (!empty($isAdmin)): ?>
      <span class="admin-badge">ADMIN</span>
    <?php endif; ?>
  </div>

  <div class="topbar-actions">
    <?php if (!empty($isAdmin)): ?>
      <a href="/logout.php" class="topbar-link">Wyloguj</a>
    <?php else: ?>
      <button type="button" id="openLogin" class="topbar-link">Zaloguj</button>
    <?php endif; ?>
  </div>
</header>
