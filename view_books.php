<?php
session_start();
include "db.php";

// Fetch all books
$result = $conn->query("SELECT * FROM books");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Books - Ambo University</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ... keeping your existing styles ... */
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; margin: 0; }
        header { background-color: #0984e3; color: white; padding: 20px; text-align: center; }
        .container { width: 95%; max-width: 1200px; margin: 20px auto; }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #0984e3; color: white; }
        
        /* Action Button Styles */
        .btn-add { background: #27ae60; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-bottom: 20px; }
        .btn-edit { color: #0984e3; margin-right: 10px; text-decoration: none; }
        .btn-delete { color: #e74c3c; text-decoration: none; }
        .btn-back { display: block; width: fit-content; margin: 20px auto; padding: 10px 20px; background: #636e72; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>

<header>
    <h1>AMBO UNIVERSITY ONLINE LIBRARY<br>BOOK MANAGEMENT</h1>
</header>

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h2>Available Books</h2>
        <a href="add_book.php" class="btn-add"><i class="fas fa-plus"></i> Add New Book</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Author</th>
                <th>ISBN</th>
                <th>Qty</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): 
                    $row = array_change_key_case($row, CASE_LOWER);
                    $id = $row['id'] ?? $row['book_id']; // Handle different column names
                ?>
                    <tr>
                        <td><?= $id ?></td>
                        <td><?= htmlspecialchars($row['title']) ?></td>
                        <td><?= htmlspecialchars($row['author']) ?></td>
                        <td><?= htmlspecialchars($row['isbn']) ?></td>
                        <td><?= htmlspecialchars($row['quantity']) ?></td>
                        <td>
                            <a href="edit_book.php?id=<?= $id ?>" class="btn-edit" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="delete_book.php?id=<?= $id ?>" class="btn-delete" 
                               onclick="return confirm('Are you sure you want to delete this book?');" title="Delete">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6" style="text-align:center;">No books found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <a href="admin.php" class="btn-back">⬅ Back to Dashboard</a>
</div>
</body>
</html>