<?php
require_once 'db_config.php';

// Fetch all tools from the database
try {
    $stmt = $pdo->query("SELECT name, slug, description FROM tools ORDER BY created_at DESC");
    $tools = $stmt->fetchAll();
} catch (\PDOException $e) {
    // If the database connection fails, we can't show the tools.
    // Show a friendly error message. In a real app, you'd log the error.
    $tools = [];
    $db_error = "Error: Could not connect to the database to fetch tools. Please ensure the database is configured correctly.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Muntitool - A Collection of Awesome Web Tools</title>
    <meta name="description" content="A curated collection of free, powerful, and easy-to-use web tools to boost your productivity.">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        #toast-container {
            position: fixed;
            top: 1.25rem;
            right: 1.25rem;
            z-index: 50;
        }
        .toast {
            display: flex;
            align-items: center;
            padding: 1rem;
            color: white;
            border-radius: 0.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.5s cubic-bezier(0.68, -0.55, 0.27, 1.55);
        }
        .toast.show {
            opacity: 1;
            transform: translateX(0);
        }
        .toast.success { background-color: #22c55e; } /* green-500 */
        .toast.error { background-color: #ef4444; } /* red-500 */
        .toast i {
            font-size: 1.5rem;
            margin-right: 0.75rem;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">

    <div id="toast-container"></div>

    <div class="flex flex-col min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm">
            <div class="container mx-auto px-4 py-6">
                <h1 class="text-3xl font-bold text-gray-900">Muntitool</h1>
                <p class="mt-1 text-gray-600">Your one-stop shop for useful web utilities.</p>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-grow container mx-auto px-4 py-8">
            <h2 class="text-2xl font-semibold mb-6">Available Tools</h2>
            <div id="tools-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php if (isset($db_error)): ?>
                    <p class="text-red-500 col-span-full"><?php echo $db_error; ?></p>
                <?php elseif (empty($tools)): ?>
                    <p class="text-gray-500 col-span-full">No tools available yet. Why not <a href="upload.html" class="text-blue-600 hover:underline">add one</a>?</p>
                <?php else: ?>
                    <?php foreach ($tools as $tool): ?>
                        <a href="tools/<?php echo htmlspecialchars($tool['slug']); ?>/" class="block bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300">
                            <h3 class="text-xl font-semibold text-gray-800"><?php echo htmlspecialchars($tool['name']); ?></h3>
                            <p class="text-gray-600 mt-2"><?php echo htmlspecialchars($tool['description']); ?></p>
                            <div class="mt-4 text-blue-600 hover:underline">
                                Open Tool <i class="fas fa-arrow-right ml-1"></i>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-white mt-8 py-4">
            <div class="container mx-auto px-4 text-center text-gray-500">
                <p>&copy; 2024 Muntitool. All Rights Reserved.</p>
                <a href="upload.html" class="text-xs text-gray-400 hover:text-gray-600 mt-2 inline-block">Admin</a>
            </div>
        </footer>
    </div>

    <script>
        // This script block is now only for the toast notifications.
        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const status = urlParams.get('status');
            const message = urlParams.get('message');

            if (status) {
                const toastContainer = document.getElementById('toast-container');
                const toast = document.createElement('div');
                let iconClass = '';
                let title = '';

                if (status === 'success') {
                    toast.className = 'toast success';
                    iconClass = 'fas fa-check-circle';
                    title = 'Success!';
                } else if (status === 'error') {
                    // Errors are shown on upload.html, but we keep this for flexibility
                    toast.className = 'toast error';
                    iconClass = 'fas fa-exclamation-circle';
                    title = 'Error!';
                }

                const messageText = message || 'Action completed successfully!';
                toast.innerHTML = `<i class="${iconClass}"></i><div><b>${title}</b><p>${messageText}</p></div>`;

                toastContainer.appendChild(toast);

                // Show the toast
                setTimeout(() => {
                    toast.classList.add('show');
                }, 100);

                // Hide the toast after 5 seconds
                setTimeout(() => {
                    toast.classList.remove('show');
                    setTimeout(() => {
                        toast.remove();
                    }, 500);
                }, 5000);

                // Clean the URL
                if (window.history.replaceState) {
                    const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
                    window.history.replaceState({path: cleanUrl}, '', cleanUrl);
                }
            }
        });
    </script>

</body>
</html>
