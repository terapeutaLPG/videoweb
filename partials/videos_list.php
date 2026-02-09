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
                $desc = $videoDescriptions[$file] ?? '';
                ?>
                <article
                    class="video-card"
                    data-search="<?= htmlspecialchars($lower) ?>"
                    data-title="<?= htmlspecialchars($title) ?>"
                    data-desc="<?= htmlspecialchars($desc) ?>"
                    data-video="<?= htmlspecialchars($url) ?>"
                    data-poster="<?= htmlspecialchars($poster) ?>">
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
                                    <div class="admin-group-title">Opis</div>
                                    <div class="admin-row stack">
                                        <input type="hidden" name="action" value="desc">
                                        <input type="hidden" name="file" value="<?= htmlspecialchars($file) ?>">
                                        <textarea name="description" class="input textarea" rows="3" placeholder="Dodaj opis filmu..."><?= htmlspecialchars($desc) ?></textarea>
                                        <button type="submit" class="btn btn-secondary">Zapisz opis</button>
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

        <div class="video-overlay" id="videoOverlay" aria-hidden="true">
            <div class="overlay-content" role="dialog" aria-modal="true" aria-labelledby="overlayTitle">
                <div class="overlay-head">
                    <div class="overlay-title" id="overlayTitle"></div>
                    <button type="button" class="btn overlay-close" id="overlayClose">Powrot</button>
                </div>
                <div class="overlay-desc" id="overlayDesc"></div>
                <video class="overlay-video" id="overlayVideo" controls preload="metadata"></video>
                <div class="overlay-actions">
                    <button type="button" class="btn" id="overlayFullscreen">Pelny ekran</button>
                </div>
            </div>
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
                            const t = (card.dataset.search || '');
                            const ok = t.includes(q);
                            card.style.display = ok ? '' : 'none';
                            if (ok) visible++;
                        });

                        if (noResults) noResults.style.display = visible === 0 ? '' : 'none';
                    });
                }

                const overlay = document.getElementById('videoOverlay');
                const overlayClose = document.getElementById('overlayClose');
                const overlayTitle = document.getElementById('overlayTitle');
                const overlayDesc = document.getElementById('overlayDesc');
                const overlayVideo = document.getElementById('overlayVideo');
                const overlayFullscreen = document.getElementById('overlayFullscreen');

                const closeOverlay = () => {
                    if (!overlay || !overlayVideo) return;
                    overlay.classList.remove('is-open');
                    overlay.setAttribute('aria-hidden', 'true');
                    document.body.classList.remove('is-locked');
                    overlayVideo.pause();
                    overlayVideo.removeAttribute('src');
                    overlayVideo.removeAttribute('poster');
                    overlayVideo.load();
                };

                const openOverlay = (card) => {
                    if (!overlay || !overlayVideo || !overlayTitle || !overlayDesc) return;
                    const title = card.dataset.title || '';
                    const desc = card.dataset.desc || '';
                    const videoSrc = card.dataset.video || '';
                    const poster = card.dataset.poster || '';

                    overlayTitle.textContent = title;
                    overlayDesc.textContent = desc || 'Brak opisu.';
                    overlayVideo.src = videoSrc;
                    if (poster) {
                        overlayVideo.setAttribute('poster', poster);
                    } else {
                        overlayVideo.removeAttribute('poster');
                    }
                    overlay.classList.add('is-open');
                    overlay.setAttribute('aria-hidden', 'false');
                    document.body.classList.add('is-locked');
                    overlayVideo.play();
                };

                cards.forEach(card => {
                    const playBtn = card.querySelector('.video-play');
                    const titleBtn = card.querySelector('.video-name-btn');
                    const media = card.querySelector('.video-media');

                    const handleOpen = () => openOverlay(card);
                    if (playBtn) playBtn.addEventListener('click', handleOpen);
                    if (titleBtn) titleBtn.addEventListener('click', handleOpen);
                    if (media) {
                        media.addEventListener('click', (e) => {
                            if (e.target && e.target.closest && e.target.closest('.video-play')) return;
                            handleOpen();
                        });
                    }
                });

                if (overlayClose) overlayClose.addEventListener('click', closeOverlay);
                if (overlay) {
                    overlay.addEventListener('click', (e) => {
                        if (e.target === overlay) closeOverlay();
                    });
                }

                if (overlayFullscreen && overlayVideo) {
                    overlayFullscreen.addEventListener('click', () => {
                        if (overlayVideo.requestFullscreen) {
                            overlayVideo.requestFullscreen();
                        } else if (overlayVideo.webkitRequestFullscreen) {
                            overlayVideo.webkitRequestFullscreen();
                        } else if (overlayVideo.webkitEnterFullscreen) {
                            overlayVideo.webkitEnterFullscreen();
                        } else if (overlayVideo.msRequestFullscreen) {
                            overlayVideo.msRequestFullscreen();
                        }
                    });
                }

                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') closeOverlay();
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