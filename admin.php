<?php
/**
 * Library Admin Dashboard (admin.php) - CONVERTED TO SIDEBAR LAYOUT
 * Profile picture logic retrieves the precise path stored in the database.
 */
session_start();
include "db.php"; // Assumes db.php contains $conn

// --- SECURITY CHECK ---
$allowedRoles = ['admin', 'super_admin'];

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowedRoles)) {
    header("Location: login.php");
    exit;
}

// Load user data
$userId = $_SESSION['user_id'] ?? 0;
$userName = htmlspecialchars($_SESSION['user_name'] ?? 'Admin');
$userRole = htmlspecialchars($_SESSION['user_role'] ?? 'admin');

// --- PHP LOGIC FOR PROFILE PICTURE ---
$base_path = 'images/profile_pics/';
$default_image = $base_path . 'default_admin.png';
$profilePicUrl = $default_image;

// 1. Fetch the profile_pic path from the database
if ($userId && isset($conn) && !$conn->connect_error) {
    $stmt = $conn->prepare("SELECT name, profile_pic FROM users WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($user_data = $result->fetch_assoc()) {
                $db_path = $user_data['profile_pic'] ?? null;
                $_SESSION['user_name'] = $user_data['name'];
                $userName = htmlspecialchars($user_data['name']);

                // 2. Use DB path if the file actually exists on disk
                if ($db_path && file_exists($db_path) && !is_dir($db_path)) {
                    $profilePicUrl = $db_path;
                } elseif ($db_path && strpos($db_path, 'profile_pics/') !== false && file_exists($db_path)) {
                    $profilePicUrl = $db_path;
                }
            }
        }
        $stmt->close();
    }
}
// ---------------------------------------------

// Initialize metrics
$totalBooks = '0';
$registeredUsers = '0';
$borrowedBooks = '0';
$overdueBooks = 'N/A';
$internetProgramStock = '0';

// Fetch dashboard metrics
if (isset($conn) && !$conn->connect_error) {
    // 1. Total Books in Stock
    $result = $conn->query("SELECT COUNT(book_id) AS count FROM books");
    if ($result && $row = $result->fetch_assoc()) {
        $totalBooks = number_format($row['count']);
    }

    // 2. Registered Users (regular users only)
    $result = $conn->query("SELECT COUNT(id) AS count FROM users WHERE role = 'user'");
    if ($result && $row = $result->fetch_assoc()) {
        $registeredUsers = number_format($row['count']);
    }

    // 3. Books Currently Borrowed (not returned)
    $result = $conn->query("SELECT COUNT(id) AS count FROM borrows WHERE status = 'borrowed'");
    if ($result && $row = $result->fetch_assoc()) {
        $borrowedBooks = number_format($row['count']);
    }

    // 4. Overdue Books - Not implemented in current schema, so show N/A
    // If you want to implement overdue, you need to add a due_date column to borrows table

    // 5. Specific Book Stock ("Internet Programing")
    $bookTitle = "Internet Programing";
    $stmt = $conn->prepare("SELECT quantity FROM books WHERE title = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $bookTitle);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $internetProgramStock = number_format($row['quantity']);
        }
        $stmt->close();
    }
}

