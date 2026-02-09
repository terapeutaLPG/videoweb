<?php
$dbHost = 'mysql8';
$dbName = '40618186_filmy';
$dbUser = '40618186_filmy';
$dbPass = 'hggCcFbjf1';

$adminUser = 'admin';
$adminPass = 'CtwyMobM@1T5aJ@dPxy$2';

try {
    $pdo = new PDO(
        "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die('Blad polaczenia z baza: ' . htmlspecialchars($e->getMessage()));
}
