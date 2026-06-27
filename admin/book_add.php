<?php
// 1. සියලුම PHP logic මෙතනින් ආරම්භ කරන්න
session_start(); // Session එක මෙතනින් ආරම්භ කිරීම වඩාත් සුදුසුයි
require '../src/config/db_connection.php';

// Fetch authors and categories
$authors_result = mysqli_query($conn, "SELECT authorid, authorfname, authorlname FROM author ORDER BY authorfname");
$authors = mysqli_fetch_all($authors_result, MYSQLI_ASSOC);

$categories_result = mysqli_query($conn, "SELECT categoryid, category FROM category ORDER BY category");
$categories = mysqli_fetch_all($categories_result, MYSQLI_ASSOC);

$error = '';
$post_data = []; // To repopulate form on error

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $post_data = $_POST;

    // --- Input Validation & Sanitization ---
    $title = isset($_POST['title']) ? trim(mysqli_real_escape_string($conn, $_POST['title'])) : '';
    $description = isset($_POST['description']) ? trim(mysqli_real_escape_string($conn, $_POST['description'])) : '';
    $selected_authors = isset($_POST['authors']) && is_array($_POST['authors']) ? $_POST['authors'] : [];
    $selected_categories = isset($_POST['categories']) && is_array($_POST['categories']) ? $_POST['categories'] : [];

    if (empty($title) || empty($description)) {
        $error = "Title and Description are required fields.";
    }

    // --- Directory & File Handling ---
    $cover_path = null;
    $pdf_path = null;

    if (empty($error)) {
        $cover_dir = "../books/cover/";
        $pdf_dir = "../books/pdf/";
        if (!is_dir($cover_dir)) mkdir($cover_dir, 0777, true);
        if (!is_dir($pdf_dir)) mkdir($pdf_dir, 0777, true);

        // --- Secure Cover Image Upload ---
        if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
            if ($_FILES['cover']['size'] > 10485760) { // 10MB
                $error = "Cover image is too large. Maximum 10MB.";
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($_FILES['cover']['tmp_name']);
                if (!in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
                    $error = "Invalid cover image format. Only JPG, PNG, GIF, WEBP are allowed.";
                } else {
                    $ext = pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION);
                    $new_filename = uniqid('cover_', true) . '.' . $ext;
                    if (move_uploaded_file($_FILES['cover']['tmp_name'], $cover_dir . $new_filename)) {
                        $cover_path = "books/cover/" . $new_filename;
                    } else {
                        $error = "Failed to move uploaded cover image.";
                    }
                }
            }
        } else {
            $error = "Cover image is required.";
        }

        // --- Secure PDF File Upload ---
        if (empty($error) && isset($_FILES['pdf']) && $_FILES['pdf']['error'] === UPLOAD_ERR_OK) {
            if ($_FILES['pdf']['size'] > 52428800) { // 50MB
                $error = "PDF file is too large. Maximum 50MB.";
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($_FILES['pdf']['tmp_name']);
                if ($mime !== 'application/pdf') {
                    $error = "Invalid file format. Only PDF files are allowed.";
                } else {
                    $new_filename = uniqid('book_', true) . '.pdf';
                    if (move_uploaded_file($_FILES['pdf']['tmp_name'], $pdf_dir . $new_filename)) {
                        $pdf_path = "books/pdf/" . $new_filename;
                    } else {
                        $error = "Failed to move uploaded PDF file.";
                    }
                }
            }
        } elseif (empty($error)) {
            $error = "PDF file is required.";
        }
    }

    // --- Database Operations ---
    if (empty($error)) {
        $sql = "INSERT INTO books (title, description, cover, pdf) VALUES ('$title', '$description', '$cover_path', '$pdf_path')";
        
        if (mysqli_query($conn, $sql)) {
            $book_id = mysqli_insert_id($conn);

            // Add authors
            if (!empty($selected_authors)) {
                foreach ($selected_authors as $author_id) {
                    $author_id = intval($author_id);
                    $author_sql = "INSERT INTO books_has_author (books_bookid, author_authorid) VALUES ($book_id, $author_id)";
                    mysqli_query($conn, $author_sql);
                }
            }

            // Add categories
            if (!empty($selected_categories)) {
                foreach ($selected_categories as $category_id) {
                    $category_id = intval($category_id);
                    $category_sql = "INSERT INTO books_has_category (books_bookid, category_categoryid) VALUES ($book_id, $category_id)";
                    mysqli_query($conn, $category_sql);
                }
            }
            
            header("Location: dashboard.php?status=added");
            exit();

        } else {
            // Database insert failed, delete uploaded files
            if ($cover_path && file_exists('../' . $cover_path)) unlink('../' . $cover_path);
            if ($pdf_path && file_exists('../' . $pdf_path)) unlink('../' . $pdf_path);
            $error = "Database error: " . mysqli_error($conn);
        }
    }
}

