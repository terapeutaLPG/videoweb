<?php
$videoDirFs = __DIR__ . '/../videos';
$thumbDirFs = __DIR__ . '/../thumbnails';
$thumbDirUrl = '/thumbnails';

function find_thumbnail_url(string $fileName, string $thumbDirFs, string $thumbDirUrl): string
{
    $base = pathinfo($fileName, PATHINFO_FILENAME);
    foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
        $fs = $thumbDirFs . '/' . $base . '.' . $ext;
        if (is_file($fs)) {
            return $thumbDirUrl . '/' . rawurlencode($base . '.' . $ext);
        }
    }
    return '';
}

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
                $poster = find_thumbnail_url($file, $thumbDirFs, $thumbDirUrl);
                ?>
                <article class="video-card" data-title="<?= htmlspecialchars($lower) ?>">
                    <div class="video-media">
                        <?php if ($poster): ?>
                            <img class="video-poster" src="<?= htmlspecialchars($poster) ?>" alt="<?= htmlspecialchars($title) ?>">
                        <?php endif; ?>
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

                        <?php if (!empty($isAdmin)): ?>
                            <div class="video-info" style="margin-top:8px;">
                                <form method="post" action="/index.php" style="display:flex; gap:8px; flex-wrap:wrap;">
                                    <input type="hidden" name="action" value="rename">
                                    <input type="hidden" name="file" value="<?= htmlspecialchars($file) ?>">
                                    <input type="text" name="new_name" placeholder="Nowa nazwa" style="flex:1; min-width:140px;">
                                    <button type="submit">Zmień nazwę</button>
                                </form>

                                <form method="post" action="/index.php" style="margin-top:8px;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="file" value="<?= htmlspecialchars($file) ?>">
                                    <button type="submit" onclick="return confirm('Usunąć plik?')">Usuń</button>
                                </form>

                                <form method="post" action="/index.php" enctype="multipart/form-data" style="margin-top:8px;">
                                    <input type="hidden" name="action" value="thumb">
                                    <input type="hidden" name="file" value="<?= htmlspecialchars($file) ?>">
                                    <input type="hidden" name="MAX_FILE_SIZE" value="5242880">
                                    <input type="file" name="thumb" accept="image/*" required>
                                    <button type="submit">Dodaj miniaturę</button>
                                </form>
                            </div>
                        <?php endif; ?>
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

                const medias = document.querySelectorAll('.video-media');
                medias.forEach(media => {
                    const video = media.querySelector('video');
                    const poster = media.querySelector('.video-poster');
                    if (!video || !poster) return;

                    video.addEventListener('play', () => media.classList.add('playing'));
                    video.addEventListener('pause', () => media.classList.remove('playing'));
                    video.addEventListener('ended', () => media.classList.remove('playing'));
                });
            })();
        </script>
    <?php endif; ?>
</section>