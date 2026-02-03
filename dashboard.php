<?php
/**
 * User Dashboard (dashboard.php)
 * LAYOUT: Sidebar + Header (Account) + Main Content (Services)
 */
session_start();
include "db.php"; 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$current_id = $_SESSION['user_id'];
$userName = htmlspecialchars($_SESSION['user_name'] ?? 'Library User');
$userRole = $_SESSION['user_role'] ?? 'user';
$profilePicPath = "assets/avatars/default.png"; // Default

// Fetch Profile Pic
if (isset($conn)) {
    $sql = "SELECT profile_pic FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $current_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        if ($res && $res['profile_pic'] && file_exists($res['profile_pic'])) {
            $profilePicPath = $res['profile_pic'];
        }
    }
    
    // Fetch Notifications
    $notif_sql = "SELECT COUNT(*) as count FROM messages WHERE recipient_id = ? AND is_read = FALSE";
    $n_stmt = $conn->prepare($notif_sql);
    $n_stmt->bind_param("i", $current_id);
    $n_stmt->execute();
    $unread_count = $n_stmt->get_result()->fetch_assoc()['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Dashboard | Library</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        :root {
            --primary: #4e54c8;
            --sidebar-bg: #1e272e;
            --header-bg: #ffffff;
            --bg-body: #f1f2f6;
            --accent: #ff4757;
            --text-main: #2f3542;
        }

        body { font-family: 'Poppins', sans-serif; margin: 0; display: flex; background: var(--bg-body); }

        /* --- SIDEBAR --- */
        .sidebar {
            width: 250px;
            height: 100vh;
            background: var(--sidebar-bg);
            color: white;
            position: fixed;
            display: flex;
            flex-direction: column;
        }
        .sidebar-brand { padding: 25px; text-align: center; border-bottom: 1px solid #34495e; }
        .sidebar-menu { flex-grow: 1; padding: 20px 0; }
        .sidebar-menu a {
            padding: 15px 25px;
            display: flex;
            align-items: center;
            color: #d2dae2;
            text-decoration: none;
            transition: 0.3s;
        }
        .sidebar-menu a:hover { background: var(--primary); color: white; }
        .sidebar-menu i { margin-right: 15px; width: 20px; }

        /* --- MAIN CONTENT --- */
        .main-container { margin-left: 250px; width: calc(100% - 250px); }

        /* --- HEADER --- */
        .top-header {
            height: 70px;
            background: var(--header-bg);
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding: 0 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .header-actions { display: flex; align-items: center; gap: 20px; }
        .header-actions a {
            text-decoration: none;
            color: var(--text-main);
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .header-actions a.logout { color: var(--accent); }
        .user-pill {
            display: flex;
            align-items: center;
            gap: 10px;
            padding-left: 20px;
            border-left: 1px solid #eee;
        }
        .user-pill img { width: 35px; height: 35px; border-radius: 50%; object-fit: cover; }

        /* --- SERVICE CARDS --- */
        .content { padding: 40px; }
        .section-title { margin-bottom: 30px; font-weight: 600; color: var(--text-main); }
        
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
        }
        .card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            text-decoration: none;
            color: var(--text-main);
            transition: 0.3s;
            position: relative;
        }
        .card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
        .card i { font-size: 35px; color: var(--primary); margin-bottom: 20px; display: block; }
        .card h3 { margin: 0 0 10px 0; font-size: 18px; }
        .card p { font-size: 13px; color: #747d8c; margin: 0; line-height: 1.5; }
        
        .badge {
            background: var(--accent);
            color: white;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 11px;
            position: absolute;
            top: 20px;
            right: 20px;
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-brand">
            <h2 style="font-size: 20px; margin: 0;">📚 Library Portal</h2>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
            <a href="borrow.php"><i class="fa-solid fa-book-medical"></i> Borrow</a>
            <a href="return.php"><i class="fa-solid fa-rotate-left"></i> Return</a>
            <a href="my_borrows.php"><i class="fa-solid fa-list-check"></i> My Borrows</a>
            <a href="online_library.php"><i class="fa-solid fa-book-open-reader"></i> Read Online</a>
            <a href="notifications.php"><i class="fa-solid fa-bell"></i> Notifications</a>
        </div>
    </div>

    <div class="main-container">
        <header class="top-header">
            <div class="header-actions">
                <a href="edit_profile.php"><i class="fa-solid fa-user-gear"></i> Edit Profile</a>
                <a href="logout.php" class="logout"><i class="fa-solid fa-power-off"></i> Logout</a>
                <div class="user-pill">
                    <span><?= $userName ?></span>
                    <img src="<?= $profilePicPath ?>" alt="User">
                </div>
            </div>
        </header>

        <div class="content">
            <h2 class="section-title">Library Services</h2>
            
            <div class="services-grid">
                <a href="borrow.php" class="card">
                    <i class="fa-solid fa-cart-plus"></i>
                    <h3>Borrow Book</h3>
                    <p>Search and request new books from our physical collection.</p>
                </a>

                <a href="return.php" class="card">
                    <i class="fa-solid fa-calendar-check"></i>
                    <h3>Return Book</h3>
                    <p>Process your returns or check due dates for current books.</p>
                </a>

                <a href="my_borrows.php" class="card">
                    <i class="fa-solid fa-book"></i>
                    <h3>My Borrowed Books</h3>
                    <p>View history and manage books you currently have in hand.</p>
                </a>

                <a href="online_library.php" class="card">
                    <i class="fa-solid fa-laptop-code"></i>
                    <h3>online_library</h3>
                    <p>Instant access to digital E-books and research journals.</p>
                </a>

                <a href="notifications.php" class="card">
                    <?php if($unread_count > 0): ?>
                        <span class="badge"><?= $unread_count ?> New</span>
                    <?php endif; ?>
                    <i class="fa-solid fa-envelope-open-text" style="color: #ffa502;"></i>
                    <h3>Notifications</h3>
                    <p>Stay updated on book arrivals, fines, and account alerts.</p>
                </a>
            </div>
        </div>
    </div>

</body>
</html>