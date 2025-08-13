<?php
// Include the database configuration
require_once 'db_config.php';

// Set the correct header for XML output
header('Content-Type: application/xml; charset=utf-8');

// --- Dynamic URL Generation ---
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
// Get the path of the directory where the script is running, and remove the script name if present
$path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$baseUrl = $protocol . $host . $path;

// --- XML Output ---

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// 1. Add static pages
$today = date('Y-m-d');
$staticPages = [
    '' // Homepage
];

foreach ($staticPages as $page) {
    echo "  <url>\n";
    echo "    <loc>" . $baseUrl . $page . "</loc>\n";
    echo "    <lastmod>" . $today . "</lastmod>\n";
    echo "  </url>\n";
}

// 2. Add dynamic tool pages from the database
try {
    $stmt = $pdo->query("SELECT slug, created_at FROM tools ORDER BY created_at DESC");
    $tools = $stmt->fetchAll();

    foreach ($tools as $tool) {
        $toolUrl = $baseUrl . '/tools/' . htmlspecialchars($tool['slug']) . '/';
        // Format the created_at timestamp to Y-m-d format for lastmod
        $lastMod = date('Y-m-d', strtotime($tool['created_at']));

        echo "  <url>\n";
        echo "    <loc>" . $toolUrl . "</loc>\n";
        echo "    <lastmod>" . $lastMod . "</lastmod>\n";
        echo "  </url>\n";
    }

} catch (\PDOException $e) {
    // If the database fails, the sitemap will be incomplete but the script won't crash.
    // In a production environment, you would log this error.
    // For now, we just output a comment in the XML for debugging.
    echo "<!-- Database query failed: " . $e->getMessage() . " -->\n";
}


echo '</urlset>';
?>
