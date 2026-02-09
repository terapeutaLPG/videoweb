<?php
$videoDir = __DIR__ . '/../videos';
$videos = [];

if (is_dir($videoDir)) {
    $videos = glob($videoDir . '/*.mp4', GLOB_NOSORT) ?: [];
    $videos = array_filter($videos, 'is_file');
}

usort($videos, function ($a, $b) {
    return filemtime($b) <=> filemtime($a);
});

$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
if ($basePath === '/') {
    $basePath = '';
}
?>

<section class="card">
    <h2>Filmy</h2>
    <div class="search-bar">
        <svg class="search-icon" viewBox="0 0 24 24" aria-hidden="true">
            <path fill="currentColor" d="M21 20l-4.35-4.35a7 7 0 10-1.41 1.41L20 21zM10 16a6 6 0 110-12 6 6 0 010 12z" />
        </svg>
        <input class="search-input" id="videoSearch" type="text" placeholder="Szukaj filmow..." autocomplete="off">
    </div>

    <?php if (empty($videos)): ?>
        <p class="muted">Na razie brak filmow.</p>
    <?php else: ?>
        <div class="videos-grid" id="videosGrid">
            <?php foreach ($videos as $video): ?>
                <?php
                $fileName = basename($video);
                $title = pathinfo($fileName, PATHINFO_FILENAME);
                $publicPath = $basePath . '/videos/' . rawurlencode($fileName);
                $displayTitle = str_replace(['_', '-'], ' ', $title);
                ?>
                <div class="video-card" data-title="<?= htmlspecialchars(strtolower($displayTitle)) ?>">
                    <video src="<?= htmlspecialchars($publicPath) ?>" controls preload="metadata"></video>
                    <div class="video-card-body">
                        <div class="video-title"><?= htmlspecialchars($displayTitle) ?></div>
                        <div class="video-date"><?= htmlspecialchars(date('Y-m-d H:i', filemtime($video))) ?></div>
                        <?php if (!empty($isAdmin)): ?>
                            <div class="video-actions">
                                <form method="post" class="inline-form">
                                    <input type="hidden" name="action" value="rename_video">
                                    <input type="hidden" name="file" value="<?= htmlspecialchars($fileName) ?>">
                                    <input type="text" name="new_name" placeholder="Nowa nazwa" class="search-input" style="margin-top:8px;" required>
                                    <button type="submit" class="action-link">Zmien nazwe</button>
                                </form>
                                <form method="post" class="inline-form" onsubmit="return confirm('Usunac ten film?');">
                                    <input type="hidden" name="action" value="delete_video">
                                    <input type="hidden" name="file" value="<?= htmlspecialchars($fileName) ?>">
                                    <button type="submit" class="action-link">Usun</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <p id="noResults" class="muted" style="display:none; margin-top:12px;">Brak wynikow.</p>
    <?php endif; ?>
</section>

<script>
const searchInput = document.getElementById('videoSearch');
const grid = document.getElementById('videosGrid');
const noResults = document.getElementById('noResults');

if (searchInput && grid) {
    const cards = Array.from(grid.querySelectorAll('.video-card'));
    searchInput.addEventListener('input', () => {
        const query = searchInput.value.trim().toLowerCase();
        let visibleCount = 0;
        cards.forEach(card => {
            const title = card.dataset.title || '';
            const isVisible = title.includes(query);
            card.style.display = isVisible ? '' : 'none';
            if (isVisible) visibleCount += 1;
        });
        if (noResults) {
            noResults.style.display = visibleCount === 0 ? '' : 'none';
        }
    });
}
</script>