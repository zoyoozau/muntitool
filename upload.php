<?php
// Include the database configuration. This will provide the $pdo object.
require_once 'db_config.php';

// --- Configuration ---
$uploadDir = 'tools/';
$sitemapPath = 'sitemap.xml'; // This file is now virtual, but we might still need the path for logic.
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
    $redirectPage = ($status === 'success') ? 'index.php' : 'upload.html';
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
@$doc->loadHTML('<?xml encoding="utf-8" ?>' . $htmlContent); // Add encoding hint for better parsing

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
    redirect_with_status('error', 'A tool with this name (slug) already exists. Please change the <title> of your HTML file.');
}

// 5. Move the Uploaded File
if (!move_uploaded_file($file['tmp_name'], $destinationPath)) {
    redirect_with_status('error', 'Failed to move uploaded file to the tools directory.');
}

// 6. Validate Category and Insert New Tool into Database
$allowedCategories = [
    'Kids & Education',
    'PDF & Docs',
    'Image & Design',
    'Social & Marketing',
    'Audio & Media',
    'Utilities & Calendars'
];
$category = isset($_POST['category']) ? $_POST['category'] : '';

if (!in_array($category, $allowedCategories)) {
    // If category is invalid, delete the uploaded file and show an error.
    unlink($destinationPath);
    redirect_with_status('error', 'Invalid category selected.');
}

try {
    $sql = "INSERT INTO tools (name, slug, path, description, category) VALUES (:name, :slug, :path, :description, :category)";
    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':name' => $toolName,
        ':slug' => $slug,
        ':path' => $destinationPath,
        ':description' => $metaDescription,
        ':category' => $category,
    ]);
} catch (PDOException $e) {
    // If database insert fails, we should delete the file we just uploaded.
    unlink($destinationPath);
    // Check for unique constraint violation
    if ($e->errorInfo[1] == 1062) {
        redirect_with_status('error', 'A tool with this name (slug) already exists in the database.');
    }
    redirect_with_status('error', 'Database error: ' . $e->getMessage());
}

// The sitemap is now fully dynamic and doesn't need to be updated from here.
// This simplifies the logic and removes a potential point of failure.

// 8. Redirect on Success
redirect_with_status('success', 'Tool uploaded and added to the database successfully!');
?>
