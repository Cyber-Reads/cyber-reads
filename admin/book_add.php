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

require '../src/config/db_connection.php';

// Fetch authors and categories
$authors_result = mysqli_query($conn, "SELECT authorid, authorfname, authorlname FROM author ORDER BY authorfname");
$authors = $authors_result ? mysqli_fetch_all($authors_result, MYSQLI_ASSOC) : [];

$categories_result = mysqli_query($conn, "SELECT categoryid, TRIM(category) AS category FROM category ORDER BY TRIM(category)");
$categories = $categories_result ? mysqli_fetch_all($categories_result, MYSQLI_ASSOC) : [];

$error = '';
$post_data = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $post_data = $_POST;

    $submitted_token = isset($_POST['csrf_token']) ? (string)$_POST['csrf_token'] : '';
    if (!hash_equals($_SESSION['csrf_token'], $submitted_token)) {
        $error = "Invalid security token. Please refresh the page and try again.";
    }

    $title = isset($_POST['title']) ? trim((string)$_POST['title']) : '';
    $description = isset($_POST['description']) ? trim((string)$_POST['description']) : '';
    $selected_authors = isset($_POST['authors']) && is_array($_POST['authors']) ? $_POST['authors'] : [];
    $selected_categories = isset($_POST['categories']) && is_array($_POST['categories']) ? $_POST['categories'] : [];

    if ($error === '' && ($title === '' || $description === '')) {
        $error = "Title and Description are required fields.";
    } elseif ($error === '' && mb_strlen($title) > 50) {
        $error = "Title cannot exceed 50 characters.";
    }

    $cover_path = null;
    $pdf_path = null;

    if ($error === '') {
        $cover_dir = "../books/cover/";
        $pdf_dir = "../books/pdf/";
        if (!is_dir($cover_dir)) mkdir($cover_dir, 0755, true);
        if (!is_dir($pdf_dir)) mkdir($pdf_dir, 0755, true);

        $allowed_image_mimes = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp'
        ];

        // --- Secure Cover Image Upload ---
        if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
            if ($_FILES['cover']['size'] > 10485760) {
                $error = "Cover image is too large. Maximum 10MB.";
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($_FILES['cover']['tmp_name']);
                $img_info = @getimagesize($_FILES['cover']['tmp_name']);
                if (!isset($allowed_image_mimes[$mime]) || $img_info === false) {
                    $error = "Invalid cover image format. Only valid JPG, PNG, GIF, and WEBP images are allowed.";
                } else {
                    $ext = $allowed_image_mimes[$mime];
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
        if ($error === '' && isset($_FILES['pdf']) && $_FILES['pdf']['error'] === UPLOAD_ERR_OK) {
            if ($_FILES['pdf']['size'] > 52428800) {
                $error = "PDF file is too large. Maximum 50MB.";
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($_FILES['pdf']['tmp_name']);
                $fh = @fopen($_FILES['pdf']['tmp_name'], 'rb');
                $magic = $fh ? fread($fh, 5) : '';
                if ($fh) fclose($fh);

                if ($mime !== 'application/pdf' || $magic !== '%PDF-') {
                    $error = "Invalid file format. Only valid PDF files are allowed.";
                } else {
                    $new_filename = uniqid('book_', true) . '.pdf';
                    if (move_uploaded_file($_FILES['pdf']['tmp_name'], $pdf_dir . $new_filename)) {
                        $pdf_path = "books/pdf/" . $new_filename;
                    } else {
                        $error = "Failed to move uploaded PDF file.";
                    }
                }
            }
        } elseif ($error === '') {
            $error = "PDF file is required.";
        }

        if ($error !== '') {
            if ($cover_path && file_exists('../' . $cover_path)) @unlink('../' . $cover_path);
            if ($pdf_path && file_exists('../' . $pdf_path)) @unlink('../' . $pdf_path);
        }
    }

    // --- Database Operations ---
    if ($error === '') {
        $stmt = $conn->prepare("INSERT INTO books (title, description, cover, pdf) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssss", $title, $description, $cover_path, $pdf_path);
            if ($stmt->execute()) {
                $book_id = (int)$stmt->insert_id;
                $stmt->close();

                if (!empty($selected_authors)) {
                    $author_stmt = $conn->prepare("INSERT IGNORE INTO books_has_author (books_bookid, author_authorid) VALUES (?, ?)");
                    if ($author_stmt) {
                        foreach ($selected_authors as $author_id) {
                            $aid = (int)$author_id;
                            if ($aid > 0) {
                                $author_stmt->bind_param("ii", $book_id, $aid);
                                $author_stmt->execute();
                            }
                        }
                        $author_stmt->close();
                    }
                }

                if (!empty($selected_categories)) {
                    $cat_stmt = $conn->prepare("INSERT IGNORE INTO books_has_category (books_bookid, category_categoryid) VALUES (?, ?)");
                    if ($cat_stmt) {
                        foreach ($selected_categories as $category_id) {
                            $cid = (int)$category_id;
                            if ($cid > 0) {
                                $cat_stmt->bind_param("ii", $book_id, $cid);
                                $cat_stmt->execute();
                            }
                        }
                        $cat_stmt->close();
                    }
                }

                mysqli_close($conn);
                header("Location: dashboard.php?status=added");
                exit();
            } else {
                $stmt->close();
            }
        }

        if ($cover_path && file_exists('../' . $cover_path)) @unlink('../' . $cover_path);
        if ($pdf_path && file_exists('../' . $pdf_path)) @unlink('../' . $pdf_path);
        error_log("Database error in book_add.php: " . mysqli_error($conn));
        $error = "Failed to save the book to the database.";
    }
}

include 'header.php';
?>

