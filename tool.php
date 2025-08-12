<?php
// Default values
$toolName = 'Tool Not Found';
$toolPath = '';
$errorMessage = '';

// Check if a slug is provided
if (isset($_GET['slug']) && !empty($_GET['slug'])) {
    $slug = $_GET['slug'];
    $toolsJsonPath = 'tools.json';

    if (file_exists($toolsJsonPath)) {
        $toolsJson = file_get_contents($toolsJsonPath);
        $tools = json_decode($toolsJson, true);
        $found = false;

        if (is_array($tools)) {
            foreach ($tools as $tool) {
                // Extract slug from path: "tools/my-slug.html" -> "my-slug"
                $pathSlug = basename($tool['path'], '.html');

                if ($pathSlug === $slug) {
                    $toolName = htmlspecialchars($tool['name']);
                    $toolPath = htmlspecialchars($tool['path']);
                    $found = true;
                    break;
                }
            }
        }

        if (!$found) {
            $errorMessage = 'The requested tool could not be found.';
            http_response_code(404);
        }
    } else {
        $errorMessage = 'Configuration file is missing.';
        http_response_code(500);
    }
} else {
    $errorMessage = 'No tool specified.';
    http_response_code(400);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $toolName; ?> - Muntitool</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            overflow: hidden;
            font-family: sans-serif;
            background-color: #f9fafb; /* gray-50 */
        }
        #loader {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #4a5568; /* gray-700 */
        }
        #loader .fa-spinner {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        #loader p {
            font-size: 1.25rem;
        }
        #tool-frame {
            border: none;
            width: 100%;
            height: 100%;
            visibility: hidden; /* Hide iframe initially */
        }
        .error-message {
            text-align: center;
            padding: 2rem;
            color: #b91c1c; /* red-700 */
        }
    </style>
</head>
<body>

    <?php if (!empty($toolPath)): ?>
        <div id="loader">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Loading Tool...</p>
        </div>
        <iframe id="tool-frame" src="/<?php echo $toolPath; ?>" title="Tool Content"></iframe>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const loader = document.getElementById('loader');
                const toolFrame = document.getElementById('tool-frame');

                toolFrame.addEventListener('load', () => {
                    loader.style.display = 'none';
                    toolFrame.style.visibility = 'visible';
                });
            });
        </script>
    <?php else: ?>
        <div class="error-message">
            <h1>Error</h1>
            <p><?php echo htmlspecialchars($errorMessage); ?></p>
            <p><a href="/" style="color: #2563eb;">Return to Homepage</a></p>
        </div>
    <?php endif; ?>

</body>
</html>
