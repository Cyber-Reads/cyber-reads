<?php
session_start();
include 'header.php'; // [[17]]
require '../db_connection.php'; // [[11]]

$error = '';
$success = '';

// Add new category
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_category'])) {
    $category_name = trim(mysqli_real_escape_string($conn, $_POST['category']));
    
    if (!empty($category_name)) {
        $sql = "INSERT INTO category (category) VALUES ('$category_name')";
        if (mysqli_query($conn, $sql)) {
            $success = "Category added successfully!";
        } else {
            $error = "Error adding category.";
        }
    } else {
        $error = "Category name cannot be empty.";
    }
}

// Delete category
if (isset($_GET['delete'])) {
    $category_id = intval($_GET['delete']);
    
    // Check if category has books
    $check_sql = "SELECT COUNT(*) as count FROM books_has_category WHERE category_categoryid = $category_id";
    $check_result = mysqli_query($conn, $check_sql);
    $result = mysqli_fetch_assoc($check_result);
    
    if ($result['count'] > 0) {
        $error = "Cannot delete category with existing books. Please reassign or delete the books first.";
    } else {
        $delete_sql = "DELETE FROM category WHERE categoryid = $category_id";
        if (mysqli_query($conn, $delete_sql)) {
            $success = "Category deleted successfully!";
        } else {
            $error = "Error deleting category.";
        }
    }
}

// Get all categories
$categories_result = mysqli_query($conn, "SELECT * FROM category ORDER BY category");
$categories = mysqli_fetch_all($categories_result, MYSQLI_ASSOC);
?>
<div class="max-w-4xl mx-auto">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Manage Categories</h1>
        <p class="text-gray-500 mt-1">Add, view, and manage book categories.</p>
    </div>

    <?php if($error): ?>
    <div class="bg-red-50 border-l-4 border-red-500 text-red-800 p-4 rounded-r-lg mb-6" role="alert">
        <p class="font-bold">Error:</p>
        <p><?php echo $error; ?></p>
    </div>
    <?php endif; ?>
    
    <?php if($success): ?>
    <div class="bg-green-50 border-l-4 border-green-500 text-green-800 p-4 rounded-r-lg mb-6" role="alert">
        <p class="font-bold">Success:</p>
        <p><?php echo $success; ?></p>
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl shadow-lg p-8 mb-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-6">Add New Category</h2>
        <form method="POST" action="category_manage.php" class="flex gap-4 items-end">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700">Category Name</label>
                <input type="text" name="category" required placeholder="e.g., Fiction, Science, History..." 
                       class="mt-1 block w-full bg-gray-50 border-gray-300 rounded-lg shadow-sm py-2 px-3 focus:outline-none focus:ring-purple-500 focus:border-purple-500 transition">
            </div>
            <div>
                <button type="submit" name="add_category" class="inline-flex justify-center py-2 px-6 border border-transparent shadow-sm font-medium rounded-lg text-white bg-black hover:bg-purple-950 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition">
                    Add Category
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">All Categories</h2>
        </div>
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $category): ?>
                <div class="bg-gray-50 rounded-xl p-4 flex justify-between items-center border border-gray-200 hover:shadow-md transition-shadow">
                    <div>
                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($category['category']); ?></p>
                        <p class="text-sm text-gray-500">ID: <?php echo $category['categoryid']; ?></p>
                    </div>
                    <a href="?delete=<?php echo $category['categoryid']; ?>" onclick="return confirm('Are you sure you want to delete this category?');" class="text-red-500 hover:text-red-700 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </a>
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
include 'footer.php'; // [[12]]
?>