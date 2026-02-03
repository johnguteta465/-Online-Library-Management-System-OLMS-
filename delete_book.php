<?php
session_start();
include "db.php";

if (isset($_GET['id'])) {
    $book_id = intval($_GET['id']);

    // DEBUG TIP: Check if your table uses 'id' or 'book_id'
    // In your borrow.php, you used 'book_id', so we use that here:
    $sql = "DELETE FROM books WHERE book_id = ?"; 
    
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        // This will tell you EXACTLY what is wrong with the SQL
        die("SQL Error: " . $conn->error . " | Check if the column name is 'id' or 'book_id'");
    }

    $stmt->bind_param("i", $book_id);

    if ($stmt->execute()) {
        header("Location: books.php?status=deleted");
        exit;
    } else {
        echo "Execution Error: " . $stmt->error;
    }
    $stmt->close();
} else {
    header("Location: books.php");
}
?>