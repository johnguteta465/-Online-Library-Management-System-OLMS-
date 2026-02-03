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

if ($_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST['borrow_id'])) {
    $borrow_id = intval($_POST['borrow_id']);

    // Find the specific borrow record
    $stmt = $conn->prepare("SELECT b.book_id, bk.title FROM borrows b JOIN books bk ON b.book_id = bk.book_id WHERE b.id = ? AND b.user_id = ? AND b.status = 'borrowed'");
    $stmt->bind_param("ii", $borrow_id, $user_id);
    $stmt->execute();
    $record = $stmt->get_result()->fetch_assoc();

    if ($record) {
        $conn->begin_transaction();
        try {
            $book_id = $record['book_id'];

            // 1. Mark as returned
            $upBorrow = $conn->prepare("UPDATE borrows SET status = 'returned', return_date = NOW() WHERE id = ?");
            $upBorrow->bind_param("i", $borrow_id);
            $upBorrow->execute();

            // 2. Increase Stock
            $upStock = $conn->prepare("UPDATE books SET quantity = quantity + 1 WHERE book_id = ?");
            $upStock->bind_param("i", $book_id);
            $upStock->execute();

            // 3. Notification logic
            $adminRes = $conn->query("SELECT id FROM users WHERE role = 'super_admin' LIMIT 1");
            $admin = $adminRes->fetch_assoc();
            $admin_id = $admin['id'] ?? 1;

            $notif_msg = "Return Received: '" . $record['title'] . "' has been returned. Thank you!";
            $notif = $conn->prepare("INSERT INTO notifications (user_id, admin_id, message) VALUES (?, ?, ?)");
            $notif->bind_param("iis", $user_id, $admin_id, $notif_msg);
            $notif->execute();

            $conn->commit();
            $msg = "Success: Book returned successfully!";
        } catch (Exception $e) {
            $conn->rollback();
            $msg = "Error: Could not process return.";
        }
    }
}

// Fetch currently borrowed books
$borrowed = $conn->prepare("SELECT b.id, bk.title FROM borrows b JOIN books bk ON b.book_id = bk.book_id WHERE b.user_id = ? AND b.status = 'borrowed'");
$borrowed->bind_param("i", $user_id);
$borrowed->execute();
$list = $borrowed->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Return Book | Ambo University</title>
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

        /* HEADER COLOR STYLE */
        header {
            background: linear-gradient(135deg, #0984e3 0%, #065fa7 100%);
            color: white;
            padding: 15px 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }
        .logo-img { width: 150px; height: 60px; object-fit: contain; }

        /* MAIN CONTENT */
        main {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
        }

        .return-card {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 500px;
            text-align: center;
        }

        h2 { color: #0984e3; font-weight: 700; margin-bottom: 25px; }
        
        select {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border: 2px solid #e0e0e0;
            margin-bottom: 20px;
            font-size: 16px;
        }

        .btn-submit {
            background: #0984e3;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            width: 100%;
            transition: 0.3s;
        }

        .btn-submit:hover { background: #065fa7; transform: translateY(-2px); }

        /* FOOTER COLOR STYLE */
        footer {
            background: linear-gradient(135deg, #003366 0%, #002244 100%);
            color: white;
            padding: 30px 0;
            text-align: center;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

<header>
    <div class="header-content">
        <div class="d-flex align-items-center gap-3">
            <img src="ambo.png" alt="Ambo Logo" class="logo-img">
            <div>
                <h4 class="mb-0">Return Portal</h4>
                <small><?php echo strtoupper($user_name); ?></small>
            </div>
        </div>
        <nav>
            <a href="dashboard.php" class="btn btn-outline-light btn-sm">Dashboard</a>
        </nav>
    </div>
</header>

<main>
    <div class="return-card">
        <i class="fas fa-undo-alt fa-3x mb-3" style="color: #0984e3;"></i>
        <h2>Return a Book</h2>
        
        <?php if($msg): ?>
            <div class="alert alert-success"><?= $msg ?></div>
        <?php endif; ?>

        <form method="POST">
            <label class="form-label d-block text-start fw-bold">Select book to return:</label>
            <select name="borrow_id" required>
                <option value="">-- Choose from your active loans --</option>
                <?php while($row = $list->fetch_assoc()): ?>
                    <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['title']) ?></option>
                <?php endwhile; ?>
            </select>
            
            <button type="submit" class="btn-submit">Confirm Return</button>
        </form>
        
        <div class="mt-4">
            <a href="dashboard.php" class="text-muted text-decoration-none small">
                <i class="fas fa-arrow-left me-1"></i> Back to Student Dashboard
            </a>
        </div>
    </div>
</main>

<footer>
    <div class="container">
        <p class="mb-0">&copy; 2024 Ambo University Library Management System. All Rights Reserved.</p>
    </div>
</footer>

</body>
</html>