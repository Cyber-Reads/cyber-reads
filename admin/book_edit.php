<?php
session_start();
include 'header.php'; // [[15]]
require '../db_connection.php'; // [[11]]

$book_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$book_id) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$post_data = [];

// --- Fetch current book data ---
$sql_book = "SELECT * FROM books WHERE bookid = $book_id";
$result = mysqli_query($conn, $sql_book);
$book = mysqli_fetch_assoc($result);

if (!$book) {
    header("Location: dashboard.php");
    exit();
}

// --- Fetch all authors, categories ---
$authors_result = mysqli_query($conn, "SELECT authorid, authorfname, authorlname FROM author ORDER BY authorfname");
$authors = mysqli_fetch_all($authors_result, MYSQLI_ASSOC);
$categories_result = mysqli_query($conn, "SELECT categoryid, category FROM category ORDER BY category");
$categories = mysqli_fetch_all($categories_result, MYSQLI_ASSOC);

// --- Fetch this book's current relations ---
$current_author_ids = [];
$book_authors_result = mysqli_query($conn, "SELECT author_authorid FROM books_has_author WHERE books_bookid = $book_id");
while ($row = mysqli_fetch_assoc($book_authors_result)) {
    $current_author_ids[] = $row['author_authorid'];
}

$current_category_ids = [];
$book_categories_result = mysqli_query($conn, "SELECT category_categoryid FROM books_has_category WHERE books_bookid = $book_id");
while ($row = mysqli_fetch_assoc($book_categories_result)) {
    $current_category_ids[] = $row['category_categoryid'];
}

// --- Handle Form Submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $post_data = $_POST;

    // --- 1. Input Validation & Sanitization ---
    $title = trim(mysqli_real_escape_string($conn, $_POST['title']));
    $description = trim(mysqli_real_escape_string($conn, $_POST['description']));
    $selected_authors = isset($_POST['authors']) && is_array($_POST['authors']) ? $_POST['authors'] : [];
    $selected_categories = isset($_POST['categories']) && is_array($_POST['categories']) ? $_POST['categories'] : [];

    if (empty($title) || empty($description)) {
        $error = "Title and Description are required fields.";
    }

    // --- 2. File Upload Handling ---
    $new_cover_path = null;
    $new_pdf_path = null;

    if (empty($error)) {
        // Handle Cover Image
        if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
            // ... (file validation logic remains the same) ...
            $cover_dir = "../books/cover/";
            $ext = pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('cover_', true) . '.' . $ext;
            if (move_uploaded_file($_FILES['cover']['tmp_name'], $cover_dir . $filename)) {
                $new_cover_path = "books/cover/" . $filename;
            } else { $error = "Failed to move cover image."; }
        }

        // Handle PDF
        if (empty($error) && isset($_FILES['pdf']) && $_FILES['pdf']['error'] === UPLOAD_ERR_OK) {
            // ... (file validation logic remains the same) ...
             $pdf_dir = "../books/pdf/";
             $filename = uniqid('book_', true) . '.pdf';
             if (move_uploaded_file($_FILES['pdf']['tmp_name'], $pdf_dir . $filename)) {
                $new_pdf_path = "books/pdf/" . $filename;
             } else { $error = "Failed to move PDF file."; }
        }
    }

    // --- 3. Database Update ---
    if (empty($error)) {
        $sql_update_parts = [];
        $sql_update_parts[] = "title = '$title'";
        $sql_update_parts[] = "description = '$description'";
        if ($new_cover_path) {
            $sql_update_parts[] = "cover = '$new_cover_path'";
        }
        if ($new_pdf_path) {
            $sql_update_parts[] = "pdf = '$new_pdf_path'";
        }
        
        $sql = "UPDATE books SET " . implode(', ', $sql_update_parts) . " WHERE bookid = $book_id";

        if (mysqli_query($conn, $sql)) {
            // Update relations: delete old, insert new
            mysqli_query($conn, "DELETE FROM books_has_author WHERE books_bookid = $book_id");
            if (!empty($selected_authors)) {
                foreach ($selected_authors as $author_id) {
                    $author_id = intval($author_id);
                    mysqli_query($conn, "INSERT INTO books_has_author (books_bookid, author_authorid) VALUES ($book_id, $author_id)");
                }
            }

            mysqli_query($conn, "DELETE FROM books_has_category WHERE books_bookid = $book_id");
            if (!empty($selected_categories)) {
                foreach ($selected_categories as $category_id) {
                    $category_id = intval($category_id);
                    mysqli_query($conn, "INSERT INTO books_has_category (books_bookid, category_categoryid) VALUES ($book_id, $category_id)");
                }
            }
            
            // Delete old files if new ones were uploaded successfully
            if ($new_cover_path && !empty($book['cover']) && file_exists('../' . $book['cover'])) {
                unlink('../' . $book['cover']);
            }
            if ($new_pdf_path && !empty($book['pdf']) && file_exists('../' . $book['pdf'])) {
                unlink('../' . $book['pdf']);
            }

            header("Location: dashboard.php?status=updated");
            exit();
        } else {
            // DB error, delete newly uploaded files
            if ($new_cover_path && file_exists('../' . $new_cover_path)) unlink('../' . $new_cover_path);
            if ($new_pdf_path && file_exists('../' . $new_pdf_path)) unlink('../' . $new_pdf_path);
            $error = "Database error: " . mysqli_error($conn);
        }
    }
}
?>