// Helper function to render a sidebar item
function renderSidebarItem($href, $icon, $text, $isActive = false)
{
    $activeClass = $isActive ? 'active' : '';
    $icon = htmlspecialchars($icon);
    $text = htmlspecialchars($text);
    return <<<HTML
    <a href="{$href}" class="sidebar-item {$activeClass}">
        <i class="fa-solid fa-{$icon}"></i>
        <span>{$text}</span>
    </a>
HTML;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Ambo University Library</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <style>
        /* =========================================
 * 🎨 PREMIUM DASHBOARD STYLES
 * ========================================= */

        /* ===== Modern Color Palette ===== */
        :root {
            --primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --primary-solid: #0984e3;
            /* Changed to match register.php blue */
            --secondary: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --accent: #ff6b8b;
            --success: #2ecc71;
            --warning: #f39c12;
            --info: #3498db;
            --bg-color: #f8fafc;
            --card-bg: #ffffff;
            --text-dark: #2d3436;
            --text-light: #718096;
            --sidebar-bg: linear-gradient(180deg, #1a237e 0%, #283593 100%);
            --sidebar-text: #e2e8f0;
            --shadow-soft: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-medium: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-heavy: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --sidebar-width: 280px;
            --header-height: 100px;
            /* Increased to match register.php */
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --radius-lg: 16px;
            --radius-md: 12px;
            --radius-sm: 8px;
        }

        /* ===== General Reset & Body ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
        }

        /* ===== HEADER - Same as register.php ===== */
        header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background-color: #0984e3;
            /* Primary Blue */
            color: white;
            padding: 10px 30px;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            height: 120px;
        }

        header .left {
            display: flex;
            align-items: center;
        }

        header img {
            width: 200px;
            height: 100px;
            object-fit: cover;
            margin-right: 15px;
        }

        /* Navigation in header */
        .header-nav {
            display: flex;
            gap: 20px;
        }

        .header-nav a {
            color: white;
            text-decoration: none;
            font-weight: bold;
            font-size: 16px;
            padding: 8px 15px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .header-nav a:hover {
            background-color: rgba(255, 255, 255, 0.2);
            text-decoration: none;
        }

        /* User Profile in header */
        .user-profile {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-info {
            text-align: right;
        }

        .user-name {
            display: block;
            font-size: 16px;
            font-weight: bold;
            color: white;
        }

        .user-role {
            display: block;
            font-size: 12px;
            color: rgba(255, 255, 255, 0.9);
            text-transform: uppercase;
        }

        .avatar-circle {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            border: 3px solid white;
        }

        .avatar-circle img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Dropdown menu */
        .dropdown-menu {
            display: none;
            position: absolute;
            top: 130px;
            right: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            min-width: 200px;
            z-index: 1001;
            border: 1px solid #e1e5eb;
        }

        .dropdown-menu a {
            display: block;
            padding: 12px 20px;
            color: #333;
            text-decoration: none;
            border-bottom: 1px solid #eee;
            transition: background 0.3s;
        }

        .dropdown-menu a:hover {
            background: #f8f9fa;
            color: #0984e3;
        }

        .dropdown-menu a:last-child {
            border-bottom: none;
        }

        .dropdown-menu.show {
            display: block;
        }

        /* Main Container */
        .main-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            position: fixed;
            top: 120px;
            /* Below header */
            left: 0;
            height: calc(100vh - 120px);
            display: flex;
            flex-direction: column;
            padding: 25px 0;
            z-index: 999;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.15);
            transition: var(--transition);
            overflow-y: auto;
        }

        .sidebar .brand {
            padding: 0 25px 30px 25px;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: var(--transition);
        }

        .sidebar .brand:hover {
            opacity: 0.9;
        }

        .sidebar .brand img {
            height: 45px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .sidebar .brand span {
            font-weight: 700;
            font-size: 1.4rem;
            color: white;
            letter-spacing: 0.5px;
        }

        /* Navigation Items */
        .sidebar-nav {
            flex-grow: 1;
            padding: 0 15px;
            overflow-y: auto;
        }

        .sidebar-item {
            display: flex;
            align-items: center;
            padding: 14px 20px;
            margin: 8px 0;
            color: var(--sidebar-text);
            text-decoration: none;
            font-size: 15px;
            font-weight: 500;
            border-radius: var(--radius-md);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .sidebar-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.6s ease;
        }

        .sidebar-item:hover::before {
            left: 100%;
        }

        .sidebar-item i {
            width: 24px;
            text-align: center;
            font-size: 18px;
            margin-right: 15px;
            transition: var(--transition);
        }

        .sidebar-item:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .sidebar-item.active {
            background: linear-gradient(90deg, rgba(255, 255, 255, 0.2) 0%, rgba(255, 255, 255, 0.1) 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            font-weight: 600;
            border-left: 4px solid var(--accent);
        }

        .sidebar-item.active i {
            color: white;
            transform: scale(1.1);
        }

        /* Section Headers */
        .sidebar-section {
            color: rgba(255, 255, 255, 0.6);
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 25px 20px 8px 20px;
            padding-bottom: 5px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Logout Button */
        .sidebar-logout {
            margin-top: auto;
            padding: 20px 25px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-logout .sidebar-item {
            color: #ff6b6b;
            background: rgba(255, 107, 107, 0.1);
        }

        .sidebar-logout .sidebar-item:hover {
            background: rgba(255, 107, 107, 0.2);
            color: #ff5252;
        }

        /* Content Area */
        .content-area {
            flex-grow: 1;
            margin-left: var(--sidebar-width);
            margin-top: 120px;
            /* Header height */
            padding: 40px;
            min-height: calc(100vh - 120px);
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        }

        /* Dashboard Content */
        .dashboard {
            max-width: 1200px;
            margin: 0 auto;
        }

        .dashboard-header {
            margin-bottom: 50px;
            text-align: left;
            position: relative;
        }

        .dashboard-header h1 {
            font-size: 36px;
            font-weight: 700;
            background: linear-gradient(135deg, #0984e3 0%, #065fa7 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
            display: inline-block;
        }

        .dashboard-header p {
            color: var(--text-light);
            font-size: 16px;
            max-width: 600px;
        }

        .welcome-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, #0984e3 0%, #065fa7 100%);
            color: white;
            padding: 8px 20px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 500;
            margin-top: 15px;
            box-shadow: 0 4px 15px rgba(9, 132, 227, 0.3);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-bottom: 50px;
        }

        /* Stat Cards */
        .stat-card {
            background: var(--card-bg);
            padding: 30px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-soft);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(to bottom, #0984e3, #065fa7);
        }

        .stat-card:nth-child(2)::before {
            background: linear-gradient(to bottom, #2ecc71, #27ae60);
        }

        .stat-card:nth-child(3)::before {
            background: linear-gradient(to bottom, #f39c12, #e67e22);
        }

        .stat-card:nth-child(4)::before {
            background: linear-gradient(to bottom, #e74c3c, #c0392b);
        }

        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-heavy);
        }

        .stat-card:hover .stat-icon {
            transform: scale(1.1) rotate(5deg);
        }

        .stat-icon {
            font-size: 40px;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #0984e3 0%, #065fa7 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background-color: rgba(9, 132, 227, 0.1);
            transition: var(--transition);
        }

        .stat-card:nth-child(2) .stat-icon {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            -webkit-background-clip: text;
            background-color: rgba(46, 204, 113, 0.1);
        }

        .stat-card:nth-child(3) .stat-icon {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            -webkit-background-clip: text;
            background-color: rgba(243, 156, 18, 0.1);
        }

        .stat-card:nth-child(4) .stat-icon {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            -webkit-background-clip: text;
            background-color: rgba(231, 76, 60, 0.1);
        }

        .stat-number {
            font-size: 36px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 5px;
            display: block;
        }

        .stat-label {
            color: var(--text-light);
            font-size: 14px;
            font-weight: 500;
            display: block;
        }

        /* Special Book Card */
        .special-book-card {
            grid-column: span 2;
            background: linear-gradient(135deg, #0984e3 0%, #065fa7 100%);
            color: white;
            padding: 40px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-heavy);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .special-book-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .special-book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 50px rgba(9, 132, 227, 0.3);
        }

        .special-book-card .stat-number {
            color: white;
            font-size: 42px;
        }

        .special-book-card .stat-label {
            color: rgba(255, 255, 255, 0.9);
            font-size: 16px;
        }

        .special-book-card .stat-icon {
            color: white;
            background: rgba(255, 255, 255, 0.2);
            font-size: 48px;
        }

        /* Activity Section */
        .activity-section {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-soft);
            padding: 40px;
            margin-top: 40px;
            border: 1px solid #e2e8f0;
        }

        .activity-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .activity-header h2 {
            font-size: 24px;
            font-weight: 600;
            color: var(--text-dark);
        }

        .view-all {
            color: var(--primary-solid);
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: var(--transition);
        }

        .view-all:hover {
            gap: 10px;
        }

        /* Activity Items */
        .activity-item {
            display: flex;
            align-items: center;
            padding: 20px;
            border-radius: var(--radius-md);
            background: #f8fafc;
            margin-bottom: 15px;
            transition: var(--transition);
            border-left: 4px solid var(--primary-solid);
        }

        .activity-item:hover {
            background: white;
            transform: translateX(5px);
            box-shadow: var(--shadow-soft);
        }

        .activity-item:nth-child(2) {
            border-left-color: var(--success);
        }

        .activity-item:nth-child(3) {
            border-left-color: var(--warning);
        }

        .activity-item i {
            font-size: 20px;
            color: var(--primary-solid);
            margin-right: 15px;
            width: 24px;
        }

        .activity-item:nth-child(2) i {
            color: var(--success);
        }

        .activity-item:nth-child(3) i {
            color: var(--warning);
        }

        .activity-content p {
            margin: 0;
            color: var(--text-dark);
            font-weight: 500;
        }

        .activity-time {
            font-size: 12px;
            color: var(--text-light);
            margin-top: 5px;
        }

        /* VIDEO MANAGEMENT SECTION */
        .activity-section h2 i {
            margin-right: 10px;
        }

        /* Mobile Responsiveness */
        @media (max-width: 1200px) {
            .special-book-card {
                grid-column: span 1;
            }

            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .content-area {
                margin-left: 0;
            }

            .mobile-menu-btn {
                display: block;
                background: none;
                border: none;
                color: white;
                font-size: 24px;
                cursor: pointer;
            }
        }

        @media (max-width: 992px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            header {
                flex-direction: column;
                height: auto;
                padding: 15px 20px;
                position: relative;
            }

            .header-nav {
                margin: 15px 0;
            }

            .user-profile {
                margin-top: 10px;
            }

            .content-area {
                margin-top: 0;
                padding: 20px;
            }
        }

        @media (max-width: 768px) {
            .dashboard {
                padding: 0;
            }

            .dashboard-header h1 {
                font-size: 28px;
            }

            .stat-card {
                padding: 25px;
            }

            .activity-section {
                padding: 25px;
            }
        }

        /* Mobile menu button */
        .mobile-menu-btn {
            display: none;
        }

        @media (max-width: 1200px) {
            .mobile-menu-btn {
                display: block;
                background: none;
                border: none;
                color: white;
                font-size: 24px;
                cursor: pointer;
                margin-right: 15px;
            }
        }

        /* Sidebar overlay for mobile */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 998;
        }

        .sidebar-overlay.active {
            display: block;
        }
    </style>

</head>

<body>

    <!-- Header - Same as register.php -->
    <header>
        <div class="left">
            <img src="ambo.png" alt="Ambo University Logo">
            <div>
                <h1 style="margin: 0; font-size: 24px;">Ambo University Library</h1>
                <p style="margin: 5px 0 0 0; font-size: 14px; opacity: 0.9;">Admin Dashboard</p>
            </div>
        </div>

        <button class="mobile-menu-btn" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>

        <div class="user-profile" onclick="toggleDropdown()">
            <div class="user-info">
                <span class="user-name"><?= $userName; ?></span>
                <span
                    class="user-role"><?= ($userRole === 'super_admin') ? 'SUPER ADMIN' : strtoupper($userRole); ?></span>
            </div>

            <div class="avatar-circle">
                <?php
                if ($profilePicUrl !== $default_image && file_exists($profilePicUrl)) {
                    echo '<img src="' . $profilePicUrl . '" alt="Profile Image">';
                } else {
                    echo strtoupper(substr($userName, 0, 1));
                }
                ?>
            </div>
        </div>

        <div class="dropdown-menu" id="userDropdown">
            <a href="profile.php"><i class="fas fa-user"></i> My Profile</a>
            <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </header>

    <!-- Sidebar overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

    <!-- Main Container -->
    <div class="main-container">
        <!-- Sidebar Navigation -->
        <nav class="sidebar" id="sidebar">
            <div class="sidebar-nav">
                <div class="sidebar-section">Dashboard</div>
                <?= renderSidebarItem('admin.php', 'dashboard', 'Overview', true); ?>

                <div class="sidebar-section">Content Management</div>
                <?= renderSidebarItem('add_book.php', 'book-medical', 'Add New Book'); ?>
                <?= renderSidebarItem('view_books.php', 'books', 'View Books'); ?>
                <?= renderSidebarItem('manage_users.php', 'users', 'Manage Users'); ?>
                <?= renderSidebarItem('report.php', 'chart-line', 'Reports'); ?>
                <?= renderSidebarItem('borrows_view.php', 'book-reader', 'View Borrows'); ?>
                <?= renderSidebarItem('send_message.php', 'envelope', 'Messages'); ?>

                <?php if ($userRole === 'super_admin'): ?>
                    <div class="sidebar-section">Admin Controls</div>
                    <?= renderSidebarItem('manage_admins.php', 'shield-alt', 'Manage Admins'); ?>
                    <?= renderSidebarItem('system_settings.php', 'cogs', 'System Settings'); ?>
                <?php endif; ?>
            </div>

            <div class="sidebar-logout">
                <a href="logout.php" class="sidebar-item">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span>Logout</span>
                </a>
            </div>
        </nav>

        <!-- Main Content Area -->
        <div class="content-area">
            <div class="dashboard">
                <div class="dashboard-header">
                    <h1>Dashboard Overview</h1>
                    <p>Welcome back, <?= $userName; ?>. Here's what's happening with your library today.</p>
                    <div class="welcome-badge">
                        <i class="fa-solid fa-calendar-check"></i>
                        Last login: Today at <?= date('H:i'); ?>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fa-solid fa-book"></i>
                        </div>
                        <span class="stat-number"><?= $totalBooks; ?></span>
                        <span class="stat-label">Total Books in Stock</span>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fa-solid fa-users"></i>
                        </div>
                        <span class="stat-number"><?= $registeredUsers; ?></span>
                        <span class="stat-label">Registered Users</span>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fa-solid fa-exchange-alt"></i>
                        </div>
                        <span class="stat-number"><?= $borrowedBooks; ?></span>
                        <span class="stat-label">Books Currently Borrowed</span>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fa-solid fa-exclamation-triangle"></i>
                        </div>
                        <span class="stat-number"><?= $overdueBooks; ?></span>
                        <span class="stat-label">Overdue Books</span>
                    </div>

                    <div class="special-book-card">
                        <div class="stat-icon">
                            <i class="fa-solid fa-star"></i>
                        </div>
                        <span class="stat-number"><?= $internetProgramStock; ?></span>
                        <span class="stat-label">Available Copies of "Internet Programming"</span>
                    </div>
                </div>

                <!-- VIDEO MANAGEMENT SECTION -->
                <div class="activity-section">
                    <div class="activity-header">
                        <h2><i class="fa-solid fa-video"></i> Library Video Center</h2>
                    </div>

                    <!-- ADMIN UPLOAD FORM -->
                    <?php if (in_array($userRole, ['admin', 'super_admin'])): ?>
                        <form action="upload_admin_video.php" method="POST" enctype="multipart/form-data"
                            style="margin-bottom:30px;">
                            <label style="font-weight:600; display:block; margin-bottom:10px;">
                                Upload New Library Video
                            </label>
                            <input type="file" name="video" accept="video/mp4,video/webm" required
                                style="margin-bottom:15px;">
                            <br>
                            <button type="submit" style="
                            padding:12px 28px;
                            background: var(--primary-solid);
                            color:#fff;
                            border:none;
                            border-radius:10px;
                            font-weight:600;
                            cursor:pointer;
                            box-shadow: var(--shadow-soft);
                        ">
                                <i class="fa-solid fa-upload"></i> Upload Video
                            </button>
                        </form>
                    <?php endif; ?>

                    <!-- VIDEO LIST -->
                    <?php
                    $videoDir = "videos/admin_uploads/";
                    $videos = glob($videoDir . "*.{mp4,webm}", GLOB_BRACE);

                    if ($videos):
                        foreach ($videos as $video):
                            ?>
                            <div style="margin-bottom:25px;">
                                <video width="100%" controls style="border-radius:14px; box-shadow: var(--shadow-soft);">
                                    <source src="<?= $video; ?>" type="video/mp4">
                                </video>
                            </div>
                            <?php
                        endforeach;
                    else:
                        echo "<p style='color:var(--text-light);'>No videos uploaded yet.</p>";
                    endif;
                    ?>
                </div>

                <!-- Recent Activity -->
                <div class="activity-section">
                    <div class="activity-header">
                        <h2>Recent System Activity</h2>
                        <a href="activity_log.php" class="view-all">
                            View All <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>

                    <div class="activity-item">
                        <i class="fa-solid fa-user-check"></i>
                        <div class="activity-content">
                            <p>You logged in to the system</p>
                            <span class="activity-time">Today at <?= date('H:i'); ?></span>
                        </div>
                    </div>

                    <div class="activity-item">
                        <i class="fa-solid fa-chart-line"></i>
                        <div class="activity-content">
                            <p>System performance is optimal</p>
                            <span class="activity-time">Last checked 2 hours ago</span>
                        </div>
                    </div>

                    <div class="activity-item">
                        <i class="fa-solid fa-shield-alt"></i>
                        <div class="activity-content">
                            <p>Security check completed successfully</p>
                            <span class="activity-time">Yesterday at 14:30</span>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats Row -->
                <div
                    style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 40px;">
                    <div
                        style="background: white; padding: 20px; border-radius: var(--radius-md); text-align: center; box-shadow: var(--shadow-soft);">
                        <i class="fa-solid fa-server"
                            style="font-size: 24px; color: var(--primary-solid); margin-bottom: 10px;"></i>
                        <p style="font-weight: 600; margin: 0;">System Status</p>
                        <span style="color: var(--success); font-weight: 600;">Online</span>
                    </div>

                    <div
                        style="background: white; padding: 20px; border-radius: var(--radius-md); text-align: center; box-shadow: var(--shadow-soft);">
                        <i class="fa-solid fa-database"
                            style="font-size: 24px; color: var(--info); margin-bottom: 10px;"></i>
                        <p style="font-weight: 600; margin: 0;">Database Size</p>
                        <span style="color: var(--text-dark); font-weight: 600;">2.4 GB</span>
                    </div>

                    <div
                        style="background: white; padding: 20px; border-radius: var(--radius-md); text-align: center; box-shadow: var(--shadow-soft);">
                        <i class="fa-solid fa-bell"
                            style="font-size: 24px; color: var(--warning); margin-bottom: 10px;"></i>
                        <p style="font-weight: 600; margin: 0;">Pending Tasks</p>
                        <span style="color: var(--text-dark); font-weight: 600;">3</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleDropdown() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.classList.toggle('show');
        }

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
            document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : 'auto';
        }

        function closeSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function (event) {
            const userProfile = document.querySelector('.user-profile');
            const dropdown = document.getElementById('userDropdown');

            if (!userProfile.contains(event.target) && dropdown && dropdown.classList.contains('show')) {
                dropdown.classList.remove('show');
            }
        });

        // Close sidebar on window resize if mobile
        window.addEventListener('resize', function () {
            if (window.innerWidth > 1200) {
                closeSidebar();
            }
        });

        // Animate stat cards on load
        document.addEventListener('DOMContentLoaded', function () {
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';

                setTimeout(() => {
                    card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });

        // Close sidebar when clicking on overlay
        document.getElementById('sidebarOverlay').addEventListener('click', closeSidebar);
    </script>

</body>

</html>