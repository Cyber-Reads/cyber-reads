<?php
session_start(); // Session එක මෙතනින් ආරම්භ කරන්න
include 'header.php'; // [[14]]
require '../db_connection.php'; // [[11]]

$books = [];
$sql = "SELECT bookid, title, cover FROM books ORDER BY bookid DESC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $books[] = $row;
    }
}
$conn->close();
?>

<div class="max-w-7xl mx-auto">
    <!-- Header Section -->
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-gray-900 mb-2">Book Management</h1>
        <p class="text-gray-600">Manage Cyber Reads Library Collection</p>
    </div>

    <!-- Status Messages -->
    <?php if(isset($_GET['status'])): ?>
        <div class="bg-white p-4 rounded-lg shadow-md mb-6 border-l-4 <?php 
            if($_GET['status'] == 'deleted') echo 'border-red-500';
            elseif($_GET['status'] == 'updated') echo 'border-blue-500';
            else echo 'border-green-500';
        ?>">
            <p class="font-semibold <?php 
                if($_GET['status'] == 'deleted') echo 'text-red-700';
                elseif($_GET['status'] == 'updated') echo 'text-blue-700';
                else echo 'text-green-700';
            ?>">
                <?php 
                if($_GET['status'] == 'added') echo 'New book has been successfully added.';
                if($_GET['status'] == 'updated') echo 'Book details have been successfully updated.';
                if($_GET['status'] == 'deleted') echo 'Book has been successfully deleted.';
                ?>
            </p>
        </div>
    <?php endif; ?>

    <!-- Books Table -->
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <div class="p-6 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-xl font-semibold text-gray-900">All Books</h2>
            <a href="book_add.php" class="px-5 py-2.5 bg-black text-white rounded-xl font-medium hover:bg-purple-950 transition-colors">
                + Add New Book
            </a>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cover</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (!empty($books)): ?>
                        <?php foreach ($books as $book): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="w-16 h-24 rounded-lg overflow-hidden shadow-md">
                                    <img class="w-full h-full object-cover" src="../<?php echo htmlspecialchars($book['cover']); ?>" alt="Book Cover" />
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-gray-900 font-medium text-base"><?php echo htmlspecialchars($book['title']); ?></p>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex space-x-3">
                                    <a href="book_edit.php?id=<?php echo $book['bookid']; ?>" class="px-4 py-2 bg-blue-100 text-blue-700 rounded-lg font-medium hover:bg-blue-200 transition-colors">Edit</a>
                                    <a href="book_delete.php?id=<?php echo $book['bookid']; ?>" onclick="return confirm('Are you sure you want to delete this book?');" class="px-4 py-2 bg-red-100 text-red-700 rounded-lg font-medium hover:bg-red-200 transition-colors">Delete</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="text-center py-16">
                                <div class="text-gray-400">
                                    <p class="text-lg font-medium">No books found</p>
                                    <p class="text-sm mt-2">Start by adding your first book</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
