<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'User';
$msg = "";

// Handle Borrow Request
if ($_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST['book_id'])) {
    $book_id = intval($_POST['book_id']);

    // 1. Check availability
    $check = $conn->prepare("SELECT title, quantity FROM books WHERE book_id = ?");
    $check->bind_param("i", $book_id);
    $check->execute();
    $book = $check->get_result()->fetch_assoc();

    if ($book && $book['quantity'] > 0) {
        $conn->begin_transaction();

        try {
            // 2. Insert Borrow Record
            $stmt = $conn->prepare("INSERT INTO borrows (user_id, book_id, borrow_date, status) VALUES (?, ?, NOW(), 'borrowed')");
            $stmt->bind_param("ii", $user_id, $book_id);
            $stmt->execute();

            // 3. Update Book Quantity
            $update = $conn->prepare("UPDATE books SET quantity = quantity - 1 WHERE book_id = ?");
            $update->bind_param("i", $book_id);
            $update->execute();

            // 4. Send Notification
            $adminRes = $conn->query("SELECT id FROM users WHERE role = 'super_admin' LIMIT 1");
            $admin = $adminRes->fetch_assoc();
            $admin_id = $admin['id'] ?? 1;

            $notif_msg = "Transaction Confirmed: You have successfully borrowed '" . $book['title'] . "'. Please return it on time.";
            $notif = $conn->prepare("INSERT INTO notifications (user_id, admin_id, message) VALUES (?, ?, ?)");
            $notif->bind_param("iis", $user_id, $admin_id, $notif_msg);
            $notif->execute();

            $conn->commit();
            $msg = "Success: Book borrowed successfully!";
        } catch (Exception $e) {
            $conn->rollback();
            $msg = "Error: Transaction failed. Please try again.";
        }
    } else {
        $msg = "Error: This book is currently out of stock.";
    }
}

// Fetch books for dropdown
$books = $conn->query("SELECT * FROM books WHERE quantity > 0");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrow Book | Ambo University</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            margin: 0;
        }

        /* HEADER BRANDING */
        header {
            background: linear-gradient(135deg, #0984e3 0%, #065fa7 100%);
            color: white;
            padding: 15px 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }

        .logo-img {
            width: 150px;
            height: 60px;
            object-fit: contain;
        }

        /* MAIN LAYOUT */
        main {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
        }

        .borrow-card {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
            text-align: center;
        }

        h2 {
            color: #0984e3;
            font-weight: 700;
            margin-bottom: 25px;
        }

        select {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border: 2px solid #e0e0e0;
            margin-bottom: 20px;
            font-size: 16px;
        }

        .btn-borrow {
            background: #0984e3;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            width: 100%;
            transition: 0.3s;
        }

        .btn-borrow:hover {
            background: #065fa7;
            transform: translateY(-2px);
        }

        /* FOOTER BRANDING */
        footer {
            background: linear-gradient(135deg, #003366 0%, #002244 100%);
            color: white;
            padding: 30px 0;
            text-align: center;
            font-size: 0.9rem;
            margin-top: auto;
        }
    </style>
</head>

<body>

    <header>
        <div class="header-content">
            <div class="d-flex align-items-center gap-3">
                <img src="ambo.png" alt="Ambo University Logo" class="logo-img">
                <div>
                    <h4 class="mb-0">Borrowing Portal</h4>
                    <small><?php echo htmlspecialchars(strtoupper($user_name)); ?></small>
                </div>
            </div>
            <nav>
                <a href="dashboard.php" class="btn btn-outline-light btn-sm">Dashboard</a>
            </nav>
        </div>
    </header>

    <main>
        <div class="borrow-card">
            <i class="fas fa-book-reader fa-3x mb-3" style="color: #0984e3;"></i>
            <h2>Borrow a Book</h2>

            <?php if ($msg): ?>
                <div
                    class="alert <?= strpos($msg, 'Success') !== false ? 'alert-success' : 'alert-danger' ?> alert-dismissible fade show">
                        <?= $msg ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="text-start mb-3">
                    <label class="form-label fw-bold">Select Available Book</label>
                    <select name="book_id" class="form-select" required>
                        <option value="">-- Choose from our collection --</option>
                        <?php while ($row = $books->fetch_assoc()): ?>
                            <option value="<?= $row['book_id'] ?>">
                               <?= htmlspecialchars($row['title']) ?> (<?= $row['quantity'] ?> available)
                            </option>
                      <?php endwhile; ?>
                    </select>
                </div>

                <button type="submit" class="btn-borrow">
                    <i class="fas fa-check-circle me-2"></i>Confirm Borrowing
                </button>
            </form>

            <div class="mt-4">
                <a href="dashboard.php" class="text-muted text-decoration-none small">
                    <i class="fas fa-arrow-left me-1"></i> Return to Dashboard
                </a>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p class="mb-0">&copy; 2024 Ambo University Library Management System. All Rights Reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>