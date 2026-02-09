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
                <span class="muted">Wpisz, aby filtrowac</span>
            </div>
        </div>

        <div class="search-wrap">
            <div class="search-field">
                <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                    <circle cx="11" cy="11" r="7" />
                    <path d="M20 20l-3.5-3.5" />
                </svg>
                <input
                    id="videoSearch"
                    class="search-input"
                    type="text"
                    placeholder="Szukaj po nazwie filmu..."
                    autocomplete="off">
            </div>
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
                        <div class="video-badge">HD</div>
                        <?php if ($poster): ?>
                            <img class="video-poster" src="<?= htmlspecialchars($poster) ?>" alt="<?= htmlspecialchars($title) ?>">
                        <?php else: ?>
                            <div class="video-placeholder" aria-hidden="true">
                                <div>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true">
                                        <rect x="3" y="5" width="18" height="14" rx="2" />
                                        <path d="M8 15l3-3 3 3 2-2 2 2" />
                                    </svg>
                                    <span>Brak miniatury</span>
                                </div>
                            </div>
                        <?php endif; ?>
                        <button type="button" class="video-play" aria-label="Odtworz">
                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M8 5l11 7-11 7V5z" />
                            </svg>
                            Odtworz
                        </button>
                    </div>

                    <div class="video-meta">
                        <button type="button" class="video-name video-name-btn" title="<?= htmlspecialchars($title) ?>" aria-label="Odtworz: <?= htmlspecialchars($title) ?>">
                            <?= htmlspecialchars($title) ?>
                        </button>

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

                    <div class="video-player">
                        <video
                            src="<?= htmlspecialchars($url) ?>"
                            <?php if ($poster): ?>poster="<?= htmlspecialchars($poster) ?>" <?php endif; ?>
                            controls
                            preload="metadata"></video>
                    </div>

                    <?php if (!empty($isAdmin)): ?>
                        <div class="video-actions">
                            <form method="post" action="/index.php" class="video-form">
                                <div class="admin-group">
                                    <div class="admin-group-title">Zmien nazwe</div>
                                    <div class="admin-row">
                                        <input type="hidden" name="action" value="rename">
                                        <input type="hidden" name="file" value="<?= htmlspecialchars($file) ?>">
                                        <input type="text" name="new_name" class="input" placeholder="Nowa nazwa">
                                        <button type="submit" class="btn">Zmien nazwe</button>
                                    </div>
                                </div>
                            </form>

                            <form method="post" action="/index.php" enctype="multipart/form-data" class="video-form">
                                <div class="admin-group">
                                    <div class="admin-group-title">Miniatura</div>
                                    <div class="admin-row">
                                        <input type="hidden" name="action" value="thumb">
                                        <input type="hidden" name="file" value="<?= htmlspecialchars($file) ?>">
                                        <input type="hidden" name="MAX_FILE_SIZE" value="5242880">
                                        <label class="file-btn">
                                            <input type="file" name="thumb" class="file-input" accept="image/*" required>
                                            Wybierz plik
                                        </label>
                                        <button type="submit" class="btn btn-secondary">Dodaj miniature</button>
                                    </div>
                                </div>
                            </form>

                            <form method="post" action="/index.php" class="video-form">
                                <div class="admin-group">
                                    <div class="admin-group-title">Usun</div>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="file" value="<?= htmlspecialchars($file) ?>">
                                    <button type="submit" class="btn btn-danger btn-full" onclick="return confirm('Usunac plik?')">Usun</button>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>
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
                if (!grid) return;

                const cards = Array.from(grid.querySelectorAll('.video-card'));

                if (input) {
                    input.addEventListener('input', function() {
                        const q = input.value.trim().toLowerCase();
                        let visible = 0;

                        cards.forEach(card => {
                            const t = (card.dataset.title || '');
                            const ok = t.includes(q);
                            card.style.display = ok ? '' : 'none';
                            if (ok) visible++;
                        });

                        if (noResults) noResults.style.display = visible === 0 ? '' : 'none';
                    });
                }

                cards.forEach(card => {
                    const playBtn = card.querySelector('.video-play');
                    const titleBtn = card.querySelector('.video-name-btn');
                    const player = card.querySelector('video');
                    if (!player) return;

                    const openAndPlay = () => {
                        card.classList.add('is-open');
                        player.play();
                    };

                    if (playBtn) playBtn.addEventListener('click', openAndPlay);
                    if (titleBtn) titleBtn.addEventListener('click', openAndPlay);
                });

                const forms = document.querySelectorAll('.video-form');
                forms.forEach(form => {
                    form.addEventListener('submit', () => {
                        const btn = form.querySelector('button[type="submit"]');
                        if (!btn) return;
                        btn.classList.add('is-loading');
                        btn.setAttribute('disabled', 'disabled');
                    });
                });
            })();
        </script>
    <?php endif; ?>
</section>