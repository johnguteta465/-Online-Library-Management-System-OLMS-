<?php
include "db.php";

echo "<h2>Table Structure</h2>";
$result = $conn->query("DESCRIBE books");
if ($result) {
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $val) {
            echo "<td>" . htmlspecialchars($val) . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Error describing table: " . $conn->error;
}

echo "<h2>First 5 Books</h2>";
$books = $conn->query("SELECT * FROM books LIMIT 5");
if ($books) {
    echo "<pre>";
    while ($row = $books->fetch_assoc()) {
        print_r($row);
    }
    echo "</pre>";
}
?>