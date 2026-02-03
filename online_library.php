<?php
session_start();
include 'db.php';

// SECURITY CHECK
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Redirect admin roles away
if (in_array(($_SESSION['user_role'] ?? 'user'), ['admin', 'super_admin'])) {
    header("Location: dashboard.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// FIXED SQL: Changed b.id to b.book_id to match your database structure
$sql = "
    SELECT 
        b.`book_id`,        
        b.`title`, 
        b.`author`, 
        b.`quantity`,              
        b.`is_available_online`,   
        b.`cover` AS cover_image,
        (SELECT COUNT(id) FROM borrows WHERE user_id = ? AND book_id = b.book_id AND status = 'borrowed') AS is_borrowed_by_user
    FROM `books` b
    ORDER BY b.`title` ASC
";

if (!isset($conn) || $conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$stmt = $conn->prepare($sql);
if ($stmt === FALSE) {
    die("SQL Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$data_to_loop = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Library | Ambo OLMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        :root {
            --primary: #4e54c8;
            --bg: #eef1f7;
            --white: #fff;
        }

        body {
            font-family: Poppins, sans-serif;
            background: var(--bg);
            margin: 0;
            padding: 20px;
        }

        .header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 30px;
            background: var(--primary);
            color: var(--white);
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: var(--white);
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .book-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        /* PROFESSIONAL FULL COVER LOOK */
        .book-card {
            background: #fff;
            border: 1px solid #eee;
            border-radius: 12px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: 0.3s;
        }

        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .image-container {
            width: 100%;
            height: 320px;
            background: #f0f0f0;
        }

        .image-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .book-info {
            padding: 15px;
            text-align: center;
        }

        .read-btn {
            display: block;
            padding: 10px;
            background: var(--primary);
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 10px;
        }
    </style>
</head>

<body>

    <div class="header-bar">
        <h1><i class="fa-solid fa-book-reader"></i> Ambo OLMS</h1>
        <a href="dashboard.php" style="color: white; text-decoration: none;">Back to Dashboard</a>
    </div>

    <div class="container">
        <h2>Available Resources</h2>
        <div class="book-grid">
            <?php foreach ($data_to_loop as $book):
                // FIXED IMAGE LOGIC: Handle '0' values from the database
                $img = $book['cover_image'];
                $display_img = ($img == '0' || empty($img)) ? 'assets/img/default_cover.jpg' : $img;
                ?>
                <div class="book-card">
                    <div class="image-container">
                        <img src="<?= htmlspecialchars($display_img) ?>" alt="Cover">
                    </div>
                    <div class="book-info">
                        <h4><?= htmlspecialchars($book['title']) ?></h4>
                        <p>By: <?= htmlspecialchars($book['author']) ?></p>

                        <?php if ($book['is_borrowed_by_user'] > 0 && $book['is_available_online']): ?>
                            <a href="view_book.php?book_id=<?= $book['book_id'] ?>" class="read-btn">
                                <i class="fa-solid fa-eye"></i> Read Online
                            </a>
                        <?php else: ?>
                            <a href="borrow.php?book_id=<?= $book['book_id'] ?>" class="read-btn" style="background: #e1b12c;">
                                <i class="fa-solid fa-hand-holding-hand"></i> Borrow Now
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>

</html>