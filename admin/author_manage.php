<?php
session_start();
include 'header.php';
require '../src/config/db_connection.php';

$error = '';
$success = '';

// Add author
if ( $_SERVER["REQUEST_METHOD"]  == "POST" && isset($_POST['add_author']) ) {

    $fname = trim(mysqli_real_escape_string($conn, $_POST['authorfname']));
    $lname = trim(mysqli_real_escape_string($conn, $_POST['authorlname']));

    if (!empty($fname) && !empty($lname)) {

        $sql = "INSERT INTO author (authorfname, authorlname) VALUES ('$fname', '$lname')";

        if (mysqli_query($conn, $sql)) {
            $success = "Author added successfully!";
        }

        else {
            $error = "Error adding author.";
        }
    }

    else {
        $error = "First name and last name are required.";

    }
}

// Delete author
if (isset($_GET['delete'])) {

    $author_id = intval($_GET['delete']);

    // Check if author has books
    $check_sql = "SELECT COUNT(*) as count FROM books_has_author WHERE author_authorid = $author_id";
    $check_result = mysqli_query($conn, $check_sql);
    $result = mysqli_fetch_assoc($check_result);

    if ($result['count'] > 0) {
        $error = "Cannot delete author with existing books. Please reassign or delete the books first.";
    }
    else {
        $delete_sql = "DELETE FROM author WHERE authorid = $author_id";

        if (mysqli_query($conn, $delete_sql)) {
            $success = "Author deleted successfully!";
        }

        else {
            $error = "Error deleting author.";
        }
    }
}

// Get all authors
$authors_result =  mysqli_query($conn, "SELECT * FROM author ORDER BY authorfname, authorlname");
$authors =  mysqli_fetch_all($authors_result, MYSQLI_ASSOC);
?>

<div class = "max-w-4xl mx-auto">

    <div class = "mb-8 ">
        <h1 class = "  text-gray-900 text-3xl font-bold" >Manage Authors</h1>
        <p class = "mt-1 text-gray-500 " >Add, view, and manage book authors.</p>

    </div>

    <?php if( $error ): ?>

    <div class="bg-red-50 p-4 rounded-r-lg border-l-4  border-red-500 text-red-800  mb-6  " role="alert">
         <p class="font-bold " >Error:</p>
        <p><?php echo $error; ?></p>

    </div>

    <?php endif; ?>

    <?php if($success): ?>
    <div class = "bg-green-50 border-l-4  text-green-800 p-4 rounded-r-lg mb-6 border-green-500 " role = "alert">

        <p class = "font-bold ">Success:</p>
        <p><?php echo $success; ?></p >

    </div>


    <?php endif; ?>

    <div class = " bg-white shadow-lg p-8 mb-8 rounded-2xl ">
        <h2 class = " text-xl font-semibold text-gray-800 mb-6">Add New Author</h2>

        <form method = "POST" action = "author_manage.php" class = " gap-6 grid grid-cols-1 md:grid-cols-3 items-end">

            <div class = "md:col-span-1">
                <label class = "  text-gray-700 block text-sm font-medium">First Name</label>
                <input type = "text" name = "authorfname" required class = "  border-gray-300 rounded-lg shadow-sm py-2 px-3 mt-1 block w-full bg-gray-50 focus:outline-none focus:ring-purple-500 focus:border-purple-500 transition">
            </div>

            <div class = "md:col-span-1">
                <label class = "block text-sm font-medium text-gray-700">Last Name</label>
                <input type = "text" name = "authorlname" required class = " shadow-sm py-2 px-3 focus:outline-none mt-1 block w-full bg-gray-50 border-gray-300 rounded-lg  focus:ring-purple-500 focus:border-purple-500 transition">
            </div>

            <div  class = "md:col-span-1" >
                <button type = "submit" name = "add_author" class = " border-transparent shadow-sm font-medium rounded-lg text-white bg-black hover:bg-purple-950 focus:outline-none w-full inline-flex justify-center py-2 px-6 border  focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition">
                    Add Author
                </button>

             </div>

        </form>

    </div>

    <div class = "bg-white overflow-hidden rounded-2xl  shadow-lg ">
        <div class = "  border-gray-200 p-6 border-b">
            <h2 class = "  text-gray-900 text-xl font-semibold">All Authors</h2 >
        </div>
        <div class=" lg:grid-cols-3 gap-4 p-6 grid grid-cols-1 md:grid-cols-2 ">
            <?php
            if (!empty($authors)):
            ?>
                <?php
                foreach ($authors as $author):
                ?>

                <div class = " justify-between items-center border border-gray-200 hover:shadow-md bg-gray-50 rounded-xl p-4 flex  transition-shadow">
                    <div>
                        <p class = "font-medium text-gray-900"><?php echo htmlspecialchars($author['authorfname'] . ' ' . $author['authorlname']); ?></p>
                        <p class =  "text-sm text-gray-500">ID: <?php echo $author['authorid']; ?></p>

                    </div>

                    <a href = "?delete=<?php echo $author['authorid']; ?>" onclick="return confirm('Are you sure you want to delete this author?');" class="text-red-500 hover:text-red-700 transition-colors">
                        <svg class = "w-5 h-5" fill = "none" stroke = "currentColor" viewBox = "0 0 24 24">
                            <path stroke-linecap = "round" stroke-linejoin = "round" stroke-width = "2" d = "M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </a>

                </div>

                <?php endforeach;
                ?>
            <?php else: ?>
                <p class = "  text-center py-8 text-gray-500 col-span-full" >No authors found. Add one using the form above.</p>
            <?php endif; ?>

        </div>


    </div>
</div>

<?php
mysqli_close($conn);
include 'footer.php'; // [[12]]
?>