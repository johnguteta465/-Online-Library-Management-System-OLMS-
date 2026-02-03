<?php
/**
 * Borrows View Page (borrows_view.php)
 * Shows all book borrowings with book details and user info
 */
session_start();
include "db.php";

// --- SECURITY CHECK ---
$allowedRoles = ['admin', 'super_admin'];

if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowedRoles)) {
    header("Location: login.php");
    exit;
}

$userName = htmlspecialchars($_SESSION['user_name']);
$userRole = htmlspecialchars($_SESSION['user_role']);

// Fetch all borrow records with book and user details
$borrows_query = "
    SELECT 
        b.id as borrow_id,
        b.borrow_date,
        b.return_date,
        b.status,
        u.id as user_id,
        u.name as user_name,
        u.email as user_email,
        bk.book_id,
        bk.title as book_title,
        bk.author as book_author
    FROM borrows b
    JOIN users u ON b.user_id = u.id
    JOIN books bk ON b.book_id = bk.book_id
    ORDER BY b.borrow_date DESC
";

$borrows_result = $conn->query($borrows_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View Borrows | Admin Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* * =========================================
 * 🎨 Harmonized CSS from admin.php
 * =========================================
 */
:root {
    --primary: #4e54c8;      
    --secondary: #8f94fb;    
    --accent: #ff6b6b;       
    --bg-color: #f4f7fc;     
    --text-dark: #2d3436;
    --text-light: #636e72;
    --white: #ffffff;
    --shadow-soft: 0 10px 30px rgba(0,0,0,0.05);
}

body {
    font-family: 'Poppins', sans-serif;
    background: var(--bg-color);
    margin: 0;
    padding: 0;
    color: var(--text-dark);
}

header { 
    background: var(--primary); 
    color: white; 
    padding: 15px 40px; 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    box-shadow: 0 3px 10px rgba(0,0,0,0.15);
}
header h1{margin:0; font-size: 20px;}
header div { font-size: 14px; font-weight: 500; opacity: 0.9; }

.container {
    max-width: 1200px;
    margin: 40px auto;
    background: var(--white);
    padding: 30px;
    border-radius: 16px;
    box-shadow: var(--shadow-soft);
}

h2 {
    color: var(--primary);
    border-bottom: 1px solid #e0e0e0;
    padding-bottom: 10px;
    margin-bottom: 20px;
    font-weight: 700;
}

/* Table Styling */
.borrows-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

.borrows-table th {
    background: var(--primary);
    color: white;
    padding: 12px 15px;
    text-align: left;
    font-weight: 600;
}

.borrows-table td {
    padding: 12px 15px;
    border-bottom: 1px solid #e0e0e0;
}

.borrows-table tr:hover {
    background: #f8f9fa;
}

.borrows-table tr.borrowed {
    background: #fff3cd;
}

.status-badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-borrowed {
    background: #ff6b6b;
    color: white;
}

.status-returned {
    background: #28a745;
    color: white;
}

.action-buttons {
    display: flex;
    gap: 8px;
}

.btn-message {
    background: var(--primary);
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    text-decoration: none;
    display: inline-block;
}

.btn-message:hover {
    opacity: 0.9;
}

.btn-view {
    background: var(--secondary);
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    text-decoration: none;
    display: inline-block;
}

.btn-view:hover {
    opacity: 0.9;
}

/* Stats Cards */
.stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: var(--shadow-soft);
    text-align: center;
}

.stat-number {
    font-size: 28px;
    font-weight: 700;
    color: var(--primary);
    margin: 10px 0;
}

.stat-label {
    color: var(--text-light);
    font-size: 14px;
}

