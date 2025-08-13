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

// Use regex to find the title. This avoids the need for the DOM extension.
$titleMatch = [];
if (preg_match('/<title>(.*?)<\/title>/is', $htmlContent, $titleMatch)) {
    $toolName = trim($titleMatch[1]);
} else {
    $toolName = 'Untitled Tool';
}

// Use regex to find the meta description.
$descriptionMatch = [];
if (preg_match('/<meta\s+name=["\']description["\']\s+content=["\'](.*?)["\']/is', $htmlContent, $descriptionMatch)) {
    $metaDescription = trim($descriptionMatch[1]);
} else {
    $metaDescription = '';
}
if (empty($metaDescription)) {
    $metaDescription = 'No description provided.';
}

// 4. Generate Slug and Validate Category
$slug = slugify($toolName);
$newFileName = $slug . '.html';
$destinationPath = $uploadDir . $newFileName;

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
    redirect_with_status('error', 'Invalid category selected.');
}

// 5. Check if Tool Exists and Perform DB Operation (Update or Insert)
try {
    // Check for an existing tool with the same slug.
    $stmt = $pdo->prepare("SELECT id FROM tools WHERE slug = :slug");
    $stmt->execute([':slug' => $slug]);
    $existingTool = $stmt->fetch(PDO::FETCH_ASSOC);

    $successMessage = '';

    if ($existingTool) {
        // --- UPDATE EXISTING TOOL ---
        $sql = "UPDATE tools SET name = :name, description = :description, category = :category WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':name' => $toolName,
            ':description' => $metaDescription,
            ':category' => $category,
            ':id' => $existingTool['id']
        ]);
        $successMessage = "เครื่องมือได้รับการอัปเดตเรียบร้อยแล้ว (Tool has been updated successfully!)";

    } else {
        // --- INSERT NEW TOOL ---
        $sql = "INSERT INTO tools (name, slug, path, description, category) VALUES (:name, :slug, :path, :description, :category)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':name' => $toolName,
            ':slug' => $slug,
            ':path' => $destinationPath,
            ':description' => $metaDescription,
            ':category' => $category,
        ]);
        $successMessage = "เครื่องมือใหม่ถูกอัปโหลดเรียบร้อยแล้ว (New tool has been uploaded successfully!)";
    }

    // 6. Move the uploaded file (overwrite if exists)
    if (!move_uploaded_file($file['tmp_name'], $destinationPath)) {
        // This is unlikely but possible if permissions are wrong.
        // We don't rollback the DB change here, as the record is valid.
        // The user can just re-upload the file.
        redirect_with_status('error', 'Database was updated, but failed to move the tool file.');
    }

    // 7. Generate and save the new sitemap
    require_once '_sitemap_generator.php';
    $xmlContent = generate_sitemap_xml($pdo);
    file_put_contents('sitemap.xml', $xmlContent, LOCK_EX);

    // 8. Redirect on Success
    redirect_with_status('success', $successMessage);

} catch (PDOException $e) {
    // If any database operation fails, show a generic error.
    redirect_with_status('error', 'Database error: ' . $e->getMessage());
}
?>
