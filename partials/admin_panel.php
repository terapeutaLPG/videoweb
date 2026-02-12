<?php
$actionMsg = $actionMsg ?? '';
$actionErr = $actionErr ?? '';
?>

<section class="card admin-box">
    <div class="admin-head">
        <div class="admin-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                <path d="M12 3l7 3v6c0 5-3.5 8-7 9-3.5-1-7-4-7-9V6l7-3z" />
            </svg>
        </div>
        <div>
            <h2 class="section-title">Panel admina</h2>
            <div class="muted">Zarzadzanie biblioteka i miniaturami</div>
        </div>
    </div>

    <div class="admin-notes">
        <div>Aby dodac film, wrzuc plik MP4 do katalogu <b>/videos</b> przez FTP.</div>
        <div>Na kartach filmow ponizej masz opcje: zmiana nazwy, ustawienie miniaturki, usuwanie.</div>
        <div>Opis filmu dodasz bezposrednio przy karcie w sekcji opis.</div>
        <div>Miniaturki: JPG/PNG/WEBP (upload przy filmie).</div>
    </div>

    <?php if (!empty($actionMsg)): ?>
        <div class="notice"><?= htmlspecialchars($actionMsg) ?></div>
    <?php endif; ?>
    <?php if (!empty($actionErr)): ?>
        <div class="error"><?= htmlspecialchars($actionErr) ?></div>
    <?php endif; ?>
</section>