<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <div class="p-6 sm:p-8">
            <h2 class="text-3xl font-bold text-gray-900">Add a New Book</h2>
            <p class="text-gray-500 mt-1">Fill in the details below to add a new book to the library.</p>
        </div>

        <form action="book_add.php" method="post" enctype="multipart/form-data" class="p-6 sm:p-8 pt-0 space-y-8">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

            <?php if ($error !== ''): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-800 p-4 rounded-r-lg" role="alert">
                <div class="flex">
                    <div class="py-1"><svg class="fill-current h-6 w-6 text-red-500 mr-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" aria-hidden="true"><path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zM11.414 10l2.829-2.828-1.414-1.414L10 8.586 7.172 5.757 5.757 7.172 8.586 10l-2.829 2.828 1.414 1.414L10 11.414l2.829 2.828 1.414-1.414L11.414 10z"/></svg></div>
                    <div>
                        <p class="font-bold">Error</p>
                        <p class="text-sm"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="space-y-6 border-t border-gray-200 pt-8">
                <h3 class="text-xl font-semibold text-gray-800">Basic Information</h3>
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700">Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" id="title" maxlength="50" required value="<?php echo htmlspecialchars(isset($post_data['title']) ? (string)$post_data['title'] : '', ENT_QUOTES, 'UTF-8'); ?>"
                           class="mt-1 block w-full bg-gray-50 border border-gray-300 rounded-lg shadow-sm py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition">
                </div>
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Description <span class="text-red-500">*</span></label>
                    <textarea name="description" id="description" rows="4" required
                              class="mt-1 block w-full bg-gray-50 border border-gray-300 rounded-lg shadow-sm py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition"><?php echo htmlspecialchars(isset($post_data['description']) ? (string)$post_data['description'] : '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
            </div>

            <div class="space-y-6 border-t border-gray-200 pt-8">
                <h3 class="text-xl font-semibold text-gray-800">File Uploads</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="cover" class="block text-sm font-medium text-gray-700">Cover Image <span class="text-red-500">*</span></label>
                        <label for="cover" class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer hover:border-purple-500 focus-within:ring-2 focus-within:ring-purple-500 transition bg-gray-50">
                            <div class="space-y-1 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true"><path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>
                                <p class="text-sm text-gray-600" id="cover-text">Click to upload a cover image</p>
                                <p class="text-xs text-gray-500">PNG, JPG, GIF, WEBP up to 10MB</p>
                            </div>
                        </label>
                        <input id="cover" name="cover" type="file" class="sr-only" accept="image/jpeg,image/png,image/gif,image/webp" required>
                    </div>
                    <div>
                        <label for="pdf" class="block text-sm font-medium text-gray-700">Book PDF <span class="text-red-500">*</span></label>
                        <label for="pdf" class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer hover:border-purple-500 focus-within:ring-2 focus-within:ring-purple-500 transition bg-gray-50">
                            <div class="space-y-1 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                                <p class="text-sm text-gray-600" id="pdf-text">Click to upload a PDF file</p>
                                <p class="text-xs text-gray-500">PDF up to 50MB</p>
                            </div>
                        </label>
                        <input id="pdf" name="pdf" type="file" class="sr-only" accept=".pdf,application/pdf" required>
                    </div>
                </div>
            </div>

            <div class="space-y-6 border-t border-gray-200 pt-8">
                <h3 class="text-xl font-semibold text-gray-800">Classification</h3>
                <fieldset>
                    <legend class="block text-sm font-medium text-gray-700">Authors</legend>
                    <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 max-h-48 overflow-y-auto p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <?php foreach ($authors as $author): ?>
                        <label class="flex items-center space-x-3 p-2 rounded-lg hover:bg-purple-50 transition cursor-pointer">
                            <input type="checkbox" name="authors[]" value="<?php echo (int)$author['authorid']; ?>" <?php echo in_array($author['authorid'], isset($post_data['authors']) && is_array($post_data['authors']) ? $post_data['authors'] : []) ? 'checked' : ''; ?>
                                   class="h-4 w-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                            <span class="text-gray-700 text-sm"><?php echo htmlspecialchars($author['authorfname'] . ' ' . $author['authorlname'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>
                <fieldset>
                    <legend class="block text-sm font-medium text-gray-700">Categories</legend>
                    <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 max-h-48 overflow-y-auto p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <?php foreach ($categories as $category): ?>
                        <label class="flex items-center space-x-3 p-2 rounded-lg hover:bg-purple-50 transition cursor-pointer">
                            <input type="checkbox" name="categories[]" value="<?php echo (int)$category['categoryid']; ?>" <?php echo in_array($category['categoryid'], isset($post_data['categories']) && is_array($post_data['categories']) ? $post_data['categories'] : []) ? 'checked' : ''; ?>
                                   class="h-4 w-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                            <span class="text-gray-700 text-sm"><?php echo htmlspecialchars($category['category'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>
            </div>

            <div class="flex justify-end pt-8 border-t border-gray-200">
                <a href="dashboard.php" class="bg-gray-200 text-gray-700 py-2.5 px-6 rounded-lg font-medium hover:bg-gray-300 transition mr-3">Cancel</a>
                <button type="submit" class="inline-flex justify-center py-2.5 px-6 border border-transparent shadow-sm font-medium rounded-lg text-white bg-black hover:bg-purple-950 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition">
                    Add Book
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('cover').addEventListener('change', function(e) {
    document.getElementById('cover-text').textContent = e.target.files[0] ? e.target.files[0].name : 'Click to upload a cover image';
});
document.getElementById('pdf').addEventListener('change', function(e) {
    document.getElementById('pdf-text').textContent = e.target.files[0] ? e.target.files[0].name : 'Click to upload a PDF file';
});
</script>

<?php
mysqli_close($conn);
include 'footer.php';
?>
