<?php
// get_columns.php
include 'db.php';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SHOW COLUMNS FROM books";
$result = $conn->query($sql);

if ($result) {
    echo "Columns in 'books' table:\n";
    echo "-------------------------\n";
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " | " . $row['Type'] . "\n";
    }
} else {
    echo "Error: " . $conn->error;
}
?>