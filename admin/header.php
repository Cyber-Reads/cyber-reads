<?php
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("Location: admin.php");
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — Cyber Reads</title>
    <link rel="icon" type="image/png" href="../assets/img/icon.png">
    <link rel="stylesheet" href="../assets/css/tailwind.min.css">
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f5f5f5 0%, #fafafa 100%);
        }
        .sidebar-item:not(.active-link):hover {
            background: linear-gradient(90deg, #f3f4f6 0%, #f9fafb 100%);
        }
        .active-link {
            background: linear-gradient(90deg, #7c3aed 0%, #9333ea 100%);
            color: #ffffff !important;
        }
        .active-link:hover {
            background: linear-gradient(90deg, #6d28d9 0%, #7e22ce 100%);
        }
    </style>
</head>
<body class="flex flex-col lg:flex-row min-h-screen bg-gray-50 text-gray-900">
    <!-- Sidebar / Responsive Header -->
    <aside class="w-full lg:w-72 bg-white shadow-lg lg:shadow-2xl flex flex-col border-b lg:border-b-0 lg:border-r border-gray-200 flex-shrink-0">
        <div class="p-4 sm:p-6 lg:p-8 border-b border-gray-100 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-12 h-12 rounded-xl overflow-hidden bg-gray-100 flex items-center justify-center flex-shrink-0">
                    <img src="../assets/img/logo1.jpg" alt="Cyber Reads Logo" width="48" height="48" class="w-full h-full object-contain">
                </div>
                <div>
                    <h1 class="text-xl lg:text-2xl font-bold text-gray-900">Cyber Reads</h1>
                    <p class="text-xs text-purple-700 font-semibold">Admin Dashboard</p>
                </div>
            </div>
            <a href="../index.html" class="lg:hidden text-xs font-semibold px-3 py-2 rounded-lg bg-gray-100 text-gray-800 hover:bg-gray-200 transition">
                View Site
            </a>
        </div>

        <nav class="flex-grow p-4 lg:p-6 flex lg:flex-col gap-2 overflow-x-auto" aria-label="Admin navigation">
            <a href="dashboard.php" class="sidebar-item whitespace-nowrap flex items-center px-4 py-2.5 lg:py-3 rounded-xl text-gray-700 transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active-link' : ''; ?>">
                <span class="font-medium text-sm lg:text-base">Dashboard</span>
            </a>
            <a href="book_add.php" class="sidebar-item whitespace-nowrap flex items-center px-4 py-2.5 lg:py-3 rounded-xl text-gray-700 transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) === 'book_add.php' ? 'active-link' : ''; ?>">
                <span class="font-medium text-sm lg:text-base">Add New Book</span>
            </a>
            <a href="author_manage.php" class="sidebar-item whitespace-nowrap flex items-center px-4 py-2.5 lg:py-3 rounded-xl text-gray-700 transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) === 'author_manage.php' ? 'active-link' : ''; ?>">
                <span class="font-medium text-sm lg:text-base">Manage Authors</span>
            </a>
            <a href="category_manage.php" class="sidebar-item whitespace-nowrap flex items-center px-4 py-2.5 lg:py-3 rounded-xl text-gray-700 transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) === 'category_manage.php' ? 'active-link' : ''; ?>">
                <span class="font-medium text-sm lg:text-base">Manage Categories</span>
            </a>
            <a href="../index.html" class="hidden lg:flex sidebar-item items-center px-4 py-3 rounded-xl text-gray-700 transition-all duration-200 mt-2 border border-gray-200">
                <span class="font-medium text-sm lg:text-base">&larr; View Public Library</span>
            </a>
        </nav>

        <div class="p-4 lg:p-6 border-t border-gray-100 flex lg:flex-col items-center lg:items-stretch justify-between gap-4">
            <div class="bg-gray-50 rounded-xl px-4 py-2.5 lg:p-4 lg:mb-4 min-w-0 flex-1">
                <p class="text-xs text-gray-500">Logged in as</p>
                <p class="text-sm font-semibold text-gray-900 truncate"><?php echo htmlspecialchars($_SESSION['admin_email'] ?? 'Admin', ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <a href="logout.php" class="flex items-center justify-center px-5 py-2.5 lg:py-3 rounded-xl bg-red-600 hover:bg-red-700 text-white font-semibold shadow-sm transition-colors text-sm lg:text-base">
                Logout
            </a>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="flex-1 overflow-y-auto">
        <div class="p-4 sm:p-6 lg:p-8">
