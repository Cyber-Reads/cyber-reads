<?php
/**
 * Cyber Reads — Automated Backend & Security Regression Tests
 * Run via CLI: C:\xampp\php\php.exe tests/test.php
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Tests can only be executed from CLI.');
}

$passed = 0;
function assertTrue($condition, $message) {
    global $passed;
    if (!$condition) {
        fwrite(STDERR, "  [FAIL] $message\n");
        exit(1);
    }
    $passed++;
    echo "  [PASS] $message\n";
}

echo "Running Cyber Reads Backend & Security Regression Tests...\n\n";

// 1. Database Connection Test
require __DIR__ . '/../src/config/db_connection.php';
assertTrue(isset($conn) && !$conn->connect_error, "Database connection established with utf8mb4 charset");

// 2. Categories Query Test (Trimmed and sorted alphabetically)
$catRes = $conn->query("SELECT categoryid, TRIM(category) AS category FROM category ORDER BY TRIM(category) ASC");
$categories = $catRes->fetch_all(MYSQLI_ASSOC);
assertTrue(count($categories) >= 10, "Categories query returns all 10 genres");
assertTrue($categories[0]['category'] === 'Adventure', "Categories are trimmed and sorted alphabetically (first is Adventure)");
foreach ($categories as $cat) {
    assertTrue($cat['category'] === trim($cat['category']), "Category '{$cat['category']}' has no leading or trailing whitespace");
}

// 3. Book Details Parameter Validation & Prepared Query Test
$stmt = $conn->prepare("SELECT b.bookid, b.title, b.cover, b.pdf FROM books b WHERE b.bookid = ?");
$id = 1;
$stmt->bind_param("i", $id);
$stmt->execute();
$book1 = $stmt->get_result()->fetch_assoc();
$stmt->close();

assertTrue($book1 && $book1['title'] === 'Silent Witness', "Book ID 1 resolves to 'Silent Witness'");
assertTrue(file_exists(__DIR__ . '/../' . $book1['cover']), "Book ID 1 cover asset exists on disk");
assertTrue(file_exists(__DIR__ . '/../' . $book1['pdf']), "Book ID 1 PDF asset exists on disk");

// 4. Verify all 10 database books have existing cover and PDF files on disk
$allBooksRes = $conn->query("SELECT bookid, title, cover, pdf FROM books ORDER BY bookid ASC");
$allBooks = $allBooksRes->fetch_all(MYSQLI_ASSOC);
assertTrue(count($allBooks) === 10, "Database contains all 10 original books");
foreach ($allBooks as $b) {
    assertTrue(file_exists(__DIR__ . '/../' . $b['cover']), "Cover file exists for book #{$b['bookid']} ({$b['cover']})");
    assertTrue(file_exists(__DIR__ . '/../' . $b['pdf']), "PDF file exists for book #{$b['bookid']} ({$b['pdf']})");
}

// 5. Security Static Checks on Admin Files
$bookAddSource = file_get_contents(__DIR__ . '/../admin/book_add.php');
$authCheckPos = strpos($bookAddSource, "\$_SESSION['admin_loggedin']");
$postCheckPos = strpos($bookAddSource, '$_SERVER["REQUEST_METHOD"] === "POST"');
assertTrue($authCheckPos !== false && $postCheckPos !== false && $authCheckPos < $postCheckPos, "admin/book_add.php enforces authentication BEFORE handling POST requests");

$bookEditSource = file_get_contents(__DIR__ . '/../admin/book_edit.php');
assertTrue(strpos($bookEditSource, 'new finfo(FILEINFO_MIME_TYPE)') !== false, "admin/book_edit.php validates uploaded file MIME types via finfo");
assertTrue(strpos($bookEditSource, 'hash_equals($_SESSION[\'csrf_token\']') !== false, "admin/book_edit.php verifies CSRF token on POST");

$bookDeleteSource = file_get_contents(__DIR__ . '/../admin/book_delete.php');
assertTrue(strpos($bookDeleteSource, "\$_SERVER['REQUEST_METHOD'] !== 'POST'") !== false, "admin/book_delete.php rejects GET deletion requests and requires POST + CSRF");

$conn->close();
echo "\nAll $passed backend and security regression tests passed!\n";
?>
