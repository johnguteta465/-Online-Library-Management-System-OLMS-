<?php
// check_books.php
include 'db.php';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Using 'book_id' based on schema discovery
$sql = "SELECT book_id, title, quantity FROM books";
$result = $conn->query($sql);

if ($result) {
    echo "Total Books Found: " . $result->num_rows . "\n";
    echo "--------------------------------------------------\n";
    echo "ID | Title | Quantity\n";
    echo "--------------------------------------------------\n";
    while ($row = $result->fetch_assoc()) {
        echo $row["book_id"] . " | " . $row["title"] . " | " . $row["quantity"] . "\n";
    }
} else {
    echo "Error executing query: " . $conn->error;
}
?>