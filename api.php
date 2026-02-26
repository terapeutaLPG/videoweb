<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$endpoint = $_GET['endpoint'] ?? '';

// Autoryzacja JWT/Token
function checkAuth()
{
    $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    // Walidacja tokenu z bazy
    return true; // lub false
}

switch ($endpoint) {
    case 'login':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $email = $data['email'] ?? '';
            $password = $data['password'] ?? '';

            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $token = bin2hex(random_bytes(32));
                // Zapisz token w bazie
                echo json_encode(['success' => true, 'token' => $token, 'user' => $user]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Błędne dane']);
            }
        }
        break;

    case 'register':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $email = $data['email'];
            $password = password_hash($data['password'], PASSWORD_BCRYPT);

            $stmt = $pdo->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
            $stmt->execute([$email, $password]);
            echo json_encode(['success' => true]);
        }
        break;

    case 'videos':
        if (!checkAuth()) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            break;
        }

        $videos = [];
        $dir = __DIR__ . '/videos/';
        foreach (scandir($dir) as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'mp4') {
                $videos[] = [
                    'id' => basename($file, '.mp4'),
                    'name' => basename($file, '.mp4'),
                    'url' => 'https://twoja-domena.pl/videos/' . $file,
                    'thumbnail' => 'https://twoja-domena.pl/thumbnails/' . basename($file, '.mp4') . '.jpg'
                ];
            }
        }
        echo json_encode(['videos' => $videos]);
        break;

    case 'stream':
        if (!checkAuth()) {
            http_response_code(401);
            die('Unauthorized');
        }

        $filename = $_GET['file'] ?? '';
        $filepath = __DIR__ . '/videos/' . basename($filename);

        if (file_exists($filepath)) {
            header('Content-Type: video/mp4');
            header('Accept-Ranges: bytes');

            $size = filesize($filepath);
            $start = 0;
            $end = $size - 1;

            // Range header dla streamingu
            if (isset($_SERVER['HTTP_RANGE'])) {
                list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
                list($start, $end_temp) = explode('-', $range);
                $end = $end_temp ?: $end;

                header('HTTP/1.1 206 Partial Content');
                header("Content-Range: bytes $start-$end/$size");
            }

            header("Content-Length: " . ($end - $start + 1));

            $fp = fopen($filepath, 'rb');
            fseek($fp, $start);
            echo fread($fp, $end - $start + 1);
            fclose($fp);
        }
        break;
}
