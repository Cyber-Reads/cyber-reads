<!-- <div>
    <?php
    // db_connection.php

    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    $servername = "localhost";
    $username = "root";
    $password = "12345678";
    $dbname = "cb";

    // MySQL port වෙනස් කළා නම් එය එකතු කරන්න
    $conn = new mysqli($servername, $username, $password, $dbname);
    // හෝ port සමඟ: $conn = new mysqli($servername, $username, $password, $dbname, 3307);

    if ($conn->connect_error) {
        // JSON ලෙස error එක යවන්න
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]);
        exit();
    }

    $conn->set_charset("utf8");
    ?>
</div>

<div>
    <?php
// filter_books.php

header('Content-Type: application/json');
include 'db_connection.php';



$json = file_get_contents('php://input');
$data = json_decode($json);

$categoryIds = isset($data->category_ids) ? $data->category_ids : [];

// මූලික SQL query එක. 
// books, books_has_author, සහ author වගු JOIN කර, GROUP_CONCAT මගින් එක් පොතකට අදාළ සියලුම කර්තෘවරුන්ගේ නම් එකම තීරුවක පෙන්වයි.
$sql = "SELECT 
            b.bookid, 
            b.title, 
            b.cover, 
            GROUP_CONCAT(CONCAT(a.authorfname, ' ', a.authorlname) SEPARATOR ', ') AS authors
        FROM books b
        LEFT JOIN books_has_author bha ON b.bookid = bha.books_bookid
        LEFT JOIN author a ON bha.author_authorid = a.authorid ";

// පරිශීලකයා ප්‍රවර්ග තෝරා ඇත්නම්, books_has_category වගුව JOIN කර WHERE clause එකක් එකතු කිරීම
if (!empty($categoryIds)) {
    $sanitizedIds = array_map('intval', $categoryIds);
    $placeholders = implode(',', array_fill(0, count($sanitizedIds), '?'));
    
    $sql .= "JOIN books_has_category bhc ON b.bookid = bhc.books_bookid WHERE bhc.category_categoryid IN ($placeholders) ";
}

// පොත් කාඩ්පත් එකම පිළිවෙලකට පෙන්වීමට GROUP BY සහ ORDER BY එකතු කිරීම
$sql .= "GROUP BY b.bookid ORDER BY b.bookid DESC";

$stmt = $conn->prepare($sql);

// ප්‍රවර්ග තෝරා ඇත්නම්, ඒවා bind කිරීම
if (!empty($categoryIds)) {
    $types = str_repeat('i', count($sanitizedIds));
    $stmt->bind_param($types, ...$sanitizedIds);
}

$stmt->execute();
$result = $stmt->get_result();

$books = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $books[] = $row;
    }
}

echo json_encode($books);

$stmt->close();
$conn->close();
?>

</div>

<div>
    <?php
// get_book_details.php

header('Content-Type: application/json');
include 'db_connection.php';

$bookId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($bookId > 0) {
    $stmt = $conn->prepare("SELECT title, description, cover, pdf FROM books WHERE bookid = ?");
    $stmt->bind_param("i", $bookId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $book = $result->fetch_assoc();
        echo json_encode($book);
    } else {
        echo json_encode(['error' => 'Book not found']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['error' => 'Invalid book ID']);
}

$conn->close();
?>

</div>

<div>
    <?php
// get_categories.php

header('Content-Type: application/json');
include 'db_connection.php';

$sql = "SELECT categoryid, category FROM category ORDER BY category ASC";
$result = $conn->query($sql);

$categories = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}

echo json_encode($categories);

$conn->close();
?>

</div> -->