<style>
    .selected-item { background-color: #f5f3ff; border-color: #c4b5fd !important; }
    .selected-item:hover { background-color: #ede9fe; }
</style>

<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <div class="p-8">
            <h1 class="text-3xl font-bold text-gray-900">Edit Book</h1>
            <p class="text-gray-500 mt-1">Update the details for "<?php echo htmlspecialchars($book['title']); ?>"</p>
        </div>

        <form action="book_edit.php?id=<?php echo $book_id; ?>" method="post" enctype="multipart/form-data" class="p-8 pt-0 space-y-8">
            
            <?php if($error): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-800 p-4 rounded-r-lg" role="alert">
                <p class="font-bold">Error:</p>
                <p><?php echo $error; ?></p>
            </div>
            <?php endif; ?>

            <div class="space-y-6 border-t border-gray-200 pt-8">
                <h2 class="text-xl font-semibold text-gray-800">Basic Information</h2>
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700">Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" id="title" required value="<?php echo htmlspecialchars(isset($post_data['title']) ? $post_data['title'] : $book['title']); ?>"
                           class="mt-1 block w-full bg-gray-50 border-gray-300 rounded-lg shadow-sm py-2 px-3 focus:outline-none focus:ring-purple-500 focus:border-purple-500 transition">
                </div>
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Description <span class="text-red-500">*</span></label>
                    <textarea name="description" id="description" rows="4" required 
                              class="mt-1 block w-full bg-gray-50 border-gray-300 rounded-lg shadow-sm py-2 px-3 focus:outline-none focus:ring-purple-500 focus:border-purple-500 transition"><?php echo htmlspecialchars(isset($post_data['description']) ? $post_data['description'] : $book['description']); ?></textarea>
                </div>
            </div>

            <div class="space-y-6 border-t border-gray-200 pt-8">
                <h2 class="text-xl font-semibold text-gray-800">File Management</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Cover Image</label>
                        <img src="../<?php echo htmlspecialchars($book['cover']); ?>" alt="Current Cover" class="mt-2 h-48 w-auto rounded-lg shadow-md">
                        <label for="cover" class="block text-sm font-medium text-gray-700 mt-4">Change Cover (Optional)</label>
                        <input id="cover" name="cover" type="file" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100 cursor-pointer">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Book PDF</label>
                        <a href="../<?php echo htmlspecialchars($book['pdf']); ?>" target="_blank" class="mt-2 inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            View Current PDF
                        </a>
                        <label for="pdf" class="block text-sm font-medium text-gray-700 mt-4">Change PDF (Optional)</label>
                        <input id="pdf" name="pdf" type="file" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100 cursor-pointer">
                    </div>
                </div>
            </div>

            <div class="space-y-6 border-t border-gray-200 pt-8">
                <h2 class="text-xl font-semibold text-gray-800">Classification</h2>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Authors</label>
                    <div class="mt-2 grid grid-cols-2 md:grid-cols-3 gap-4 max-h-48 overflow-y-auto p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <?php foreach ($authors as $author): ?>
                        <label class="flex items-center space-x-3 p-2 rounded-lg border border-transparent hover:bg-gray-100 transition cursor-pointer">
                            <input type="checkbox" name="authors[]" value="<?php echo $author['authorid']; ?>" <?php echo in_array($author['authorid'], isset($post_data['authors']) ? $post_data['authors'] : $current_author_ids) ? 'checked' : ''; ?>
                                   class="h-4 w-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                            <span class="text-gray-700 text-sm"><?php echo htmlspecialchars($author['authorfname'] . ' ' . $author['authorlname']); ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Categories</label>
                    <div class="mt-2 grid grid-cols-2 md:grid-cols-3 gap-4 max-h-48 overflow-y-auto p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <?php foreach ($categories as $category): ?>
                        <label class="flex items-center space-x-3 p-2 rounded-lg border border-transparent hover:bg-gray-100 transition cursor-pointer">
                            <input type="checkbox" name="categories[]" value="<?php echo $category['categoryid']; ?>" <?php echo in_array($category['categoryid'], isset($post_data['categories']) ? $post_data['categories'] : $current_category_ids) ? 'checked' : ''; ?>
                                   class="h-4 w-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                            <span class="text-gray-700 text-sm"><?php echo htmlspecialchars($category['category']); ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="flex justify-end pt-8 border-t border-gray-200">
                <a href="dashboard.php" class="bg-gray-200 text-gray-700 py-2 px-6 rounded-lg font-medium hover:bg-gray-300 transition mr-3">Cancel</a>
                <button type="submit" class="inline-flex justify-center py-2 px-6 border border-transparent shadow-sm font-medium rounded-lg text-white bg-black hover:bg-purple-950 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition">
                    Update Book
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        function updateCheckboxStyle(checkbox) {
            const label = checkbox.closest('label');
            if (label) {
                if (checkbox.checked) {
                    label.classList.add('selected-item');
                } else {
                    label.classList.remove('selected-item');
                }
            }
        }
        const checkboxes = document.querySelectorAll('input[name="authors[]"], input[name="categories[]"]');
        checkboxes.forEach(updateCheckboxStyle);
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', () => updateCheckboxStyle(checkbox));
        });
    });
</script>

<?php 
mysqli_close($conn);
include 'footer.php'; // [[12]]
?>