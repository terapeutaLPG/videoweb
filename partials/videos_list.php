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
        <section class="recent-section" id="recentSection" hidden>
            <div class="recent-head">
                <div>
                    <h2 class="recent-title">Ostatnio ogladany</h2>
                    <div class="recent-sub muted">Wznow od miejsca przerwania.</div>
                </div>
                <button type="button" class="btn btn-secondary" id="recentClear">Wyczysc</button>
            </div>

            <div class="recent-card" id="recentCard" tabindex="0" role="button" aria-label="Wznow ostatni film">
                <div class="recent-thumb" id="recentThumb">
                    <span>Brak miniatury</span>
                </div>
                <div class="recent-body">
                    <div class="recent-name" id="recentTitle"></div>
                    <div class="recent-desc" id="recentDesc"></div>
                    <div class="recent-meta">
                        <span class="pill" id="recentProgress"></span>
                        <span class="muted" id="recentUpdated"></span>
                    </div>
                    <div class="recent-actions">
                        <button type="button" class="btn" id="recentResume">Wznow</button>
                        <button type="button" class="btn btn-secondary" id="recentStartOver">Od poczatku</button>
                    </div>
                </div>
            </div>
        </section>
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
                            <img class="video-poster" src="<?= htmlspecialchars($poster) ?>" alt="<?= htmlspecialchars($title) ?>" loading="lazy" decoding="async" fetchpriority="low">
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
                            preload="none"></video>
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

        <div class="hover-preview" id="hoverPreview" aria-hidden="true">
            <div class="hover-preview-panel">
                <div class="hover-preview-thumb" id="hoverPreviewThumb">
                    <span>Brak miniatury</span>
                </div>
                <div>
                    <div class="hover-preview-title" id="hoverPreviewTitle"></div>
                    <div class="hover-preview-desc" id="hoverPreviewDesc"></div>
                </div>
            </div>
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

        <div class="tv-toast" id="tvToast" role="status" aria-live="polite">
            <div class="tv-toast-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                    <rect x="3" y="6" width="18" height="12" rx="2" />
                    <path d="M8 3l4 3 4-3" />
                </svg>
            </div>
            <div>
                <div class="tv-toast-title">Tryb TV uruchomiony</div>
                <div class="tv-toast-sub">Filmy startuja w pelnym ekranie</div>
            </div>
        </div>

        <script>
            (function() {
                const input = document.getElementById('videoSearch');
                const grid = document.getElementById('videosGrid');
                const noResults = document.getElementById('noResults');
                const recentSection = document.getElementById('recentSection');
                const recentCard = document.getElementById('recentCard');
                const recentThumb = document.getElementById('recentThumb');
                const recentTitle = document.getElementById('recentTitle');
                const recentDesc = document.getElementById('recentDesc');
                const recentProgress = document.getElementById('recentProgress');
                const recentUpdated = document.getElementById('recentUpdated');
                const recentResume = document.getElementById('recentResume');
                const recentStartOver = document.getElementById('recentStartOver');
                const recentClear = document.getElementById('recentClear');
                if (!grid) return;

                const cards = Array.from(grid.querySelectorAll('.video-card'));
                const RECENT_STORAGE_KEY = 'video_recent';
                let currentOverlayData = null;
                let recentData = null;
                let lastSave = 0;

                const prefersReducedMotion = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
                let allowHoverPreview = !prefersReducedMotion;

                if (input) {
                    let searchRaf = 0;
                    const runSearch = () => {
                        const q = input.value.trim().toLowerCase();
                        let visible = 0;

                        cards.forEach(card => {
                            const t = (card.dataset.search || '');
                            const ok = t.includes(q);
                            card.style.display = ok ? '' : 'none';
                            if (ok) visible++;
                        });

                        if (noResults) noResults.style.display = visible === 0 ? '' : 'none';
                    };

                    input.addEventListener('input', function() {
                        if (searchRaf) cancelAnimationFrame(searchRaf);
                        searchRaf = requestAnimationFrame(runSearch);
                    });
                }

                const overlay = document.getElementById('videoOverlay');
                const overlayClose = document.getElementById('overlayClose');
                const overlayTitle = document.getElementById('overlayTitle');
                const overlayDesc = document.getElementById('overlayDesc');
                const overlayVideo = document.getElementById('overlayVideo');
                const overlayFullscreen = document.getElementById('overlayFullscreen');
                const tvToggle = document.getElementById('tvToggle');
                const tvToast = document.getElementById('tvToast');
                const hoverPreview = document.getElementById('hoverPreview');
                const hoverPreviewThumb = document.getElementById('hoverPreviewThumb');
                const hoverPreviewTitle = document.getElementById('hoverPreviewTitle');
                const hoverPreviewDesc = document.getElementById('hoverPreviewDesc');

                const TV_STORAGE_KEY = 'video_tv_mode';
                const baseUrl = window.location.pathname + window.location.search;
                let tvMode = false;
                let overlayOpen = false;
                let tvToastTimer = null;

                const applyTvMode = (enabled) => {
                    tvMode = enabled;
                    allowHoverPreview = !enabled && !prefersReducedMotion;
                    document.body.classList.toggle('tv-mode', enabled);
                    if (tvToggle) {
                        tvToggle.classList.toggle('is-on', enabled);
                        tvToggle.setAttribute('aria-pressed', enabled ? 'true' : 'false');
                    }
                    if (enabled) hideHoverPreview();
                };

                const showTvToast = () => {
                    if (!tvToast) return;
                    tvToast.classList.remove('show');
                    void tvToast.offsetWidth;
                    tvToast.classList.add('show');
                    if (tvToastTimer) clearTimeout(tvToastTimer);
                    tvToastTimer = setTimeout(() => {
                        tvToast.classList.remove('show');
                    }, 2200);
                };

                const animateTvToggle = () => {
                    if (!tvToggle) return;
                    tvToggle.classList.remove('is-pressed');
                    void tvToggle.offsetWidth;
                    tvToggle.classList.add('is-pressed');
                };


                const loadTvMode = () => {
                    let saved = false;
                    try {
                        saved = localStorage.getItem(TV_STORAGE_KEY) === '1';
                    } catch (err) {
                        saved = false;
                    }
                    applyTvMode(saved);
                };


                if (tvToggle) {
                    loadTvMode();
                    tvToggle.addEventListener('click', () => {
                        const next = !tvMode;
                        applyTvMode(next);
                        animateTvToggle();
                        if (next) showTvToast();
                        try {
                            localStorage.setItem(TV_STORAGE_KEY, next ? '1' : '0');
                        } catch (err) {
                            // ignore storage errors
                        }
                    });
                } else {
                    loadTvMode();
                }


                const requestVideoFullscreen = (video) => {
                    if (!video) return;
                    if (video.requestFullscreen) {
                        video.requestFullscreen();
                    } else if (video.webkitRequestFullscreen) {
                        video.webkitRequestFullscreen();
                    } else if (video.webkitEnterFullscreen) {
                        video.webkitEnterFullscreen();
                    } else if (video.msRequestFullscreen) {
                        video.msRequestFullscreen();
                    }
                };

                const closeOverlay = (options = {}) => {
                    const skipHistory = options.skipHistory === true;
                    if (!overlay || !overlayVideo || !overlayOpen) return;
                    saveRecentProgress('close');
                    if (!skipHistory && history.state && history.state.videoOverlay) {
                        history.back();
                        return;
                    }
                    overlayOpen = false;
                    overlay.classList.remove('is-open');
                    overlay.setAttribute('aria-hidden', 'true');
                    overlayVideo.pause();
                    overlayVideo.removeAttribute('src');
                    overlayVideo.removeAttribute('poster');
                    overlayVideo.load();
                };

                const showHoverPreview = (card) => {
                    if (!allowHoverPreview) return;
                    if (!hoverPreview || !hoverPreviewThumb || !hoverPreviewTitle || !hoverPreviewDesc) return;
                    const title = card.dataset.title || '';
                    const desc = card.dataset.desc || '';
                    const poster = card.dataset.poster || '';

                    hoverPreviewTitle.textContent = title;
                    hoverPreviewDesc.textContent = desc || 'Brak opisu.';
                    hoverPreviewThumb.innerHTML = '';

                    if (poster) {
                        const img = document.createElement('img');
                        img.src = poster;
                        img.alt = title;
                        img.loading = 'lazy';
                        img.decoding = 'async';
                        hoverPreviewThumb.appendChild(img);
                    } else {
                        const span = document.createElement('span');
                        span.textContent = 'Brak miniatury';
                        hoverPreviewThumb.appendChild(span);
                    }

                    hoverPreview.classList.add('is-open');
                    hoverPreview.setAttribute('aria-hidden', 'false');
                };

                const hideHoverPreview = () => {
                    if (!hoverPreview || !hoverPreviewThumb) return;
                    hoverPreview.classList.remove('is-open');
                    hoverPreview.setAttribute('aria-hidden', 'true');
                    hoverPreviewThumb.innerHTML = '';
                };

                const formatTime = (seconds) => {
                    if (!Number.isFinite(seconds) || seconds < 0) return '0:00';
                    const total = Math.floor(seconds);
                    const h = Math.floor(total / 3600);
                    const m = Math.floor((total % 3600) / 60);
                    const s = total % 60;
                    if (h > 0) {
                        return `${h}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
                    }
                    return `${m}:${String(s).padStart(2, '0')}`;
                };

                const readRecent = () => {
                    try {
                        const raw = localStorage.getItem(RECENT_STORAGE_KEY);
                        if (!raw) return null;
                        return JSON.parse(raw);
                    } catch (err) {
                        return null;
                    }
                };

                const writeRecent = (data) => {
                    try {
                        localStorage.setItem(RECENT_STORAGE_KEY, JSON.stringify(data));
                    } catch (err) {
                        // ignore storage errors
                    }
                };

                const renderRecent = (data) => {
                    if (!recentSection || !recentThumb || !recentTitle || !recentDesc || !recentProgress || !recentUpdated) return;
                    if (!data || !data.video) {
                        recentSection.setAttribute('hidden', 'hidden');
                        return;
                    }

                    recentTitle.textContent = data.title || 'Bez tytulu';
                    recentDesc.textContent = data.desc || 'Brak opisu.';
                    const current = Number.isFinite(data.currentTime) ? data.currentTime : 0;
                    const duration = Number.isFinite(data.duration) ? data.duration : 0;
                    const progressText = duration > 0 ?
                        `Wznow od ${formatTime(current)} / ${formatTime(duration)}` :
                        `Wznow od ${formatTime(current)}`;
                    recentProgress.textContent = progressText;

                    if (data.updatedAt) {
                        const updated = new Date(data.updatedAt);
                        if (!Number.isNaN(updated.getTime())) {
                            recentUpdated.textContent = `Ostatnio: ${updated.toLocaleString('pl-PL')}`;
                        } else {
                            recentUpdated.textContent = '';
                        }
                    } else {
                        recentUpdated.textContent = '';
                    }

                    recentThumb.innerHTML = '';
                    if (data.poster) {
                        const img = document.createElement('img');
                        img.src = data.poster;
                        img.alt = data.title || 'Miniatura';
                        img.loading = 'lazy';
                        img.decoding = 'async';
                        recentThumb.appendChild(img);
                    } else {
                        const span = document.createElement('span');
                        span.textContent = 'Brak miniatury';
                        recentThumb.appendChild(span);
                    }

                    recentSection.removeAttribute('hidden');
                };

                const clearRecent = () => {
                    recentData = null;
                    try {
                        localStorage.removeItem(RECENT_STORAGE_KEY);
                    } catch (err) {
                        // ignore storage errors
                    }
                    renderRecent(null);
                };

                const saveRecentProgress = (reason) => {
                    if (!currentOverlayData || !overlayVideo) return;
                    const now = Date.now();
                    if (reason !== 'close' && reason !== 'force' && now - lastSave < 2000) return;
                    lastSave = now;
                    let currentTime = overlayVideo.currentTime || 0;
                    const duration = overlayVideo.duration;
                    if (Number.isFinite(duration) && duration > 0 && currentTime / duration > 0.98) {
                        currentTime = 0;
                    }
                    const payload = {
                        ...currentOverlayData,
                        currentTime,
                        duration: Number.isFinite(duration) ? duration : null,
                        updatedAt: now
                    };
                    writeRecent(payload);
                    recentData = payload;
                    renderRecent(payload);
                };

                const openOverlayWithData = (data, options = {}) => {
                    if (!overlay || !overlayVideo || !overlayTitle || !overlayDesc || !data) return;
                    hideHoverPreview();
                    const startAt = Number.isFinite(options.startAt) ? options.startAt : 0;

                    overlayTitle.textContent = data.title || '';
                    overlayDesc.textContent = data.desc || 'Brak opisu.';
                    overlayVideo.src = data.video || '';
                    if (data.poster) {
                        overlayVideo.setAttribute('poster', data.poster);
                    } else {
                        overlayVideo.removeAttribute('poster');
                    }

                    currentOverlayData = {
                        title: data.title || '',
                        desc: data.desc || '',
                        video: data.video || '',
                        poster: data.poster || ''
                    };

                    overlayOpen = true;
                    if (!(history.state && history.state.videoOverlay)) {
                        history.pushState({
                            videoOverlay: true
                        }, '', baseUrl + '#watch');
                    }
                    overlay.classList.add('is-open');
                    overlay.setAttribute('aria-hidden', 'false');
                    overlay.scrollTop = 0;

                    const handleLoaded = () => {
                        if (startAt > 0 && overlayVideo.duration && startAt < overlayVideo.duration - 1) {
                            try {
                                overlayVideo.currentTime = startAt;
                            } catch (err) {
                                // ignore seek errors
                            }
                        }
                        const playPromise = overlayVideo.play();
                        if (playPromise && typeof playPromise.catch === 'function') {
                            playPromise.catch(() => {});
                        }
                    };

                    if (overlayVideo.readyState >= 1) {
                        handleLoaded();
                    } else {
                        overlayVideo.addEventListener('loadedmetadata', handleLoaded, {
                            once: true
                        });
                    }
                };

                const openOverlay = (card) => {
                    if (!card) return;
                    const title = card.dataset.title || '';
                    const desc = card.dataset.desc || '';
                    const videoSrc = card.dataset.video || '';
                    const poster = card.dataset.poster || '';
                    openOverlayWithData({
                        title,
                        desc,
                        video: videoSrc,
                        poster
                    });
                };

                cards.forEach(card => {
                    const playBtn = card.querySelector('.video-play');
                    const titleBtn = card.querySelector('.video-name-btn');
                    const media = card.querySelector('.video-media');
                    let previewTimer = null;

                    if (!card.hasAttribute('tabindex')) {
                        card.setAttribute('tabindex', '0');
                    }

                    const handleOpen = () => openOverlay(card);
                    if (playBtn) playBtn.addEventListener('click', handleOpen);
                    if (titleBtn) titleBtn.addEventListener('click', handleOpen);
                    if (media) {
                        media.addEventListener('click', (e) => {
                            if (e.target && e.target.closest && e.target.closest('.video-play')) return;
                            handleOpen();
                        });
                    }

                    const clearPreview = () => {
                        if (previewTimer) {
                            clearTimeout(previewTimer);
                            previewTimer = null;
                        }
                        card.classList.remove('is-preview');
                        hideHoverPreview();
                    };

                    const schedulePreview = () => {
                        if (!allowHoverPreview) return;
                        if (previewTimer) return;
                        previewTimer = setTimeout(() => {
                            card.classList.add('is-preview');
                            showHoverPreview(card);
                            previewTimer = null;
                        }, 4000);
                    };

                    if (media) {
                        media.addEventListener('pointerenter', schedulePreview);
                        media.addEventListener('pointerleave', clearPreview);
                        media.addEventListener('pointerdown', clearPreview);
                    }
                    card.addEventListener('pointerdown', clearPreview);
                    card.addEventListener('focusin', () => {
                        if (overlayOpen) return;
                        showHoverPreview(card);
                    });
                    card.addEventListener('focusout', clearPreview);

                    card.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter' || e.key === ' ') {
                            e.preventDefault();
                            handleOpen();
                        }
                    });
                });

                if (overlayClose) overlayClose.addEventListener('click', closeOverlay);
                if (overlay) {
                    overlay.addEventListener('click', (e) => {
                        if (e.target === overlay) closeOverlay();
                    });
                }

                window.addEventListener('popstate', () => {
                    if (overlayOpen) closeOverlay({
                        skipHistory: true
                    });
                });

                if (overlayFullscreen && overlayVideo) {
                    overlayFullscreen.addEventListener('click', () => {
                        requestVideoFullscreen(overlayVideo);
                    });
                }

                if (overlayVideo) {
                    overlayVideo.addEventListener('timeupdate', () => saveRecentProgress('time'));
                    overlayVideo.addEventListener('pause', () => saveRecentProgress('pause'));
                    overlayVideo.addEventListener('ended', () => saveRecentProgress('ended'));
                }

                if (recentResume) {
                    recentResume.addEventListener('click', (e) => {
                        e.preventDefault();
                        if (!recentData) return;
                        openOverlayWithData(recentData, {
                            startAt: recentData.currentTime || 0
                        });
                    });
                }

                if (recentStartOver) {
                    recentStartOver.addEventListener('click', (e) => {
                        e.preventDefault();
                        if (!recentData) return;
                        openOverlayWithData(recentData, {
                            startAt: 0
                        });
                    });
                }

                if (recentCard) {
                    recentCard.addEventListener('click', () => {
                        if (!recentData) return;
                        openOverlayWithData(recentData, {
                            startAt: recentData.currentTime || 0
                        });
                    });
                    recentCard.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter' || e.key === ' ') {
                            e.preventDefault();
                            if (!recentData) return;
                            openOverlayWithData(recentData, {
                                startAt: recentData.currentTime || 0
                            });
                        }
                    });
                }

                if (recentClear) {
                    recentClear.addEventListener('click', (e) => {
                        e.preventDefault();
                        clearRecent();
                    });
                }

                window.addEventListener('pagehide', () => saveRecentProgress('close'));

                recentData = readRecent();
                renderRecent(recentData);

                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') closeOverlay();
                });

                const getFocusable = () => {
                    const selector = [
                        'a[href]',
                        'button',
                        'input',
                        'textarea',
                        'select',
                        '[tabindex]:not([tabindex="-1"])'
                    ].join(',');
                    return Array.from(document.querySelectorAll(selector))
                        .filter(el => !el.hasAttribute('disabled') && el.offsetParent !== null);
                };

                const isTypingTarget = (el) => {
                    if (!el) return false;
                    const tag = el.tagName;
                    return el.isContentEditable || tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT';
                };

                document.addEventListener('keydown', (e) => {
                    if (!['ArrowDown', 'ArrowUp', 'ArrowLeft', 'ArrowRight'].includes(e.key)) return;
                    if (isTypingTarget(e.target) || e.target.tagName === 'VIDEO') return;

                    const items = getFocusable();
                    if (!items.length) return;

                    const current = document.activeElement;
                    let index = items.indexOf(current);
                    if (index === -1) index = 0;

                    if (e.key === 'ArrowDown' || e.key === 'ArrowRight') {
                        index = (index + 1) % items.length;
                    } else {
                        index = (index - 1 + items.length) % items.length;
                    }

                    e.preventDefault();
                    items[index].focus();
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