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

include 'header.php';
require '../src/config/db_connection.php';

$error = '';
$success = '';

// Add new category
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_category'])) {
    $submitted_token = isset($_POST['csrf_token']) ? (string)$_POST['csrf_token'] : '';
    if (!hash_equals($_SESSION['csrf_token'], $submitted_token)) {
        $error = "Invalid security token. Please try again.";
    } else {
        $category_name = isset($_POST['category']) ? trim((string)$_POST['category']) : '';

        if ($category_name !== '') {
            $stmt = $conn->prepare("INSERT INTO category (category) VALUES (?)");
            if ($stmt) {
                $stmt->bind_param("s", $category_name);
                if ($stmt->execute()) {
                    $success = "Category added successfully!";
                } else {
                    $error = "Error adding category.";
                }
                $stmt->close();
            } else {
                $error = "Error adding category.";
            }
        } else {
            $error = "Category name cannot be empty.";
        }
    }
}

// Delete category
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_category'])) {
    $submitted_token = isset($_POST['csrf_token']) ? (string)$_POST['csrf_token'] : '';
    if (!hash_equals($_SESSION['csrf_token'], $submitted_token)) {
        $error = "Invalid security token. Please try again.";
    } else {
        $category_id = (int)$_POST['delete_category'];
        if ($category_id > 0) {
            $check_stmt = $conn->prepare("SELECT COUNT(*) AS count FROM books_has_category WHERE category_categoryid = ?");
            $check_stmt->bind_param("i", $category_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            $result = $check_result ? $check_result->fetch_assoc() : ['count' => 0];
            $check_stmt->close();

            if ((int)$result['count'] > 0) {
                $error = "Cannot delete category with existing books. Please reassign or delete the books first.";
            } else {
                $del_stmt = $conn->prepare("DELETE FROM category WHERE categoryid = ?");
                $del_stmt->bind_param("i", $category_id);
                if ($del_stmt->execute()) {
                    $success = "Category deleted successfully!";
                } else {
                    $error = "Error deleting category.";
                }
                $del_stmt->close();
            }
        }
    }
}

// Get all categories
$categories_result = mysqli_query($conn, "SELECT categoryid, TRIM(category) AS category FROM category ORDER BY TRIM(category)");
$categories = $categories_result ? mysqli_fetch_all($categories_result, MYSQLI_ASSOC) : [];
?>
<div class="max-w-4xl mx-auto">
    <div class="mb-8">
        <h2 class="text-3xl font-bold text-gray-900">Manage Categories</h2>
        <p class="text-gray-500 mt-1">Add, view, and manage book categories.</p>
    </div>

    <?php if ($error !== ''): ?>
    <div class="bg-red-50 border-l-4 border-red-500 text-red-800 p-4 rounded-r-lg mb-6" role="alert">
        <p class="font-bold">Error:</p>
        <p><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
    <div class="bg-green-50 border-l-4 border-green-500 text-green-800 p-4 rounded-r-lg mb-6" role="status" aria-live="polite">
        <p class="font-bold">Success:</p>
        <p><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl shadow-lg p-6 sm:p-8 mb-8">
        <h3 class="text-xl font-semibold text-gray-800 mb-6">Add New Category</h3>
        <form method="POST" action="category_manage.php" class="flex flex-col sm:flex-row gap-4 sm:items-end">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
            <div class="flex-1">
                <label for="category-name" class="block text-sm font-medium text-gray-700">Category Name <span class="text-red-500">*</span></label>
                <input id="category-name" type="text" name="category" maxlength="100" required placeholder="e.g., Fiction, Science, History..."
                       class="mt-1 block w-full bg-gray-50 border border-gray-300 rounded-lg shadow-sm py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition">
            </div>
            <div>
                <button type="submit" name="add_category" value="1" class="w-full sm:w-auto inline-flex justify-center py-2.5 px-6 border border-transparent shadow-sm font-medium rounded-lg text-white bg-black hover:bg-purple-950 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition">
                    Add Category
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-xl font-semibold text-gray-900">All Categories (<?php echo count($categories); ?>)</h3>
        </div>
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $category): ?>
                <div class="bg-gray-50 rounded-xl p-4 flex justify-between items-center border border-gray-200 hover:shadow-md transition-shadow">
                    <div>
                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($category['category'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <p class="text-sm text-gray-500">ID: <?php echo (int)$category['categoryid']; ?></p>
                    </div>
                    <form method="POST" action="category_manage.php" onsubmit="return confirm('Are you sure you want to delete this category?');">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="delete_category" value="<?php echo (int)$category['categoryid']; ?>">
                        <button type="submit" aria-label="Delete category <?php echo htmlspecialchars($category['category'], ENT_QUOTES, 'UTF-8'); ?>" class="p-2 rounded-lg text-red-600 hover:text-red-800 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-gray-500 col-span-full text-center py-8">No categories found. Add one using the form above.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
mysqli_close($conn);
include 'footer.php';
?>
