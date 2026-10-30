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

$books = [];
$sql = "SELECT bookid, title, cover FROM books ORDER BY bookid DESC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $books[] = $row;
    }
}
$conn->close();
?>

<div class="max-w-7xl mx-auto">
    <!-- Header Section -->
    <div class="mb-8">
        <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-2">Book Management</h2>
        <p class="text-gray-600">Manage Cyber Reads Library Collection</p>
    </div>

    <!-- Status Messages -->
    <?php if (isset($_GET['status'])): ?>
        <?php
        $status = (string)$_GET['status'];
        $borderClass = 'border-green-500';
        $textClass = 'text-green-700';
        $message = '';

        if ($status === 'deleted') {
            $borderClass = 'border-red-500';
            $textClass = 'text-red-700';
            $message = 'Book has been successfully deleted.';
        } elseif ($status === 'updated') {
            $borderClass = 'border-blue-500';
            $textClass = 'text-blue-700';
            $message = 'Book details have been successfully updated.';
        } elseif ($status === 'added') {
            $message = 'New book has been successfully added.';
        } elseif ($status === 'error') {
            $borderClass = 'border-red-500';
            $textClass = 'text-red-700';
            $message = 'Unable to complete the requested operation.';
        }
        ?>
        <?php if ($message !== ''): ?>
            <div class="bg-white p-4 rounded-lg shadow-md mb-6 border-l-4 <?php echo $borderClass; ?>" role="status" aria-live="polite">
                <p class="font-semibold <?php echo $textClass; ?>">
                    <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
                </p>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Books Table -->
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <div class="p-6 border-b border-gray-200 flex flex-wrap gap-4 justify-between items-center">
            <h3 class="text-xl font-semibold text-gray-900">All Books (<?php echo count($books); ?>)</h3>
            <a href="book_add.php" class="px-5 py-2.5 bg-black text-white rounded-xl font-medium hover:bg-purple-950 focus:outline-none focus:ring-2 focus:ring-purple-600 focus:ring-offset-2 transition-colors">
                + Add New Book
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Cover</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Title</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (!empty($books)): ?>
                        <?php foreach ($books as $book): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="w-16 h-24 rounded-lg overflow-hidden shadow-md bg-gray-100">
                                    <img class="w-full h-full object-cover" src="../<?php echo htmlspecialchars($book['cover'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8'); ?> cover" width="64" height="96" loading="lazy" />
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-gray-900 font-medium text-base"><?php echo htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8'); ?></p>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap items-center gap-3">
                                    <a href="book_edit.php?id=<?php echo (int)$book['bookid']; ?>" class="px-4 py-2 bg-blue-100 text-blue-800 rounded-lg font-medium hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">Edit</a>
                                    <form action="book_delete.php" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this book?');">
                                        <input type="hidden" name="id" value="<?php echo (int)$book['bookid']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <button type="submit" class="px-4 py-2 bg-red-100 text-red-800 rounded-lg font-medium hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-red-500 transition-colors">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="text-center py-16">
                                <div class="text-gray-500">
                                    <p class="text-lg font-medium">No books found</p>
                                    <p class="text-sm mt-2">Start by adding your first book</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
