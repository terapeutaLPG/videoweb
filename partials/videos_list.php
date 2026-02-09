<?php
$videoDirFs = __DIR__ . '/../videos';
$videos = [];

if (is_dir($videoDirFs)) {
    $videos = glob($videoDirFs . '/*.mp4', GLOB_NOSORT) ?: [];
    $videos = array_values(array_filter($videos, 'is_file'));
}

usort($videos, fn($a, $b) => filemtime($b) <=> filemtime($a));

$thumbMap = is_array($thumbMap ?? null) ? $thumbMap : [];

function buildPublicPath(string $relativePath): string
{
    $relativePath = trim($relativePath, '/');
    $parts = array_map('rawurlencode', explode('/', $relativePath));
    return '/' . implode('/', $parts);
}
?>

<section class="card">
    <h2>Filmy</h2>

    <div class="search-bar">
        <input class="search-input" id="videoSearch" type="text" placeholder="Szukaj filmow..." autocomplete="off">
    </div>

    <?php if (empty($videos)): ?>
        <p class="muted" style="margin-top:10px;">Na razie brak filmow w katalogu /videos.</p>
    <?php else: ?>
        <div class="videos-grid" id="videosGrid">
            <?php foreach ($videos as $videoFs): ?>
                <?php
                $fileName = basename($videoFs);
                $titleRaw = pathinfo($fileName, PATHINFO_FILENAME);
                $displayTitle = trim(str_replace(['_', '-'], ' ', $titleRaw));
                $publicVideo = buildPublicPath('videos/' . $fileName);

                $poster = '';
                if (!empty($thumbMap[$fileName])) {
                    $poster = buildPublicPath($thumbMap[$fileName]);
                }
                ?>
                <div class="video-card" data-title="<?= htmlspecialchars(mb_strtolower($displayTitle)) ?>">
                    <video src="<?= htmlspecialchars($publicVideo) ?>" controls preload="metadata" <?= $poster ? 'poster="' . htmlspecialchars($poster) . '"' : '' ?>></video>
                    <div class="video-card-body">
                        <div class="video-title"><?= htmlspecialchars($displayTitle) ?></div>
                        <div class="video-date"><?= htmlspecialchars(date('Y-m-d H:i', filemtime($videoFs))) ?></div>

                        <?php if (!empty($isAdmin)): ?>
                            <div class="video-actions">
                                <form method="post" class="inline-form">
                                    <input type="hidden" name="action" value="rename_video">
                                    <input type="hidden" name="file" value="<?= htmlspecialchars($fileName) ?>">
                                    <input type="text" name="new_name" placeholder="Nowa nazwa (bez .mp4)" required>
                                    <button type="submit" class="action-link">Zmien nazwe</button>
                                </form>

                                <form method="post" class="inline-form">
                                    <input type="hidden" name="action" value="set_thumb">
                                    <input type="hidden" name="file" value="<?= htmlspecialchars($fileName) ?>">
                                    <input type="text" name="thumb_path" placeholder="Miniaturka, np. thumbs/mini.jpg" required>
                                    <button type="submit" class="action-link">Ustaw miniaturke</button>
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
    const q = searchInput.value.trim().toLowerCase();
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
</script>
