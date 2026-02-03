<?php
session_start();
include "db.php";
if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit; 
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Student';

// Updated query to include book_id so we can link to the reader
$stmt = $conn->prepare("
    SELECT b.borrow_date, b.return_date, b.status, bk.title, bk.book_id, bk.digital_file
    FROM borrows b 
    JOIN books bk ON b.book_id = bk.book_id 
    WHERE b.user_id = ? 
    ORDER BY b.borrow_date DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$history = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Borrow History | Ambo University</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: #f4f7f6; 
            margin: 0; 
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* HEADER BRANDING */
        header {
            background: linear-gradient(135deg, #0984e3 0%, #065fa7 100%);
            color: white;
            padding: 15px 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }

        /* MAIN CONTENT */
        main { flex: 1; padding: 40px 20px; }
        .container-custom { 
            max-width: 900px; 
            margin: 0 auto; 
            background: white; 
            padding: 30px; 
            border-radius: 15px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.05); 
        }

        h2 { color: #0984e3; font-weight: 700; margin-bottom: 25px; text-align: center; }

        /* LIST STYLES */
        .history-item { 
            border-bottom: 1px solid #eee; 
            padding: 20px 0; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
        }
        .history-item:last-child { border: none; }
        
        .book-info { display: flex; flex-direction: column; flex-grow: 1; }
        .book-title { font-weight: 600; font-size: 1.1rem; color: #2d3436; }
        .date { font-size: 0.85rem; color: #636e72; margin-top: 4px; }
        
        .actions-status { display: flex; align-items: center; gap: 15px; }

        .status { 
            padding: 6px 12px; 
            border-radius: 20px; 
            font-size: 0.75rem; 
            font-weight: bold; 
            text-transform: uppercase; 
            min-width: 90px;
            text-align: center;
        }
        .status.borrowed { background: #fff9db; color: #f08c00; border: 1px solid #ffe8cc; }
        .status.returned { background: #ebfbee; color: #2b8a3e; border: 1px solid #d3f9d8; }

        .read-btn {
            background-color: #0984e3;
            color: white;
            border: none;
            padding: 6px 15px;
            border-radius: 5px;
            font-size: 0.85rem;
            text-decoration: none;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .read-btn:hover { background-color: #065fa7; color: white; transform: translateY(-2px); }

        /* FOOTER BRANDING */
        footer {
            background: linear-gradient(135deg, #003366 0%, #002244 100%);
            color: white;
            padding: 25px 0;
            text-align: center;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

<header>
    <div class="header-content">
        <div class="d-flex align-items-center gap-3">
            <img src="ambo.png" alt="Ambo Logo" style="height: 50px;">
            <div>
                <h5 class="mb-0">Library Portal</h5>
                <small class="text-light-50"><?php echo htmlspecialchars($user_name); ?></small>
            </div>
        </div>
        <nav>
            <a href="dashboard.php" class="btn btn-outline-light btn-sm">Dashboard</a>
        </nav>
    </div>
</header>

<main>
    <div class="container-custom">
        <h2><i class="fas fa-history me-2"></i>Borrowing History</h2>
        
        <?php if($history->num_rows == 0): ?>
            <div class="text-center py-5">
                <i class="fas fa-book-open fa-3x text-light mb-3"></i>
                <p class="text-muted">You haven't borrowed any books yet.</p>
                <a href="borrow.php" class="btn btn-primary btn-sm">Explore Books</a>
            </div>
        <?php endif; ?>

        <?php while($row = $history->fetch_assoc()): ?>
        <div class="history-item">
            <div class="book-info">
                <span class="book-title"><?= htmlspecialchars($row['title']) ?></span>
                <span class="date">
                    <i class="far fa-calendar-alt me-1"></i> 
                    Borrowed: <?= date('M d, Y', strtotime($row['borrow_date'])) ?>
                    <?php if($row['return_date']): ?>
                        <span class="ms-2 text-success">| Returned: <?= date('M d, Y', strtotime($row['return_date'])) ?></span>
                    <?php endif; ?>
                </span>
            </div>
            
            <div class="actions-status">
                <?php if($row['status'] == 'borrowed' && !empty($row['digital_file'])): ?>
                    <a href="read.php?book_id=<?= $row['book_id'] ?>" class="read-btn">
                        <i class="fas fa-book-reader"></i> Read Online
                    </a>
                <?php endif; ?>

                <span class="status <?= htmlspecialchars($row['status']) ?>">
                    <?= htmlspecialchars($row['status']) ?>
                </span>
            </div>
        </div>
        <?php endwhile; ?>

        <div class="text-center mt-4">
            <a href="dashboard.php" class="text-muted text-decoration-none small">
                <i class="fas fa-arrow-left me-1"></i> Return to Dashboard
            </a>
        </div>
    </div>
</main>

<footer>
    <div class="container">
        <p class="mb-0">&copy; 2024 Ambo University Library Management System. Empowering Education.</p>
    </div>
</footer>

</body>
</html>