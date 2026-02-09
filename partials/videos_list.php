<?php
$videoDirFs = __DIR__ . '/../videos';

$videos = [];
if (is_dir($videoDirFs)) {
    $videos = glob($videoDirFs . '/*.mp4') ?: [];
}

usort($videos, function ($a, $b) {
    return filemtime($b) <=> filemtime($a);
});

function video_public_url(string $fileName): string
{
    // spacje i znaki w nazwie pliku
    return '/videos/' . rawurlencode($fileName);
}

function nice_title_from_filename(string $fileName): string
{
    $title = pathinfo($fileName, PATHINFO_FILENAME);
    $title = str_replace(['_', '-'], ' ', $title);
    $title = preg_replace('/\s+/', ' ', $title);
    return trim($title);
}
?>

<section class="videos-section">
    <div class="videos-head">
        <div>
            <h2 class="videos-title">Filmy</h2>
            <div class="videos-sub">
                <span class="pill"><?= count($videos) ?> pozycji</span>
                <span class="dot">•</span>
                <span class="muted">wpisz, żeby filtrować</span>
            </div>
        </div>

        <div class="search-wrap">
            <input
                id="videoSearch"
                class="search-input"
                type="text"
                placeholder="Szukaj po nazwie filmu..."
                autocomplete="off">
        </div>
    </div>

    <?php if (empty($videos)): ?>
        <div class="empty">
            <div class="empty-title">Brak filmów</div>
            <div class="muted">Wrzuć pliki MP4 do folderu <b>/videos</b> przez FTP.</div>
        </div>
    <?php else: ?>
        <div class="videos-grid" id="videosGrid">
            <?php foreach ($videos as $path): ?>
                <?php
                $file = basename($path);
                $title = nice_title_from_filename($file);
                $url = video_public_url($file);
                $lower = mb_strtolower($title);
                $date = date('Y-m-d H:i', filemtime($path));
                ?>
                <article class="video-card" data-title="<?= htmlspecialchars($lower) ?>">
                    <div class="video-media">
                        <video src="<?= htmlspecialchars($url) ?>" controls preload="metadata"></video>
                    </div>

                    <div class="video-meta">
                        <div class="video-name" title="<?= htmlspecialchars($title) ?>">
                            <?= htmlspecialchars($title) ?>
                        </div>

                        <div class="video-info">
                            <span class="muted"><?= htmlspecialchars($date) ?></span>

                            <?php if (!empty($isAdmin)): ?>
                                <span class="dot">•</span>
                                <span class="muted" title="<?= htmlspecialchars($file) ?>">
                                    <?= htmlspecialchars($file) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <div id="noResults" class="empty" style="display:none;">
            <div class="empty-title">Brak wyników</div>
            <div class="muted">Spróbuj krótszej frazy.</div>
        </div>

        <script>
            (function() {
                const input = document.getElementById('videoSearch');
                const grid = document.getElementById('videosGrid');
                const noResults = document.getElementById('noResults');
                if (!input || !grid) return;

                const cards = Array.from(grid.querySelectorAll('.video-card'));

                function applyFilter() {
                    const q = input.value.trim().toLowerCase();
                    let visible = 0;

                    cards.forEach(card => {
                        const t = (card.dataset.title || '');
                        const ok = t.includes(q);
                        card.style.display = ok ? '' : 'none';
                        if (ok) visible++;
                    });

                    if (noResults) noResults.style.display = visible === 0 ? '' : 'none';
                }

                input.addEventListener('input', applyFilter);
            })();
        </script>
    <?php endif; ?>
</section>