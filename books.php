<?php
session_start();
include "db.php";

// --- SECURITY CHECK ---
// Only admins and super_admins should access this page
$allowedRoles = ['admin', 'super_admin'];
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowedRoles)) {
    header("Location: login.php");
    exit;
}

// Fetch all books from the database
$result = $conn->query("SELECT * FROM books ORDER BY book_id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Books | Ambo University</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --ambo-blue: #0984e3; --ambo-dark: #2d3436; }
        body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        header { 
            background: var(--ambo-blue); 
            color: white; 
            padding: 2rem; 
            text-align: center; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .container { margin-top: 30px; }
        
        .table-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.05);
        }

        .table thead { background-color: var(--ambo-blue); color: white; }
        
        .btn-add { background-color: #27ae60; color: white; transition: 0.3s; }
        .btn-add:hover { background-color: #219150; color: white; transform: translateY(-2px); }
        
        .action-btns a { margin: 0 5px; font-size: 1.1rem; }
        .edit-icon { color: #0984e3; }
        .delete-icon { color: #e74c3c; }
        
        .status-badge { font-size: 0.85rem; padding: 5px 10px; }
    </style>
</head>
<body>

<header>
    <h1 class="h3 mb-0">AMBO UNIVERSITY HACHALU HUNDESSA CAMPUS</h1>
    <p class="mb-0 text-uppercase small tracking-widest">Online Library Management System</p>
</header>

<div class="container">
    <?php if(isset($_GET['status'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            Action completed successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="table-card">
        <div class="d-flex justify-content-between align-items: center mb-4">
            <h2 class="h4 mb-0"><i class="fas fa-book me-2"></i>Book Collection</h2>
            <a href="add_book.php" class="btn btn-add shadow-sm">
                <i class="fas fa-plus-circle me-2"></i>Add New Book
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>ISBN</th>
                        <th>Stock</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td class="fw-bold">#<?= $row['book_id'] ?></td>
                                <td><?= htmlspecialchars($row['title']) ?></td>
                                <td><?= htmlspecialchars($row['author']) ?></td>
                                <td><code><?= htmlspecialchars($row['isbn']) ?></code></td>
                                <td>
                                    <?php if($row['quantity'] > 0): ?>
                                        <span class="badge bg-success status-badge"><?= $row['quantity'] ?> In Stock</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger status-badge">Out of Stock</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center action-btns">
                                    <a href="edit_book.php?id=<?= $row['book_id'] ?>" class="edit-icon" title="Edit Book">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="delete_book.php?id=<?= $row['book_id'] ?>" class="delete-icon" 
                                       onclick="return confirm('Delete this book permanently?');" title="Delete Book">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fas fa-folder-open fa-3x mb-3 d-block"></i>
                                No books found in the library database.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="text-center mt-4 mb-5">
        <a href="admin.php" class="btn btn-outline-secondary px-4">
            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>