// 2. PHP logic අවසන් වූ පසු, HTML ප්‍රතිදානය ආරම්භ කරන්න
include 'header.php'; // [[16]]
?>

<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <div class="p-8">
            <h1 class="text-3xl font-bold text-gray-900">Add a New Book</h1>
            <p class="text-gray-500 mt-1">Fill in the details below to add a new book to the library.</p>
        </div>

        <form action="book_add.php" method="post" enctype="multipart/form-data" class="p-8 pt-0 space-y-8">
            
            <?php if($error): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-800 p-4 rounded-r-lg" role="alert">
                <div class="flex">
                    <div class="py-1"><svg class="fill-current h-6 w-6 text-red-500 mr-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zM11.414 10l2.829-2.828-1.414-1.414L10 8.586 7.172 5.757 5.757 7.172 8.586 10l-2.829 2.828 1.414 1.414L10 11.414l2.829 2.828 1.414-1.414L11.414 10z"/></svg></div>
                    <div>
                        <p class="font-bold">Error</p>
                        <p class="text-sm"><?php echo $error; ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="space-y-6 border-t border-gray-200 pt-8">
                <h2 class="text-xl font-semibold text-gray-800">Basic Information</h2>
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700">Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" id="title" required value="<?php echo htmlspecialchars(isset($post_data['title']) ? $post_data['title'] : ''); ?>"
                           class="mt-1 block w-full bg-gray-50 border-gray-300 rounded-lg shadow-sm py-2 px-3 focus:outline-none focus:ring-purple-500 focus:border-purple-500 transition">
                </div>
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Description <span class="text-red-500">*</span></label>
                    <textarea name="description" id="description" rows="4" required 
                              class="mt-1 block w-full bg-gray-50 border-gray-300 rounded-lg shadow-sm py-2 px-3 focus:outline-none focus:ring-purple-500 focus:border-purple-500 transition"><?php echo htmlspecialchars(isset($post_data['description']) ? $post_data['description'] : ''); ?></textarea>
                </div>
            </div>

            <div class="space-y-6 border-t border-gray-200 pt-8">
                <h2 class="text-xl font-semibold text-gray-800">File Uploads</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Cover Image <span class="text-red-500">*</span></label>
                        <label for="cover" class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer hover:border-purple-400 transition bg-gray-50">
                            <div class="space-y-1 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true"><path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>
                                <p class="text-sm text-gray-600" id="cover-text">Click to upload a file</p>
                                <p class="text-xs text-gray-500">PNG, JPG, GIF, WEBP up to 10MB</p>
                            </div>
                        </label>
                        <input id="cover" name="cover" type="file" class="sr-only" accept="image/*" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Book PDF <span class="text-red-500">*</span></label>
                        <label for="pdf" class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer hover:border-purple-400 transition bg-gray-50">
                            <div class="space-y-1 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                                <p class="text-sm text-gray-600" id="pdf-text">Click to upload a file</p>
                                <p class="text-xs text-gray-500">PDF up to 50MB</p>
                            </div>
                        </label>
                        <input id="pdf" name="pdf" type="file" class="sr-only" accept=".pdf" required>
                    </div>
                </div>
            </div>

            <div class="space-y-6 border-t border-gray-200 pt-8">
                <h2 class="text-xl font-semibold text-gray-800">Classification</h2>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Authors</label>
                    <div class="mt-2 grid grid-cols-2 md:grid-cols-3 gap-4 max-h-48 overflow-y-auto p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <?php foreach ($authors as $author): ?>
                        <label class="flex items-center space-x-3 p-2 rounded-lg hover:bg-purple-50 transition cursor-pointer">
                            <input type="checkbox" name="authors[]" value="<?php echo $author['authorid']; ?>" <?php echo in_array($author['authorid'], isset($post_data['authors']) ? $post_data['authors'] : []) ? 'checked' : ''; ?>
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
                        <label class="flex items-center space-x-3 p-2 rounded-lg hover:bg-purple-50 transition cursor-pointer">
                            <input type="checkbox" name="categories[]" value="<?php echo $category['categoryid']; ?>" <?php echo in_array($category['categoryid'], isset($post_data['categories']) ? $post_data['categories'] : []) ? 'checked' : ''; ?>
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
                    Add Book
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('cover').addEventListener('change', function(e) {
    document.getElementById('cover-text').textContent = e.target.files[0] ? e.target.files[0].name : 'Click to upload a file';
});
document.getElementById('pdf').addEventListener('change', function(e) {
    document.getElementById('pdf-text').textContent = e.target.files[0] ? e.target.files[0].name : 'Click to upload a file';
});
</script>

<?php 
mysqli_close($conn);
include 'footer.php'; // [[12]]
?>