/* Filter Section */
.filter-section {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.filter-form {
    display: flex;
    gap: 15px;
    align-items: center;
    flex-wrap: wrap;
}

.filter-form select, .filter-form input {
    padding: 8px 12px;
    border-radius: 6px;
    border: 1px solid #ddd;
}

.filter-form button {
    background: var(--primary);
    color: white;
    border: none;
    padding: 8px 20px;
    border-radius: 6px;
    cursor: pointer;
}

/* Back Link */
.back-link {
    display: block;
    width: fit-content;
    margin-top: 20px;
    text-decoration: none;
    color: var(--primary);
    font-weight: 600;
    padding: 10px 0;
    transition: 0.3s;
}
.back-link:hover {
    color: var(--secondary);
}

.no-data {
    text-align: center;
    padding: 40px;
    color: var(--text-light);
    font-style: italic;
}
</style>
</head>
<body>

<header>
    <h1>Book Borrowing Records</h1>
    <div>Admin: <?= $userName ?> (<?= strtoupper($userRole) ?>)</div>
</header>

<div class="container">
    <h2><i class="fas fa-book-reader"></i> All Borrowing Records</h2>
    
    <!-- Statistics -->
    <?php
    // Get stats
    $total_borrows = $conn->query("SELECT COUNT(*) as total FROM borrows")->fetch_assoc()['total'];
    $active_borrows = $conn->query("SELECT COUNT(*) as active FROM borrows WHERE status = 'borrowed'")->fetch_assoc()['active'];
    $returned_books = $conn->query("SELECT COUNT(*) as returned FROM borrows WHERE status = 'returned'")->fetch_assoc()['returned'];
    $unique_users = $conn->query("SELECT COUNT(DISTINCT user_id) as users FROM borrows")->fetch_assoc()['users'];
    ?>
    
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-number"><?= $total_borrows ?></div>
            <div class="stat-label">Total Borrows</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $active_borrows ?></div>
            <div class="stat-label">Active Borrows</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $returned_books ?></div>
            <div class="stat-label">Returned Books</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $unique_users ?></div>
            <div class="stat-label">Unique Users</div>
        </div>
    </div>
    
    <!-- Filter Section -->
    <div class="filter-section">
        <form method="GET" class="filter-form">
            <select name="status">
                <option value="">All Status</option>
                <option value="borrowed">Borrowed</option>
                <option value="returned">Returned</option>
            </select>
            <input type="text" name="search" placeholder="Search user or book...">
            <button type="submit">Filter</button>
            <a href="borrows_view.php" style="margin-left: auto;">Clear Filters</a>
        </form>
    </div>
    
    <!-- Borrows Table -->
    <table class="borrows-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Book</th>
                <th>Borrow Date</th>
                <th>Return Date</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($borrows_result->num_rows > 0): ?>
                <?php while($borrow = $borrows_result->fetch_assoc()): ?>
                <tr class="<?= $borrow['status'] == 'borrowed' ? 'borrowed' : '' ?>">
                    <td>#<?= $borrow['borrow_id'] ?></td>
                    <td>
                        <strong><?= htmlspecialchars($borrow['user_name']) ?></strong><br>
                        <small><?= htmlspecialchars($borrow['user_email']) ?></small><br>
                        <small>User ID: <?= $borrow['user_id'] ?></small>
                    </td>
                    <td>
                        <strong><?= htmlspecialchars($borrow['book_title']) ?></strong><br>
                        <small>by <?= htmlspecialchars($borrow['book_author']) ?></small><br>
                        <small>Book ID: <?= $borrow['book_id'] ?></small>
                    </td>
                    <td><?= date('Y-m-d H:i', strtotime($borrow['borrow_date'])) ?></td>
                    <td>
                        <?php if ($borrow['return_date']): ?>
                            <?= date('Y-m-d H:i', strtotime($borrow['return_date'])) ?>
                        <?php else: ?>
                            <em>Not returned</em>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="status-badge status-<?= $borrow['status'] ?>">
                            <?= ucfirst($borrow['status']) ?>
                        </span>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <a href="send_message.php?user_id=<?= $borrow['user_id'] ?>" 
                               class="btn-message" 
                               title="Send message to this user">
                                <i class="fas fa-envelope"></i> Message
                            </a>
                            <a href="user_borrows.php?user_id=<?= $borrow['user_id'] ?>" 
                               class="btn-view" 
                               title="View user's borrowing history">
                                <i class="fas fa-history"></i> History
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="no-data">
                        <i class="fas fa-book-open" style="font-size: 48px; margin-bottom: 15px;"></i><br>
                        No borrowing records found.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <a href="admin.php" class="back-link">⬅ Back to Admin Dashboard</a>
</div>

<script>
// Add confirmation for sending messages
document.querySelectorAll('.btn-message').forEach(button => {
    button.addEventListener('click', function(e) {
        if (!confirm('Send message to this user?')) {
            e.preventDefault();
        }
    });
});

// Highlight overdue books (if due date exists)
document.addEventListener('DOMContentLoaded', function() {
    // This is a placeholder - you'll need to implement due date logic
    // Currently your schema doesn't have a due_date column
});
</script>
</body>
</html>