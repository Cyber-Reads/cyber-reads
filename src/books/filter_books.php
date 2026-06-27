<?php

header('Content-Type: application/json');


include '../config/db_connection.php';


$json = file_get_contents ('php://input');

$data = json_decode ($json);


if (isset($data->category_ids) && !empty($data->category_ids)) {
    $categoryIds = $data->category_ids;
} 

else {
    
    $categoryIds = [];
}


$sql = "" ;


if (!empty($categoryIds))  {
    
    $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));

    
    $sql = "SELECT b.bookid, b.title, b.cover, GROUP_CONCAT(CONCAT(a.authorfname, ' ', a.authorlname) SEPARATOR ', ') AS authors
            FROM books b
            LEFT JOIN books_has_author bha ON b.bookid = bha.books_bookid
            LEFT JOIN author a ON bha.author_authorid = a.authorid
            JOIN books_has_category bhc ON b.bookid = bhc.books_bookid
            WHERE bhc.category_categoryid IN ($placeholders)
            GROUP BY b.bookid
            ORDER BY b.bookid DESC";
    
    
    $stmt = $conn->prepare( $sql);

    $types = str_repeat( 'i', count($categoryIds ));

    $stmt->bind_param( $types, ...$categoryIds );

} else {
    

    $sql = "SELECT b.bookid, b.title, b.cover, GROUP_CONCAT(CONCAT(a.authorfname, ' ', a.authorlname) SEPARATOR ', ') AS authors
            FROM books b
            LEFT JOIN books_has_author bha ON b.bookid = bha.books_bookid
            LEFT JOIN author a ON bha.author_authorid = a.authorid
            GROUP BY b.bookid
            ORDER BY b.bookid DESC";

    
    $stmt = $conn->prepare($sql );

}


$stmt->execute();

$result = $stmt->get_result();


$books = [];


if ( $result->num_rows > 0 ) {
    
    while ($row = $result->fetch_assoc()) {
        $books[] = $row;

    }
}


echo json_encode($books );


$stmt->close();
$conn->close();


?>