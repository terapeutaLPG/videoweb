<section class="card" id="login">
    <h2>Logowanie</h2>
    <form method="post">
        <input type="hidden" name="action" value="login">
        <div class="field">
            <label for="login-user">Login</label>
            <input type="text" id="login-user" name="login" required>
        </div>
        <div class="field">
            <label for="login-pass">Haslo</label>
            <input type="password" id="login-pass" name="password" required>
        </div>
        <button type="submit" class="btn">Zaloguj</button>
        <?php if (!empty($loginError)): ?>
            <div class="error"><?= htmlspecialchars($loginError) ?></div>
        <?php endif; ?>
    </form>
</section>
