<?php


header('Content-Type: application/json');


include 'db_connection.php';


$bookId =  0;

if  (isset($_GET['id'])) {
    $bookId = (int)$_GET['id'];

}

if ( $bookId > 0 ) {
    
    $sql =  "SELECT 
                b.bookid, 
                b.title, 
                b.description, 
                b.cover, 
                b.pdf,
                GROUP_CONCAT(DISTINCT CONCAT(a.authorfname, ' ', a.authorlname) SEPARATOR ', ') AS authors,
                GROUP_CONCAT(DISTINCT c.category SEPARATOR ', ') AS categories
            FROM books b
            LEFT JOIN books_has_author bha ON b.bookid = bha.books_bookid
            LEFT JOIN author a ON bha.author_authorid = a.authorid
            LEFT JOIN books_has_category bhc ON b.bookid = bhc.books_bookid
            LEFT JOIN category c ON bhc.category_categoryid = c.categoryid
            WHERE b.bookid = ?
            GROUP BY b.bookid";
    
    
    $stmt =  $conn->prepare($sql) ;

    $stmt->bind_param("i", $bookId);

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows > 0)  {

        $book = $result->fetch_assoc();

        echo json_encode($book);

    } 
    
    else {

        http_response_code(404);
        echo json_encode(['error' =>  'Book not found']);
    }
    

    $stmt->close();

}


else {

    http_response_code (400);
    echo json_encode (['error' =>  'Invalid book ID']) ;

}


$conn->close();

?>