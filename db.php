<?php
/**
 * db.php
 * Database connection file
 * Ambo University Library Management System
 */

$host = "localhost";
$user = "root";
$pass = "";          // Default for XAMPP
$db   = "library_db";

$conn = new mysqli($host, $user, $pass, $db);

/* ===============================
   CONNECTION CHECK
================================ */
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

/* ===============================
   SET CHARACTER SET
================================ */
$conn->set_charset("utf8mb4");
?>
