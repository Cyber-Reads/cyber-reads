<?php
// session_start() is now called in each individual page (dashboard.php, book_add.php, etc.) BEFORE including this header.
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("location: ../admin.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="si">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Cyber Reads</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="../assets/img/icon.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: linear-gradient(135deg, #f5f5f5 0%, #fafafa 100%); }
        .sidebar-item:not(.active-link):hover { background: linear-gradient(90deg, #f3f4f6 0%, #f9fafb 100%); }
        .active-link { 
            background: linear-gradient(90deg, #8b5cf6 0%, #a78bfa 100%);
            color: white !important;
        }
        .active-link:hover {
             background: linear-gradient(90deg, #7c3aed 0%, #9d82f7 100%);
        }
    </style>
</head>
<body class="flex h-screen bg-gray-50">
    <!-- Modern Sidebar -->
    <aside class="w-72 bg-white shadow-2xl flex flex-col border-r border-gray-100">
        <div class="p-8 border-b border-gray-100">
            <div class="flex items-center space-x-3">
                <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-purple-600 ...">
    <!-- SVG එක වෙනුවට img tag එක යොදන්න -->
                    <img src="../assets/img/logo1.jpg" alt="Logo" class="w-15 h-15">
                </div>

                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Cyber Reads</h1>
                    <p class="text-xs text-purple-600 font-medium">Admin Dashboard</p>
                </div>
            </div>
        </div>
        
        <nav class="flex-grow p-6 space-y-2">
            <a href="dashboard.php" class="sidebar-item flex items-center px-4 py-3 rounded-xl text-gray-700 transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active-link' : ''; ?>">
                <span class="font-medium">Dashboard</span>
            </a>
            <a href="book_add.php" class="sidebar-item flex items-center px-4 py-3 rounded-xl text-gray-700 transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'book_add.php' ? 'active-link' : ''; ?>">
                <span class="font-medium">Add New Book</span>
            </a>
            <a href="author_manage.php" class="sidebar-item flex items-center px-4 py-3 rounded-xl text-gray-700 transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'author_manage.php' ? 'active-link' : ''; ?>">
                <span class="font-medium">Manage Authors</span>
            </a>
            <a href="category_manage.php" class="sidebar-item flex items-center px-4 py-3 rounded-xl text-gray-700 transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'category_manage.php' ? 'active-link' : ''; ?>">
                <span class="font-medium">Manage Categories</span>
            </a>
        </nav>
        
        <div class="p-6 border-t border-gray-100">
            <div class="bg-gray-50 rounded-xl p-4 mb-4">
                <p class="text-xs text-gray-500">Logged in as</p>
                <p class="text-sm font-semibold text-gray-900 truncate"><?php echo htmlspecialchars($_SESSION['admin_email']); ?></p>
            </div>
            <a href="logout.php" class="flex items-center justify-center w-full px-4 py-3 rounded-xl bg-gradient-to-r from-red-500 to-red-600 text-white font-semibold shadow-lg hover:shadow-red-500/50 transition-shadow">
                Logout
            </a>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="flex-1 overflow-y-auto">
        <div class="p-8">
