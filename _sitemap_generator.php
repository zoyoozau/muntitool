<?php
/**
 * This script generates the sitemap XML content.
 * It is intended to be included by other scripts and not accessed directly.
 */

/**
 * Generates the full sitemap XML as a string.
 * @param PDO $pdo The database connection object.
 * @return string The sitemap XML content.
 */
function generate_sitemap_xml($pdo) {
    // The base URL for the sitemap.
    $baseUrl = 'https://muntitool.com';

    // Start output buffering to capture the XML content
    ob_start();

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
        // For the homepage, the loc should be the base URL with a trailing slash.
        $loc = ($page === '') ? $baseUrl . '/' : $baseUrl . $page;
        echo "    <loc>" . $loc . "</loc>\n";
        echo "    <lastmod>" . $today . "</lastmod>\n";
        echo "  </url>\n";
    }

    // 2. Add dynamic tool pages from the database
    try {
        // Select updated_at as well, which will be used for lastmod if available.
        $stmt = $pdo->query("SELECT slug, created_at, updated_at FROM tools ORDER BY created_at DESC");
        $tools = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($tools as $tool) {
            $toolUrl = $baseUrl . '/tools/' . htmlspecialchars($tool['slug']) . '.html';

            // Use updated_at for lastmod if it's not NULL, otherwise fallback to created_at.
            $lastModTimestamp = !empty($tool['updated_at']) ? $tool['updated_at'] : $tool['created_at'];
            $lastMod = date('Y-m-d', strtotime($lastModTimestamp));

            echo "  <url>\n";
            echo "    <loc>" . $toolUrl . "</loc>\n";
            echo "    <lastmod>" . $lastMod . "</lastmod>\n";
            echo "  </url>\n";
        }

    } catch (\PDOException $e) {
        // In case of a database error, we can log it or handle it.
        // For now, we'll just add a comment to the sitemap for debugging.
        echo "<!-- Database query failed: " . htmlspecialchars($e->getMessage()) . " -->\n";
    }

    echo '</urlset>';

    // Get the content from the buffer and clean it
    $xmlContent = ob_get_clean();

    return $xmlContent;
}
?>
