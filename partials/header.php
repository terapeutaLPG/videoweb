<header class="topbar">
    <div class="topbar-title">
        Filmy PL
        <?php if (!empty($isAdmin)): ?>
            <span class="admin-badge">ADMIN</span>
        <?php endif; ?>
    </div>
    <div class="topbar-actions">
        <?php if (!empty($isAdmin)): ?>
            <a href="/logout.php" class="topbar-link">Wyloguj</a>
        <?php else: ?>
            <a href="#login" class="topbar-link">Zaloguj</a>
        <?php endif; ?>
    </div>
</header>
