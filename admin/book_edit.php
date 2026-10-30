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

$book_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($book_id <= 0) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$post_data = [];

// --- Fetch current book data ---
$stmt_book = $conn->prepare("SELECT bookid, title, description, cover, pdf FROM books WHERE bookid = ?");
$stmt_book->bind_param("i", $book_id);
$stmt_book->execute();
$result_book = $stmt_book->get_result();
$book = $result_book ? $result_book->fetch_assoc() : null;
$stmt_book->close();

if (!$book) {
    $conn->close();
    header("Location: dashboard.php");
    exit();
}

// --- Fetch all authors, categories ---
$authors_result = mysqli_query($conn, "SELECT authorid, authorfname, authorlname FROM author ORDER BY authorfname");
$authors = $authors_result ? mysqli_fetch_all($authors_result, MYSQLI_ASSOC) : [];

$categories_result = mysqli_query($conn, "SELECT categoryid, TRIM(category) AS category FROM category ORDER BY TRIM(category)");
$categories = $categories_result ? mysqli_fetch_all($categories_result, MYSQLI_ASSOC) : [];

// --- Fetch this book's current relations ---
$current_author_ids = [];
$stmt_bha = $conn->prepare("SELECT author_authorid FROM books_has_author WHERE books_bookid = ?");
$stmt_bha->bind_param("i", $book_id);
$stmt_bha->execute();
$bha_res = $stmt_bha->get_result();
while ($row = $bha_res->fetch_assoc()) {
    $current_author_ids[] = $row['author_authorid'];
}
$stmt_bha->close();

$current_category_ids = [];
$stmt_bhc = $conn->prepare("SELECT category_categoryid FROM books_has_category WHERE books_bookid = ?");
$stmt_bhc->bind_param("i", $book_id);
$stmt_bhc->execute();
$bhc_res = $stmt_bhc->get_result();
while ($row = $bhc_res->fetch_assoc()) {
    $current_category_ids[] = $row['category_categoryid'];
}
$stmt_bhc->close();

function safeUnlinkBookAsset($relativePath) {
    if (empty($relativePath) || !is_string($relativePath)) return;
    $booksRoot = realpath(__DIR__ . '/../books');
    $targetPath = realpath(__DIR__ . '/../' . ltrim($relativePath, '/\\'));
    if ($booksRoot && $targetPath && strpos($targetPath, $booksRoot) === 0 && is_file($targetPath)) {
        @unlink($targetPath);
    }
}

// --- Handle Form Submission ---
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

    $new_cover_path = null;
    $new_pdf_path = null;

    if ($error === '') {
        $allowed_image_mimes = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp'
        ];

        // Handle Cover Image
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
                    $cover_dir = "../books/cover/";
                    if (!is_dir($cover_dir)) mkdir($cover_dir, 0755, true);
                    $ext = $allowed_image_mimes[$mime];
                    $filename = uniqid('cover_', true) . '.' . $ext;
                    if (move_uploaded_file($_FILES['cover']['tmp_name'], $cover_dir . $filename)) {
                        $new_cover_path = "books/cover/" . $filename;
                    } else {
                        $error = "Failed to move cover image.";
                    }
                }
            }
        }

        // Handle PDF
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
                    $pdf_dir = "../books/pdf/";
                    if (!is_dir($pdf_dir)) mkdir($pdf_dir, 0755, true);
                    $filename = uniqid('book_', true) . '.pdf';
                    if (move_uploaded_file($_FILES['pdf']['tmp_name'], $pdf_dir . $filename)) {
                        $new_pdf_path = "books/pdf/" . $filename;
                    } else {
                        $error = "Failed to move PDF file.";
                    }
                }
            }
        }

        if ($error !== '') {
            if ($new_cover_path && file_exists('../' . $new_cover_path)) @unlink('../' . $new_cover_path);
            if ($new_pdf_path && file_exists('../' . $new_pdf_path)) @unlink('../' . $new_pdf_path);
        }
    }

    // --- 3. Database Update ---
    if ($error === '') {
        $final_cover = $new_cover_path ? $new_cover_path : $book['cover'];
        $final_pdf = $new_pdf_path ? $new_pdf_path : $book['pdf'];

        $stmt_upd = $conn->prepare("UPDATE books SET title = ?, description = ?, cover = ?, pdf = ? WHERE bookid = ?");
        if ($stmt_upd) {
            $stmt_upd->bind_param("ssssi", $title, $description, $final_cover, $final_pdf, $book_id);
            if ($stmt_upd->execute()) {
                $stmt_upd->close();

                $del_auth = $conn->prepare("DELETE FROM books_has_author WHERE books_bookid = ?");
                $del_auth->bind_param("i", $book_id);
                $del_auth->execute();
                $del_auth->close();

                if (!empty($selected_authors)) {
                    $ins_auth = $conn->prepare("INSERT IGNORE INTO books_has_author (books_bookid, author_authorid) VALUES (?, ?)");
                    foreach ($selected_authors as $author_id) {
                        $aid = (int)$author_id;
                        if ($aid > 0) {
                            $ins_auth->bind_param("ii", $book_id, $aid);
                            $ins_auth->execute();
                        }
                    }
                    $ins_auth->close();
                }

                $del_cat = $conn->prepare("DELETE FROM books_has_category WHERE books_bookid = ?");
                $del_cat->bind_param("i", $book_id);
                $del_cat->execute();
                $del_cat->close();

                if (!empty($selected_categories)) {
                    $ins_cat = $conn->prepare("INSERT IGNORE INTO books_has_category (books_bookid, category_categoryid) VALUES (?, ?)");
                    foreach ($selected_categories as $category_id) {
                        $cid = (int)$category_id;
                        if ($cid > 0) {
                            $ins_cat->bind_param("ii", $book_id, $cid);
                            $ins_cat->execute();
                        }
                    }
                    $ins_cat->close();
                }

                if ($new_cover_path && !empty($book['cover'])) {
                    safeUnlinkBookAsset($book['cover']);
                }
                if ($new_pdf_path && !empty($book['pdf'])) {
                    safeUnlinkBookAsset($book['pdf']);
                }

                mysqli_close($conn);
                header("Location: dashboard.php?status=updated");
                exit();
            } else {
                $stmt_upd->close();
            }
        }

        if ($new_cover_path && file_exists('../' . $new_cover_path)) @unlink('../' . $new_cover_path);
        if ($new_pdf_path && file_exists('../' . $new_pdf_path)) @unlink('../' . $new_pdf_path);
        error_log("Database error in book_edit.php: " . mysqli_error($conn));
        $error = "Failed to update book details.";
    }
}

