<section class="card">
    <h2>Panel admina</h2>
    <p class="muted">Zalogowano jako admin.</p>
    <p class="muted">Aby dodac film, wrzuc plik MP4 do katalogu /videos przez FTP.</p>
    <p class="muted">Na kartach filmow ponizej masz opcje edycji nazwy, ustawienia miniaturki i usuwania.</p>

    <?php if (!empty($actionMsg)): ?>
        <div class="notice"><?= htmlspecialchars($actionMsg) ?></div>
    <?php endif; ?>
    <?php if (!empty($actionErr)): ?>
        <div class="error"><?= htmlspecialchars($actionErr) ?></div>
    <?php endif; ?>
</section>
