<?php
// --- Database Configuration ---
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'moo');
define('DB_USER', 'thaisum_moo');
define('DB_PASS', '038382167');
define('DB_CHARSET', 'utf8mb4');

// --- PDO Connection ---
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (\PDOException $e) {
    // This will cause a fatal error, which will lead to a white screen if display_errors is off.
    // The user can add error reporting to the top of the calling script (e.g., upload.php) to debug this.
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
// Note: The closing tag is intentionally omitted. This is a best practice in PHP
// for files that contain only PHP code. It prevents accidental whitespace from
// being sent to the browser and causing "headers already sent" errors.
?>
