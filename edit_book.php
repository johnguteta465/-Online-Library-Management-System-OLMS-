<?php
session_start();
include "db.php";

// 1. Get the ID from the URL
$id = intval($_GET['id'] ?? 0);
$message = "";

// --- HANDLE UPDATE REQUEST (Step 2) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_book'])) {
    $title = $_POST['title'];
    $author = $_POST['author'];
    $isbn = $_POST['isbn'];
    $quantity = intval($_POST['quantity']);

    // CHANGED: Using 'book_id' instead of 'id'
    $update_sql = "UPDATE books SET title=?, author=?, isbn=?, quantity=? WHERE book_id=?";
    $update_stmt = $conn->prepare($update_sql);
    
    if ($update_stmt === false) {
        die("Update Prepare Error: " . $conn->error);
    }

    $update_stmt->bind_param("sssii", $title, $author, $isbn, $quantity, $id);
    
    if ($update_stmt->execute()) {
        header("Location: books.php?status=updated");
        exit;
    } else {
        $message = "<div class='alert alert-danger'>Update failed: " . $conn->error . "</div>";
    }
}

// --- FETCH CURRENT DATA (Step 1) ---
// CHANGED: Using 'book_id' instead of 'id'
$fetch_sql = "SELECT * FROM books WHERE book_id = ?";
$stmt = $conn->prepare($fetch_sql);

if ($stmt === false) {
    // This part was likely causing your Line 34 error
    die("Fetch Prepare Error: " . $conn->error . ". Check if your column is named 'book_id' or 'id'.");
}

$stmt->bind_param("i", $id);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();

if (!$book) {
    die("Book not found in database with ID: " . $id);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Book | Ambo University</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f7f6; padding: 40px; font-family: 'Segoe UI', sans-serif; }
        .edit-container { max-width: 600px; margin: auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .btn-update { background: #0984e3; color: white; border: none; padding: 10px 25px; }
        .header-title { color: #0984e3; border-bottom: 2px solid #0984e3; padding-bottom: 10px; margin-bottom: 20px; }
    </style>
</head>
<body>

<div class="edit-container">
    <h2 class="header-title">Edit Book Details</h2>
    <?= $message ?>
    
    <form method="POST">
        <div class="mb-3">
            <label class="form-label fw-bold">Book Title</label>
            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($book['title']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold">Author</label>
            <input type="text" name="author" class="form-control" value="<?= htmlspecialchars($book['author']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold">ISBN</label>
            <input type="text" name="isbn" class="form-control" value="<?= htmlspecialchars($book['isbn']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold">Quantity</label>
            <input type="number" name="quantity" class="form-control" value="<?= htmlspecialchars($book['quantity']) ?>" required>
        </div>
        
        <div class="d-flex justify-content-between mt-4">
            <a href="books.php" class="btn btn-secondary">Cancel</a>
            <button type="submit" name="update_book" class="btn btn-update">Save Changes</button>
        </div>
    </form>
</div>

</body>
</html>