<?php
$actionMsg = $actionMsg ?? '';
$actionErr = $actionErr ?? '';
?>

<section class="card">
    <h2 class="section-title">Panel admina</h2>
    <p class="muted">Aby dodac film, wrzuc plik MP4 do katalogu <b>/videos</b> przez FTP.</p>
    <p class="muted">Na kartach filmow ponizej masz opcje: zmiana nazwy, ustawienie miniaturki, usuwanie.</p>
    <p class="muted">Miniaturki: JPG/PNG/WEBP (upload przy filmie).</p>

    <?php if (!empty($actionMsg)): ?>
        <div class="notice"><?= htmlspecialchars($actionMsg) ?></div>
    <?php endif; ?>
    <?php if (!empty($actionErr)): ?>
        <div class="error"><?= htmlspecialchars($actionErr) ?></div>
    <?php endif; ?>
</section>