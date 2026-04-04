<?php
require __DIR__ . '/db.php';

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$error = '';
$success = '';
$validToken = false;
$userId = 0;
if ($token === '') {
    $error = 'Brak tokenu resetu.';
} else {
    try {
        $stmt = $pdo->prepare('SELECT user_id FROM password_resets WHERE token = ? AND expires_at > NOW() LIMIT 1');
        $stmt->execute([$token]);
        $reset = $stmt->fetch();

        if ($reset) {
            $validToken = true;
            $userId = (int) $reset['user_id'];
        } else {
            $error = 'Link jest nieprawidlowy albo wygasl.';
        }
    } catch (PDOException $e) {
        $error = 'Blad serwera.';
    }
}
?>
<!doctype html>
<html lang="pl">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ustaw nowe haslo</title>
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
            padding: 24px;
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
            margin-bottom: 12px;
        }

        button {
            width: 100%;
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
        <h1>Ustaw nowe haslo</h1>
        <?php if ($error): ?>
            <p style="color:#ff6b7a;"><?= htmlspecialchars($error) ?></p>
        <?php elseif ($validToken): ?>
            <p>Wpisz nowe haslo do swojego konta</p>
            <form method="post">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                <label for="password">Nowe haslo</label>
                <input type="password" id="password" name="password" required minlength="6" autocomplete="new-password">
                <label for="password2">Powtorz nowe haslo</label>
                <input type="password" id="password2" name="password2" required minlength="6" autocomplete="new-password">
                <button type="submit">Zmien haslo</button>
            </form>
        <?php endif; ?>

        <p><a href="index.php">Wroc do strony</a></p>
    </div>
</body>

</html>