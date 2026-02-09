<?php
// folder z filmami (filesystem)
$videoDirFs = __DIR__ . '/../videos';

// pobranie plików mp4
$videos = [];
if (is_dir($videoDirFs)) {
    $videos = glob($videoDirFs . '/*.mp4') ?: [];
}

// sortowanie: najnowsze pierwsze
usort($videos, function ($a, $b) {
    return filemtime($b) <=> filemtime($a);
});

// helper: bezpieczny URL do pliku (spacje, znaki)
function video_public_url(string $fileName): string {
    return '/videos/' . rawurlencode($fileName);
}
?>

<section class="videos-section">
    <h2 class="videos-title">
        Filmy
        <span class="videos-count">(<?= count($videos) ?>)</span>
    </h2>

    <?php if (empty($videos)): ?>
        <p class="videos-empty">Brak filmów w katalogu <b>/videos</b>.</p>
    <?php else: ?>
        <div class="videos-grid">
            <?php foreach ($videos as $path): ?>
                <?php
                $file = basename($path);
                $title = pathinfo($file, PATHINFO_FILENAME);
                $url = video_public_url($file);
                ?>
                <div class="video-card">
                    <video
                        src="<?= htmlspecialchars($url) ?>"
                        controls
                        preload="metadata"
                    ></video>

                    <div class="video-meta">
                        <div class="video-name">
                            <?= htmlspecialchars(str_replace(['_', '-'], ' ', $title)) ?>
                        </div>

                        <?php if (!empty($isAdmin)): ?>
                            <div class="video-admin">
                                plik: <?= htmlspecialchars($file) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
