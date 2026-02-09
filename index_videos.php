<?php
// katalog z filmami
$videoDir = 'videos';

// pobieramy wszystkie mp4
$videos = glob($videoDir . '/*.mp4');

echo '<h2>Filmy</h2>';

if (!$videos) {
    echo '<p>Na razie brak filmów.</p>';
    return;
}

// sortowanie: najnowsze pierwsze
usort($videos, function ($a, $b) {
    return filemtime($b) - filemtime($a);
});

foreach ($videos as $video) {
    $fileName = basename($video);
    $title = pathinfo($fileName, PATHINFO_FILENAME);

    echo '<div style="margin-bottom:40px;">';
    echo '<h3>' . htmlspecialchars($title) . '</h3>';
    echo '<video src="' . htmlspecialchars($video) . '" controls width="600"></video>';

    // opcje admina
    if (isset($_SESSION['user_id'])) {
        echo '<div style="margin-top:10px; opacity:0.7;">';
        echo 'Plik: ' . htmlspecialchars($fileName);
        echo '</div>';
    }

    echo '</div>';
}
