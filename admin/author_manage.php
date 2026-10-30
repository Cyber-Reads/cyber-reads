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

// Add author
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_author'])) {
    $submitted_token = isset($_POST['csrf_token']) ? (string)$_POST['csrf_token'] : '';
    if (!hash_equals($_SESSION['csrf_token'], $submitted_token)) {
        $error = "Invalid security token. Please try again.";
    } else {
        $fname = isset($_POST['authorfname']) ? trim((string)$_POST['authorfname']) : '';
        $lname = isset($_POST['authorlname']) ? trim((string)$_POST['authorlname']) : '';

        if ($fname !== '' && $lname !== '') {
            $stmt = $conn->prepare("INSERT INTO author (authorfname, authorlname) VALUES (?, ?)");
            if ($stmt) {
                $stmt->bind_param("ss", $fname, $lname);
                if ($stmt->execute()) {
                    $success = "Author added successfully!";
                } else {
                    $error = "Error adding author.";
                }
                $stmt->close();
            } else {
                $error = "Error adding author.";
            }
        } else {
            $error = "First name and last name are required.";
        }
    }
}

// Delete author (supports POST with CSRF token)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_author'])) {
    $submitted_token = isset($_POST['csrf_token']) ? (string)$_POST['csrf_token'] : '';
    if (!hash_equals($_SESSION['csrf_token'], $submitted_token)) {
        $error = "Invalid security token. Please try again.";
    } else {
        $author_id = (int)$_POST['delete_author'];
        if ($author_id > 0) {
            $check_stmt = $conn->prepare("SELECT COUNT(*) AS count FROM books_has_author WHERE author_authorid = ?");
            $check_stmt->bind_param("i", $author_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            $result = $check_result ? $check_result->fetch_assoc() : ['count' => 0];
            $check_stmt->close();

            if ((int)$result['count'] > 0) {
                $error = "Cannot delete author with existing books. Please reassign or delete the books first.";
            } else {
                $del_stmt = $conn->prepare("DELETE FROM author WHERE authorid = ?");
                $del_stmt->bind_param("i", $author_id);
                if ($del_stmt->execute()) {
                    $success = "Author deleted successfully!";
                } else {
                    $error = "Error deleting author.";
                }
                $del_stmt->close();
            }
        }
    }
}

// Get all authors
$authors_result = mysqli_query($conn, "SELECT authorid, authorfname, authorlname FROM author ORDER BY authorfname, authorlname");
$authors = $authors_result ? mysqli_fetch_all($authors_result, MYSQLI_ASSOC) : [];
?>

<div class="max-w-4xl mx-auto">
    <div class="mb-8">
        <h2 class="text-gray-900 text-3xl font-bold">Manage Authors</h2>
        <p class="mt-1 text-gray-500">Add, view, and manage book authors.</p>
    </div>

    <?php if ($error !== ''): ?>
    <div class="bg-red-50 p-4 rounded-r-lg border-l-4 border-red-500 text-red-800 mb-6" role="alert">
        <p class="font-bold">Error:</p>
        <p><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
    <div class="bg-green-50 border-l-4 text-green-800 p-4 rounded-r-lg mb-6 border-green-500" role="status" aria-live="polite">
        <p class="font-bold">Success:</p>
        <p><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
    <?php endif; ?>

    <div class="bg-white shadow-lg p-6 sm:p-8 mb-8 rounded-2xl">
        <h3 class="text-xl font-semibold text-gray-800 mb-6">Add New Author</h3>

        <form method="POST" action="author_manage.php" class="gap-6 grid grid-cols-1 md:grid-cols-3 items-end">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

            <div class="md:col-span-1">
                <label for="authorfname" class="text-gray-700 block text-sm font-medium">First Name <span class="text-red-500">*</span></label>
                <input id="authorfname" type="text" name="authorfname" maxlength="100" required class="border border-gray-300 rounded-lg shadow-sm py-2.5 px-3 mt-1 block w-full bg-gray-50 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition">
            </div>

            <div class="md:col-span-1">
                <label for="authorlname" class="block text-sm font-medium text-gray-700">Last Name <span class="text-red-500">*</span></label>
                <input id="authorlname" type="text" name="authorlname" maxlength="100" required class="shadow-sm py-2.5 px-3 focus:outline-none mt-1 block w-full bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition">
            </div>

            <div class="md:col-span-1">
                <button type="submit" name="add_author" value="1" class="border-transparent shadow-sm font-medium rounded-lg text-white bg-black hover:bg-purple-950 focus:outline-none w-full inline-flex justify-center py-2.5 px-6 border focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition">
                    Add Author
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white overflow-hidden rounded-2xl shadow-lg">
        <div class="border-gray-200 p-6 border-b">
            <h3 class="text-gray-900 text-xl font-semibold">All Authors (<?php echo count($authors); ?>)</h3>
        </div>
        <div class="lg:grid-cols-3 gap-4 p-6 grid grid-cols-1 md:grid-cols-2">
            <?php if (!empty($authors)): ?>
                <?php foreach ($authors as $author): ?>
                <div class="justify-between items-center border border-gray-200 hover:shadow-md bg-gray-50 rounded-xl p-4 flex transition-shadow">
                    <div>
                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($author['authorfname'] . ' ' . $author['authorlname'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <p class="text-sm text-gray-500">ID: <?php echo (int)$author['authorid']; ?></p>
                    </div>

                    <form method="POST" action="author_manage.php" onsubmit="return confirm('Are you sure you want to delete this author?');">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="delete_author" value="<?php echo (int)$author['authorid']; ?>">
                        <button type="submit" aria-label="Delete author <?php echo htmlspecialchars($author['authorfname'] . ' ' . $author['authorlname'], ENT_QUOTES, 'UTF-8'); ?>" class="p-2 rounded-lg text-red-600 hover:text-red-800 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-center py-8 text-gray-500 col-span-full">No authors found. Add one using the form above.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
mysqli_close($conn);
include 'footer.php';
?>
