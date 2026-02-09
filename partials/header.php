<header class="topbar">
    <div class="topbar-title">Moja Walentynka</div>

    <div class="topbar-actions">
        <?php if (!empty($isAdmin)): ?>
            <a href="?logout=1" class="topbar-link">Wyloguj</a>
        <?php else: ?>
            <button type="button" id="openLogin" class="topbar-link">Zaloguj</button>
        <?php endif; ?>
    </div>
</header>
