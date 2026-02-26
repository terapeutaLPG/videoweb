<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$endpoint = $_GET['endpoint'] ?? '';

function checkAuth()
{
    global $pdo;

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (empty($authHeader)) {
        return false;
    }

    $token = str_replace('Bearer ', '', $authHeader);

    try {
        $stmt = $pdo->prepare("
            SELECT u.* FROM users u
            INNER JOIN auth_tokens t ON u.id = t.user_id
            WHERE t.token = ? AND (t.expires_at IS NULL OR t.expires_at > NOW())
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        return $user ?: false;
    } catch (PDOException $e) {
        return false;
    }
}

function sendJson($data, $status = 200)
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

switch ($endpoint) {
    case 'register':
        if ($method !== 'POST') {
            sendJson(['error' => 'Method not allowed'], 405);
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        if (empty($email) || empty($password)) {
            sendJson(['error' => 'Email i hasło są wymagane'], 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            sendJson(['error' => 'Nieprawidłowy email'], 400);
        }

        if (strlen($password) < 6) {
            sendJson(['error' => 'Hasło musi mieć min. 6 znaków'], 400);
        }

        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                sendJson(['error' => 'Ten email jest już zajęty'], 409);
            }

            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
            $stmt->execute([$email, $hashedPassword]);

            sendJson(['success' => true, 'message' => 'Konto utworzone pomyślnie']);
        } catch (PDOException $e) {
            sendJson(['error' => 'Błąd serwera: ' . $e->getMessage()], 500);
        }
        break;

    case 'login':
        if ($method !== 'POST') {
            sendJson(['error' => 'Method not allowed'], 405);
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        if (empty($email) || empty($password)) {
            sendJson(['error' => 'Email i hasło są wymagane'], 400);
        }

        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password'])) {
                sendJson(['error' => 'Nieprawidłowy email lub hasło'], 401);
            }

            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));

            $stmt = $pdo->prepare("INSERT INTO auth_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$user['id'], $token, $expiresAt]);

            sendJson([
                'success' => true,
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'email' => $user['email']
                ]
            ]);
        } catch (PDOException $e) {
            sendJson(['error' => 'Błąd serwera: ' . $e->getMessage()], 500);
        }
        break;

    case 'logout':
        if ($method !== 'POST') {
            sendJson(['error' => 'Method not allowed'], 405);
        }

        $user = checkAuth();
        if (!$user) {
            sendJson(['error' => 'Unauthorized'], 401);
        }

        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = str_replace('Bearer ', '', $authHeader);

        try {
            $stmt = $pdo->prepare("DELETE FROM auth_tokens WHERE token = ?");
            $stmt->execute([$token]);
            sendJson(['success' => true, 'message' => 'Wylogowano']);
        } catch (PDOException $e) {
            sendJson(['error' => 'Błąd serwera'], 500);
        }
        break;

    case 'videos':
        if ($method !== 'GET') {
            sendJson(['error' => 'Method not allowed'], 405);
        }

        $user = checkAuth();
        if (!$user) {
            sendJson(['error' => 'Unauthorized'], 401);
        }

        try {
            $videos = [];
            $videoDir = __DIR__ . '/videos/';
            $thumbDir = __DIR__ . '/thumbnails/';

            if (!is_dir($videoDir)) {
                sendJson(['videos' => []]);
            }

            $metaData = [];
            $stmt = $pdo->query("SELECT file_name, description FROM video_meta");
            foreach ($stmt->fetchAll() as $row) {
                $metaData[$row['file_name']] = $row['description'];
            }


            foreach (scandir($videoDir) as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'mp4') {
                    $baseName = pathinfo($file, PATHINFO_FILENAME);

                    $thumbnail = null;
                    foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
                        $thumbFile = $baseName . '.' . $ext;
                        if (file_exists($thumbDir . $thumbFile)) {
                            $thumbnail = 'https://' . $_SERVER['HTTP_HOST'] . '/thumbnails/' . $thumbFile;
                            break;
                        }
                    }

                    $videos[] = [
                        'id' => $baseName,
                        'filename' => $file,
                        'name' => $baseName,
                        'description' => $metaData[$file] ?? '',
                        'url' => 'https://' . $_SERVER['HTTP_HOST'] . '/api.php?endpoint=stream&file=' . urlencode($file),
                        'thumbnail' => $thumbnail
                    ];
                }
            }

            sendJson(['videos' => $videos]);
        } catch (Exception $e) {
            sendJson(['error' => 'Błąd: ' . $e->getMessage()], 500);
        }
        break;

    case 'stream':
        $user = checkAuth();
        if (!$user) {
            http_response_code(401);
            die('Unauthorized');
        }

        $filename = $_GET['file'] ?? '';
        if (empty($filename)) {
            http_response_code(400);
            die('Missing file parameter');
        }

        $filepath = __DIR__ . '/videos/' . basename($filename);

        if (!file_exists($filepath)) {
            http_response_code(404);
            die('Video not found');
        }

        $size = filesize($filepath);
        $start = 0;
        $end = $size - 1;

        header('Content-Type: video/mp4');
        header('Accept-Ranges: bytes');

        if (isset($_SERVER['HTTP_RANGE'])) {
            list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
            if (strpos($range, ',') !== false) {
                http_response_code(416);
                header("Content-Range: bytes */$size");
                exit;
            }

            $range = explode('-', $range);
            $start = intval($range[0]);
            $end = isset($range[1]) && is_numeric($range[1]) ? intval($range[1]) : $end;

            if ($start > $end || $start > $size - 1 || $end >= $size) {
                http_response_code(416);
                header("Content-Range: bytes */$size");
                exit;
            }

            http_response_code(206);
            header("Content-Range: bytes $start-$end/$size");
        }

        header("Content-Length: " . ($end - $start + 1));

        $fp = fopen($filepath, 'rb');
        fseek($fp, $start);

        $buffer = 1024 * 8;
        $bytesLeft = $end - $start + 1;

        while ($bytesLeft > 0 && !feof($fp)) {
            $bytesToRead = min($buffer, $bytesLeft);
            echo fread($fp, $bytesToRead);
            flush();
            $bytesLeft -= $bytesToRead;
        }

        fclose($fp);
        exit;
        break;
    case 'like':
        $user = checkAuth();
        if (!$user) sendJson(['error' => 'Unauthorized'], 401);
        $filename = $_GET['file'] ?? '';
        if (empty($filename)) sendJson(['error' => 'Brak nazwy pliku'], 400);
        if ($method === 'POST') {
            try {
                $stmt = $pdo->prepare('SELECT id FROM likes WHERE user_id = ? AND video_filename = ?');
                $stmt->execute([$user['id'], $filename]);
                if ($stmt->fetch()) {
                    $pdo->prepare('DELETE FROM likes WHERE user_id = ? AND video_filename = ?')->execute([$user['id'], $filename]);
                    $liked = false;
                } else {
                    $pdo->prepare('INSERT INTO likes (user_id, video_filename) VALUES (?, ?)')->execute([$user['id'], $filename]);
                    $liked = true;
                }
                $count = $pdo->prepare('SELECT COUNT(*) FROM likes WHERE video_filename = ?');
                $count->execute([$filename]);
                sendJson(['liked' => $liked, 'count' => (int)$count->fetchColumn()]);
            } catch (PDOException $e) {
                sendJson(['error' => $e->getMessage()], 500);
            }
        } elseif ($method === 'GET') {
            try {
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM likes WHERE video_filename = ?');
                $stmt->execute([$filename]);
                $total = (int)$stmt->fetchColumn();
                $stmt2 = $pdo->prepare('SELECT id FROM likes WHERE user_id = ? AND video_filename = ?');
                $stmt2->execute([$user['id'], $filename]);
                sendJson(['liked' => (bool)$stmt2->fetch(), 'count' => $total]);
            } catch (PDOException $e) {
                sendJson(['error' => $e->getMessage()], 500);
            }
        }
        break;

    case 'comments':
        $user = checkAuth();
        if (!$user) sendJson(['error' => 'Unauthorized'], 401);
        $filename = $_GET['file'] ?? '';
        if (empty($filename)) sendJson(['error' => 'Brak nazwy pliku'], 400);
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $content = trim($data['content'] ?? '');
            if (empty($content)) sendJson(['error' => 'Komentarz pusty'], 400);
            if (mb_strlen($content) > 500) sendJson(['error' => 'Max 500 znaków'], 400);
            try {
                $pdo->prepare('INSERT INTO comments (user_id, video_filename, content) VALUES (?, ?, ?)')->execute([$user['id'], $filename, $content]);
                sendJson(['success' => true, 'comment' => ['id' => (int)$pdo->lastInsertId(), 'email' => $user['email'], 'content' => $content, 'created_at' => date('Y-m-d H:i:s')]]);
            } catch (PDOException $e) {
                sendJson(['error' => $e->getMessage()], 500);
            }
        } elseif ($method === 'GET') {
            try {
                $stmt = $pdo->prepare('SELECT c.id, u.email, c.content, c.created_at FROM comments c JOIN users u ON c.user_id = u.id WHERE c.video_filename = ? ORDER BY c.created_at DESC LIMIT 50');
                $stmt->execute([$filename]);
                sendJson(['comments' => $stmt->fetchAll()]);
            } catch (PDOException $e) {
                sendJson(['error' => $e->getMessage()], 500);
            }
        } elseif ($method === 'DELETE') {
            $data = json_decode(file_get_contents('php://input'), true);
            $commentId = (int)($data['id'] ?? 0);
            if (!$commentId) sendJson(['error' => 'Brak ID'], 400);
            try {
                $pdo->prepare('DELETE FROM comments WHERE id = ? AND user_id = ?')->execute([$commentId, $user['id']]);
                sendJson(['success' => true]);
            } catch (PDOException $e) {
                sendJson(['error' => $e->getMessage()], 500);
            }
        }
        break;


    default:
        sendJson(['error' => 'Unknown endpoint', 'available' => ['register', 'login', 'logout', 'videos', 'stream']], 404);
}
