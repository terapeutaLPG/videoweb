<?php
require __DIR__ . '/db.php';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Podaj poprawny adres email.';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT id, email FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                $token = bin2hex(random_bytes(32));
                $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
                $pdo->prepare('DELETE FROM password_resets WHERE user_id = ?')->execute([(int) $user['id']]);
                $pdo->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)')->execute([(int) $user['id'], $token, $expiresAt]);
                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? 'mojawalentynkakcc.pl';
                $resetLink = $scheme . '://' . $host . '/reset_password.php?token=' . urlencode($token);

                $subject = 'Reset hasla';
                $body = "Kliknij link aby ustawic nowe haslo:\n\n" . $resetLink . "\n\nLink wygasa za 1 godzine.";
                $headers = "From: noreply@" . $host . "\r\n";
                $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

                @mail($user['email'], $subject, $body, $headers);
            }

            $message = 'Jesli konto istnieje to link zostal wyslany.';
        } catch (PDOException $e) {
            $error = 'Bląd serwera';
        }
    }
}
?>
<!doctype html>
<html lang="pl">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Przypomnij haslo</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #0a0c12;
            color: #e7e9ef;
            font-family: Arial, sans-serif;
        }

        .card {
            width: 100%;
            max-width: 420px;
            background: #111827;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 24px;
        }

        h1 {
            margin: 0 0 8px;
            font-size: 22px;
        }

        p {
            color: #a2a8b8;
            font-size: 14px;
            line-height: 1.6;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-size: 14px;
        }

        input {
            width: 100%;
            box-sizing: border-box;
            padding: 12px;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            background: #0b1220;
            color: #fff;
            font-size: 14px;
        }

        button {
            width: 100%;
            margin-top: 14px;
            padding: 12px;
            border: 0;
            border-radius: 999px;
            background: #39d3ff;
            color: #04111d;
            font-weight: 700;
            cursor: pointer;
        }

        a {
            color: #39d3ff;
        }
    </style>
</head>

<body>
    <div class="card">
        <h1>Przypomnij haslo</h1>
        <p>Podaj email przypisany do konta</p>
        <form method="post">
            <label for="email">Twoj email</label>
            <input type="email" id="email" name="email" required>
            <button type="submit">Wyslij link</button>
        </form>
        <?php if ($message): ?>
            <p style="color:#39d3ff;"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>
        <?php if ($error): ?>
            <p style="color:#ff6b7a;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <p><a href="index.php">Wroc do strony</a></p>
    </div>
</body>

</html>