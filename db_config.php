<?php
// --- Database Configuration ---
define('DB_HOST', 'localhost');
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
    // In a real production environment, you would log this error and
    // show a generic, user-friendly error page.
    // For now, we'll just stop execution and show the error.
    // The user can enable display_errors in index.php to see this.
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>
