<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'super_admin')) {
    header("Location: login.php");
    exit;
}

$user_name = $_SESSION['user_name'] ?? 'Administrator';
$message = '';
$message_type = ''; 

// 1. Initialize Variables with defaults
$stats = ['total_books' => 0, 'total_users' => 0, 'active_borrows' => 0];
$settings = [
    'site_name' => 'Ambo University Library',
    'site_email' => 'library@ambou.edu.et',
    'max_borrow_days' => 14,
    'max_books_per_user' => 1,
    'allow_registration' => 1,
    'max_login_attempts' => 5,
    'library_status' => 1 // 1 = Open, 0 = Closed/Maintenance
];

// 2. Fetch Data
try {
    $resBooks = $conn->query("SELECT COUNT(*) as c FROM books");
    if ($resBooks) $stats['total_books'] = $resBooks->fetch_assoc()['c'];

    $resUsers = $conn->query("SELECT COUNT(*) as c FROM users WHERE role = 'user'");
    if ($resUsers) $stats['total_users'] = $resUsers->fetch_assoc()['c'];

    $resBorrows = $conn->query("SELECT COUNT(*) as c FROM borrows WHERE status = 'borrowed'");
    if ($resBorrows) $stats['active_borrows'] = $resBorrows->fetch_assoc()['c'];

    $resSettings = $conn->query("SELECT * FROM system_settings LIMIT 1");
    if ($resSettings && $resSettings->num_rows > 0) {
        $settings = $resSettings->fetch_assoc();
    }
} catch (Exception $e) {
    $message = "Database Error: " . $e->getMessage();
    $message_type = 'error';
}

// 3. Handle Form Update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Fixed: Added null-coalescing (?? '') to prevent Undefined Key errors
    $site_name = $conn->real_escape_string($_POST['site_name'] ?? $settings['site_name']);
    $site_email = $conn->real_escape_string($_POST['site_email'] ?? $settings['site_email']);
    $max_days = intval($_POST['max_borrow_days']);
    $max_books = intval($_POST['max_books_per_user']);
    $reg_control = isset($_POST['allow_registration']) ? 1 : 0;
    $lib_status = isset($_POST['library_status']) ? 1 : 0; // New Setting
    $login_attempts = intval($_POST['max_login_attempts']);

    $update = $conn->query("UPDATE system_settings SET 
        site_name='$site_name', 
        site_email='$site_email', 
        max_borrow_days=$max_days, 
        max_books_per_user=$max_books,
        allow_registration=$reg_control,
        library_status=$lib_status,
        max_login_attempts=$login_attempts 
        WHERE id=1");

    if ($update) {
        $message = "System settings updated successfully!";
        $message_type = "success";
        // Update local array to show changes immediately
        $settings = array_merge($settings, $_POST);
        $settings['allow_registration'] = $reg_control;
        $settings['library_status'] = $lib_status;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Control | Ambo University</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --w3-teal: #96d4d4;
            --w3-dark: #282a35;
            --w3-green: #04aa6d;
            --w3-purple: #b157a8;
        }
        body { background-color: #ffffff; font-family: 'Segoe UI', sans-serif; }
        .w3-navbar { background: white; padding: 15px 30px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; }
        .system-header { background-color: var(--w3-teal); padding: 50px 20px; text-align: center; }
        .system-header h1 { font-size: 2.8rem; font-weight: 900; }
        
        .stats-container { max-width: 1000px; margin: -30px auto 30px; display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; }
        .stat-box { padding: 20px; border-radius: 4px; text-align: center; box-shadow: 0 4px 8px rgba(0,0,0,0.05); }
        .color-1 { background-color: #fff4a3; } 
        .color-2 { background-color: #ffc0c7; }
        .color-3 { background-color: var(--w3-purple); color: white; }
        .color-4 { background-color: #e2e0e7; }

        .settings-card { max-width: 900px; margin: 0 auto 50px; padding: 40px; background: #fbfbfb; border: 1px solid #eee; border-radius: 8px; }
        .section-h { border-left: 5px solid var(--w3-teal); padding-left: 15px; margin: 30px 0 20px; font-weight: 800; }
        .btn-save { background-color: var(--w3-green); color: white; padding: 12px; border: none; border-radius: 25px; font-weight: 700; width: 100%; }
    </style>
</head>
<body>

<nav class="w3-navbar">
    <div><strong>AMBO LIBRARY</strong></div>
    <div><strong><?php echo strtoupper($user_name); ?></strong></div>
</nav>

<header class="system-header">
    <h1>SYSTEM SETTINGS</h1>
</header>

<div class="stats-container">
    <div class="stat-box color-1"><h2><?php echo $stats['total_books']; ?></h2><p>Books</p></div>
    <div class="stat-box color-2"><h2><?php echo $stats['total_users']; ?></h2><p>Users</p></div>
    <div class="stat-box color-3"><h2><?php echo $stats['active_borrows']; ?></h2><p>Active</p></div>
    <div class="stat-box color-4"><h2><?php echo ($settings['library_status'] ? 'OPEN' : 'CLOSED'); ?></h2><p>Status</p></div>
</div>

<div class="container">
    <?php if($message): ?>
        <div class="alert alert-success text-center"><?php echo $message; ?></div>
    <?php endif; ?>

    <div class="settings-card">
        <form method="POST">
            <h4 class="section-h">General Information</h4>
            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Library Site Name</label>
                    <input type="text" name="site_name" class="form-control" value="<?php echo htmlspecialchars($settings['site_name']); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Admin Contact Email</label>
                    <input type="email" name="site_email" class="form-control" value="<?php echo htmlspecialchars($settings['site_email']); ?>">
                </div>
            </div>

            <h4 class="section-h">Security & Access</h4>
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="allow_registration" id="reg" <?php echo $settings['allow_registration'] ? 'checked' : ''; ?>>
                        <label class="form-check-label fw-bold" for="reg">Allow Registration</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="library_status" id="libStatus" <?php echo $settings['library_status'] ? 'checked' : ''; ?>>
                        <label class="form-check-label fw-bold text-primary" for="libStatus">Library Online (Open)</label>
                    </div>
                    <small class="text-muted">Uncheck to block Student access.</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Max Login Attempts</label>
                    <input type="number" name="max_login_attempts" class="form-control" value="<?php echo $settings['max_login_attempts']; ?>">
                </div>
            </div>

            <h4 class="section-h">Borrowing Rules</h4>
            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Books per Student</label>
                    <input type="number" name="max_books_per_user" class="form-control" value="1" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Max Duration (Days)</label>
                    <input type="number" name="max_borrow_days" class="form-control" value="<?php echo $settings['max_borrow_days']; ?>">
                </div>
            </div>

            <button type="submit" class="btn-save">UPDATE SYSTEM CONTROLS</button>
             <a href="admin.php" class="back-link">⬅ Back to Admin Dashboard</a>
            
        </form>
    </div>
</div>

</body>
</html>