include 'header.php';
?>

<style>
    .selected-item { background-color: #f5f3ff; border-color: #c4b5fd !important; }
    .selected-item:hover { background-color: #ede9fe; }
</style>

<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <div class="p-6 sm:p-8">
            <h2 class="text-3xl font-bold text-gray-900">Edit Book</h2>
            <p class="text-gray-500 mt-1">Update the details for "<?php echo htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8'); ?>"</p>
        </div>

        <form action="book_edit.php?id=<?php echo (int)$book_id; ?>" method="post" enctype="multipart/form-data" class="p-6 sm:p-8 pt-0 space-y-8">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

            <?php if ($error !== ''): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-800 p-4 rounded-r-lg" role="alert">
                <p class="font-bold">Error:</p>
                <p><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <?php endif; ?>

            <div class="space-y-6 border-t border-gray-200 pt-8">
                <h3 class="text-xl font-semibold text-gray-800">Basic Information</h3>
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700">Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" id="title" maxlength="50" required value="<?php echo htmlspecialchars(isset($post_data['title']) ? (string)$post_data['title'] : $book['title'], ENT_QUOTES, 'UTF-8'); ?>"
                           class="mt-1 block w-full bg-gray-50 border border-gray-300 rounded-lg shadow-sm py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition">
                </div>
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Description <span class="text-red-500">*</span></label>
                    <textarea name="description" id="description" rows="4" required
                              class="mt-1 block w-full bg-gray-50 border border-gray-300 rounded-lg shadow-sm py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition"><?php echo htmlspecialchars(isset($post_data['description']) ? (string)$post_data['description'] : $book['description'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
            </div>

            <div class="space-y-6 border-t border-gray-200 pt-8">
                <h3 class="text-xl font-semibold text-gray-800">File Management</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <span class="block text-sm font-medium text-gray-700">Current Cover Image</span>
                        <img src="../<?php echo htmlspecialchars($book['cover'], ENT_QUOTES, 'UTF-8'); ?>" alt="Current cover for <?php echo htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8'); ?>" class="mt-2 h-48 w-auto rounded-lg shadow-md object-cover">
                        <label for="cover" class="block text-sm font-medium text-gray-700 mt-4">Change Cover (Optional)</label>
                        <input id="cover" name="cover" type="file" accept="image/jpeg,image/png,image/gif,image/webp" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100 cursor-pointer">
                    </div>
                    <div>
                        <span class="block text-sm font-medium text-gray-700">Current Book PDF</span>
                        <a href="../<?php echo htmlspecialchars($book['pdf'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" class="mt-2 inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            View Current PDF
                        </a>
                        <label for="pdf" class="block text-sm font-medium text-gray-700 mt-4">Change PDF (Optional)</label>
                        <input id="pdf" name="pdf" type="file" accept=".pdf,application/pdf" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100 cursor-pointer">
                    </div>
                </div>
            </div>

            <div class="space-y-6 border-t border-gray-200 pt-8">
                <h3 class="text-xl font-semibold text-gray-800">Classification</h3>
                <fieldset>
                    <legend class="block text-sm font-medium text-gray-700">Authors</legend>
                    <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 max-h-48 overflow-y-auto p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <?php foreach ($authors as $author): ?>
                        <label class="flex items-center space-x-3 p-2 rounded-lg border border-transparent hover:bg-gray-100 transition cursor-pointer">
                            <input type="checkbox" name="authors[]" value="<?php echo (int)$author['authorid']; ?>" <?php echo in_array($author['authorid'], isset($post_data['authors']) && is_array($post_data['authors']) ? $post_data['authors'] : $current_author_ids) ? 'checked' : ''; ?>
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
                        <label class="flex items-center space-x-3 p-2 rounded-lg border border-transparent hover:bg-gray-100 transition cursor-pointer">
                            <input type="checkbox" name="categories[]" value="<?php echo (int)$category['categoryid']; ?>" <?php echo in_array($category['categoryid'], isset($post_data['categories']) && is_array($post_data['categories']) ? $post_data['categories'] : $current_category_ids) ? 'checked' : ''; ?>
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
include 'footer.php';
?>
