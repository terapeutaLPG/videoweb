<header class="topbar">
    <div class="topbar-title">Moja Walentynka</div>
    <div class="topbar-actions">
        <?php if (!empty($isAdmin)): ?>
            <a href="?logout=1" class="topbar-link">Wyloguj</a>
        <?php else: ?>
            <a href="?admin=1#login" class="topbar-link">Zaloguj</a>
        <?php endif; ?>
    </div>
</header>
