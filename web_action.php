<?php
header('Content-Type: application/json');
header('Cache-Control: no-store');

require __DIR__ . '/db.php';

// Sprawdź czy user jest zalogowany (admin lub zwykły user)
$userId = null;
$userEmail = null;

if (!empty($_SESSION['is_admin'])) {
    // Admin - użyj specjalnego ID lub pobierz z bazy
    $userId = 0; // admin nie ma ID w tabeli users
    $userEmail = 'admin';
} elseif (!empty($_SESSION['user_id'])) {
    $userId = (int)$_SESSION['user_id'];
    $userEmail = $_SESSION['user_email'] ?? '';
}

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';
$filename = trim($data['filename'] ?? '');

function sendJson($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($filename)) sendJson(['error' => 'Brak nazwy pliku'], 400);

switch ($action) {

    case 'like_status':
        if (!$userId) sendJson(['liked' => false, 'count' => getLikeCount($filename)]);
        try {
            $stmt = $pdo->prepare('SELECT id FROM likes WHERE user_id = ? AND video_filename = ?');
            $stmt->execute([$userId, $filename]);
            $total = getLikeCount($filename);
            sendJson(['liked' => (bool)$stmt->fetch(), 'count' => $total]);
        } catch (Exception $e) {
            sendJson(['liked' => false, 'count' => 0]);
        }
        break;

    case 'like':
        if (!$userId) sendJson(['error' => 'Niezalogowany'], 401);
        try {
            $stmt = $pdo->prepare('SELECT id FROM likes WHERE user_id = ? AND video_filename = ?');
            $stmt->execute([$userId, $filename]);
            if ($stmt->fetch()) {
                $pdo->prepare('DELETE FROM likes WHERE user_id = ? AND video_filename = ?')->execute([$userId, $filename]);
                $liked = false;
            } else {
                $pdo->prepare('INSERT INTO likes (user_id, video_filename) VALUES (?, ?)')->execute([$userId, $filename]);
                $liked = true;
            }
            sendJson(['liked' => $liked, 'count' => getLikeCount($filename)]);
        } catch (Exception $e) {
            sendJson(['error' => $e->getMessage()], 500);
        }
        break;

    case 'comment':
        if (!$userId) sendJson(['error' => 'Niezalogowany'], 401);
        $content = trim($data['content'] ?? '');
        if (empty($content)) sendJson(['error' => 'Komentarz pusty'], 400);
        if (mb_strlen($content) > 500) sendJson(['error' => 'Max 500 znaków'], 400);
        try {
            $pdo->prepare('INSERT INTO comments (user_id, video_filename, content) VALUES (?, ?, ?)')->execute([$userId, $filename, $content]);
            sendJson(['success' => true]);
        } catch (Exception $e) {
            sendJson(['error' => $e->getMessage()], 500);
        }
        break;

    case 'get_comments':
        try {
            $stmt = $pdo->prepare('SELECT c.id, u.email, c.content, c.created_at FROM comments c JOIN users u ON c.user_id = u.id WHERE c.video_filename = ? ORDER BY c.created_at DESC LIMIT 50');
            $stmt->execute([$filename]);
            sendJson(['comments' => $stmt->fetchAll()]);
        } catch (Exception $e) {
            sendJson(['comments' => []]);
        }
        break;

    default:
        sendJson(['error' => 'Nieznana akcja'], 400);
}

function getLikeCount($filename) {
    global $pdo;
    try {
        $s = $pdo->prepare('SELECT COUNT(*) FROM likes WHERE video_filename = ?');
        $s->execute([$filename]);
        return (int)$s->fetchColumn();
    } catch (Exception $e) { return 0; }
}
