<?php
header('Cache-Control: no-store, no-cache, must-revalidate');
require __DIR__ . '/db.php';
header('Location: /index.php');
exit;
