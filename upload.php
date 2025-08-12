<?php

// --- Configuration ---
$uploadDir = 'tools/';
$toolsJsonPath = 'tools.json';
$sitemapPath = 'sitemap.xml';
$allowedMimeType = 'text/html';
$maxFileSize = 2 * 1024 * 1024; // 2 MB

// --- Helper Functions ---

/**
 * Redirects the user with a status message.
 * @param string $status 'success' or 'error'
 * @param string $message The message to display (for errors)
 */
function redirect_with_status($status, $message = '') {
    // On success, redirect to the homepage. On error, redirect back to the upload form.
    $redirectPage = ($status === 'success') ? 'index.html' : 'upload.html';
    $location = $redirectPage . '?status=' . $status;
    if (!empty($message)) {
        $location .= '&message=' . urlencode($message);
    }
    header('Location: ' . $location);
    exit();
}

/**
 * Creates a URL-friendly slug from a string.
 * @param string $text
 * @return string
 */
function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    if (empty($text)) {
        return 'n-a';
    }
    return $text;
}

// --- Main Logic ---

// 1. Check Request and File Upload
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Not a POST request, silently exit or redirect to form
    header('Location: upload.html');
    exit();
}

if (!isset($_FILES['tool_file']) || $_FILES['tool_file']['error'] !== UPLOAD_ERR_OK) {
    redirect_with_status('error', 'File upload failed. Error code: ' . $_FILES['tool_file']['error']);
}

$file = $_FILES['tool_file'];

// 2. Validate File
if ($file['size'] > $maxFileSize) {
    redirect_with_status('error', 'File is too large. Maximum size is 2MB.');
}

// More robust MIME type check
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);
if ($mime !== $allowedMimeType && !str_ends_with($file['name'], '.html') && !str_ends_with($file['name'], '.htm')) {
     redirect_with_status('error', 'Invalid file type. Only HTML files are allowed.');
}


// 3. Read and Parse HTML content
$htmlContent = file_get_contents($file['tmp_name']);
if ($htmlContent === false) {
    redirect_with_status('error', 'Could not read file content.');
}

$doc = new DOMDocument();
// Suppress warnings from malformed HTML
@$doc->loadHTML($htmlContent);

$titleNode = $doc->getElementsByTagName('title')->item(0);
$toolName = $titleNode ? trim($titleNode->nodeValue) : 'Untitled Tool';

$metaDescription = '';
$metaNodes = $doc->getElementsByTagName('meta');
foreach ($metaNodes as $meta) {
    if (strtolower($meta->getAttribute('name')) === 'description') {
        $metaDescription = trim($meta->getAttribute('content'));
        break;
    }
}
if (empty($metaDescription)) {
    $metaDescription = 'No description provided.';
}

// 4. Generate Slug and New File Path
$slug = slugify($toolName);
$newFileName = $slug . '.html';
$destinationPath = $uploadDir . $newFileName;

if (file_exists($destinationPath)) {
    redirect_with_status('error', 'A tool with this name already exists. Please change the <title> of your HTML file.');
}

// 5. Move the Uploaded File
if (!move_uploaded_file($file['tmp_name'], $destinationPath)) {
    redirect_with_status('error', 'Failed to move uploaded file to the tools directory.');
}

// 6. Update tools.json
$toolsData = json_decode(file_get_contents($toolsJsonPath), true);
if ($toolsData === null) {
    // Handle JSON decode error or empty file
    $toolsData = [];
}

$newTool = [
    'name' => $toolName,
    'path' => $destinationPath,
    'description' => $metaDescription,
];
$toolsData[] = $newTool;

// Use flock for safe file writing
$jsonFile = fopen($toolsJsonPath, 'w');
if ($jsonFile && flock($jsonFile, LOCK_EX)) {
    fwrite($jsonFile, json_encode($toolsData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    flock($jsonFile, LOCK_UN);
    fclose($jsonFile);
} else {
    // Could not get a lock or open file, attempt to clean up
    unlink($destinationPath);
    redirect_with_status('error', 'Could not write to tools.json. Please check file permissions.');
}

// 7. Update sitemap.xml
try {
    $sitemap = new DOMDocument();
    $sitemap->preserveWhiteSpace = false;
    $sitemap->formatOutput = true;
    $sitemap->load($sitemapPath);

    $urlset = $sitemap->getElementsByTagName('urlset')->item(0);

    // Determine base URL
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $path = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
    $baseUrl = $protocol . $host . $path;

    $newUrlNode = $sitemap->createElement('url');
    $newUrlNode->appendChild($sitemap->createElement('loc', $baseUrl . '/tool.html?path=' . $destinationPath));
    $newUrlNode->appendChild($sitemap->createElement('lastmod', date('Y-m-d')));

    $urlset->appendChild($newUrlNode);

    $sitemap->save($sitemapPath);
} catch (Exception $e) {
    // Something went wrong with sitemap, this is non-critical but should be noted.
    // For now, we continue but a more robust system might log this error.
    // To be safe, we can redirect with a partial success message.
    redirect_with_status('success', 'Tool uploaded, but sitemap could not be updated. Error: ' . $e->getMessage());
}


// 8. Redirect on Success
redirect_with_status('success');

?>
