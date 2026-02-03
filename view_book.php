<?php
session_start();
include 'db.php';

// Security Check
if (!isset($_SESSION['user_id']) || in_array(($_SESSION['user_role'] ?? 'user'), ['admin', 'super_admin'])) {
    header("Location: login.php");
    exit;
}

$book_id = intval($_GET['book_id'] ?? 0);

if ($book_id === 0) {
    header("Location: online_library.php");
    exit;
}

// Fetch book details and the path to the file for online viewing
// FIX: Using 'pdf' and aliasing it as 'online_content_path'
$stmt = $conn->prepare("
    SELECT title, author, pdf AS online_content_path 
    FROM books 
    WHERE book_id = ? AND is_available_online = TRUE
");

// Check if prepare succeeded BEFORE calling bind_param
if ($stmt === FALSE) {
    echo "<script>alert('Database Error: Failed to prepare statement. Check if all columns (id, is_available_online, pdf) exist.'); window.location='online_library.php';</script>";
    exit;
}

// The error was happening here (Line 24 in your original code)
$stmt->bind_param("i", $book_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<script>alert('Book not found or not available for online reading.'); window.location='online_library.php';</script>";
    exit;
}

$book = $result->fetch_assoc();
$filePath = $book['online_content_path']; 

if (!file_exists($filePath)) {
    // Handle case where file path is in DB but file is missing
    echo "<script>alert('Error: Digital file is missing.'); window.location='online_library.php';</script>";
    exit;
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Read: <?= htmlspecialchars($book['title']) ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
/* Disable right-click context menu (Basic Anti-Download Measure) */
body {
    user-select: none; /* Disable text selection */
    -webkit-user-select: none;
    -moz-user-select: none;
    font-family: Poppins, sans-serif;
    margin: 0;
    overflow: hidden; /* Prevent body scroll, let the iframe handle it */
}

.reader-container {
    width: 100vw;
    height: 100vh;
    display: flex;
    flex-direction: column;
}

.reader-header {
    background: #003366; /* Dark header */
    color: white;
    padding: 10px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-shrink: 0;
}

.reader-header h1 {
    margin: 0;
    font-size: 18px;
    font-weight: 500;
}

.reader-header a {
    color: #ffc107; /* Yellow accent for link */
    text-decoration: none;
    font-weight: 600;
}

/* The actual reader frame/object */
.reader-frame {
    flex-grow: 1; /* Take up all remaining space */
    border: none;
    width: 100%;
    height: 100%;
}

</style>
</head>
<body>

<div class="reader-container">
    <div class="reader-header">
        <h1>Reading: <?= htmlspecialchars($book['title']) ?> by <?= htmlspecialchars($book['author']) ?></h1>
        <a href="online_library.php"><i class="fa-solid fa-arrow-left"></i> Back to Library</a>
    </div>

    <iframe 
        class="reader-frame" 
        src="<?= htmlspecialchars($filePath) ?>" 
        type="application/pdf"
        title="Online Reader for <?= htmlspecialchars($book['title']) ?>"
    >
        <p>Your browser does not support embedded PDFs. Please use a modern browser.</p>
    </iframe>

</div>

<script>
    // Additional basic anti-download script (user can still disable JS)
    document.addEventListener('contextmenu', event => event.preventDefault());
</script>

</body>
</html>