<?php
/**
 * Manage Administrators Page (manage_admins.php)
 * RESTRICTED: Allows ONLY 'super_admin' role to manage 'admin' accounts.
 * Provides functionality to add, update name/email, reset password, and delete admin users.
 */
session_start();
include "db.php"; 

// --- CRITICAL SECURITY CHECK ---
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'super_admin') {
    header("Location: " . (isset($_SESSION['user_role']) ? 'admin.php' : 'login.php'));
    exit;
}

$userName = htmlspecialchars($_SESSION['user_name']);
$userRole = htmlspecialchars($_SESSION['user_role']);
$current_user_id = $_SESSION['user_id'];
$message = '';
$message_type = ''; 

function redirect_with_message($msg, $type) {
    header("Location: manage_admins.php?msg=" . urlencode($msg) . "&type=" . urlencode($type));
    exit;
}

if (isset($_GET['msg']) && isset($_GET['type'])) {
    $message = htmlspecialchars(urldecode($_GET['msg']));
    $message_type = htmlspecialchars(urldecode($_GET['type']));
}

// ✅ Handle Delete Admin
if (isset($_GET['delete'])) {
    $id_to_delete = intval($_GET['delete']);

    if ($id_to_delete == $current_user_id) {
        redirect_with_message("Error: You cannot delete your own Super Admin account.", 'error');
    }

    $check_stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $check_stmt->bind_param("i", $id_to_delete);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $target_user = $result->fetch_assoc();
    $check_stmt->close();

    if (!$target_user || $target_user['role'] !== 'admin') {
         redirect_with_message("Error: User not found or is a protected Super Admin.", 'error');
    }

    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'admin'");
    $stmt->bind_param("i", $id_to_delete);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        redirect_with_message("Admin user ID #$id_to_delete deleted successfully.", 'success');
    } else {
        redirect_with_message("Error deleting admin user: Database failure.", 'error');
    }
}

// ✅ Handle Add Admin
if (isset($_POST['add_admin'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password']; 
    $role = 'admin';

    if (empty($name) || empty($email) || empty($password)) {
        redirect_with_message("Error: All fields are required.", 'error');
    }
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $hashed_password, $role);

    if ($stmt->execute()) {
        redirect_with_message("Admin user '{$name}' created successfully.", 'success');
    } else {
        if ($conn->errno == 1062) {
             redirect_with_message("Error: Email '{$email}' is already registered.", 'error');
        } else {
            redirect_with_message("Error: " . $stmt->error, 'error');
        }
    }
}

// ✅ Handle Update Account
if (isset($_POST['update_admin'])) {
    $id = intval($_POST['id']);
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);

    if ($id == $current_user_id) {
         redirect_with_message("Error: Update your own profile via the Settings page.", 'error');
    }

    $stmt = $conn->prepare("UPDATE users SET name=?, email=? WHERE id=? AND role = 'admin'");
    $stmt->bind_param("ssi", $name, $email, $id);

    if ($stmt->execute()) {
        redirect_with_message("Admin account #$id updated.", 'success');
    }
}

// ✅ Handle Password Reset
if (isset($_POST['reset_password'])) {
    $id = intval($_POST['id']);
    $new_password = $_POST['new_password'];
    
    if (empty($new_password)) {
        redirect_with_message("Error: Password cannot be empty.", 'error');
    }

    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=? AND role = 'admin'");
    $stmt->bind_param("si", $hashed_password, $id);

    if ($stmt->execute()) {
        redirect_with_message("Password for Admin #$id reset successfully.", 'success');
    }
}

$admins = $conn->query("SELECT id, name, email, role FROM users WHERE role IN ('admin', 'super_admin') ORDER BY role DESC, name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Super Admin | Manage Staff</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --ambo-blue: #0984e3;
            --ambo-dark: #2d3436;
            --bg-light: #f4f7f6;
            --danger: #d63031;
            --success: #27ae60;
            --white: #ffffff;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg-light);
            margin: 0;
            color: var(--ambo-dark);
        }

        header { 
            background: var(--ambo-blue); 
            color: white; 
            padding: 20px 40px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }

        .card {
            background: var(--white);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .card-title { color: var(--ambo-blue); font-weight: 600; margin-bottom: 20px; border-bottom: 2px solid #eee; padding-bottom: 10px; }

        .grid-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }

        input {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            width: 100%;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: 0.3s;
        }

        .btn-primary { background: var(--ambo-blue); color: white; }
        .btn-primary:hover { background: #0773c5; }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
        }

        th { background: #eef2f7; color: var(--ambo-blue); padding: 15px; text-align: left; font-size: 14px; }
        td { padding: 15px; border-bottom: 1px solid #f1f1f1; }

        .role-badge {
            padding: 4px 10px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-super { background: #dfe6e9; color: var(--ambo-blue); border: 1px solid var(--ambo-blue); }
        .badge-admin { background: var(--ambo-blue); color: white; }

        .message { padding: 15px; border-radius: 6px; margin-bottom: 20px; font-weight: 500; }
        .success { background: #dff9fb; color: var(--success); border-left: 5px solid var(--success); }
        .error { background: #fab1a0; color: var(--danger); border-left: 5px solid var(--danger); }

        .delete-btn { color: var(--danger); text-decoration: none; font-weight: 600; }
        .delete-btn:hover { text-decoration: underline; }
    </style>
</head>
<body>

<header>
    <div>
        <h1 style="margin:0; font-size: 22px;"><i class="fas fa-user-shield me-2"></i>Ambo Library Portal</h1>
        <small>Super Admin Authorization Level</small>
    </div>
    <div class="text-end">
        <span>Welcome, <strong><?= $userName ?></strong></span>
    </div>
</header>

<div class="container">

    <?php if ($message): ?>
        <div class="message <?= $message_type ?>">
            <i class="fas <?= $message_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle' ?> me-2"></i>
            <?= $message ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h3 class="card-title"><i class="fas fa-plus-circle me-2"></i>Register New Admin</h3>
        <form method="POST" class="grid-form">
            <div>
                <label class="small fw-bold">Full Name</label>
                <input type="text" name="name" placeholder="Enter name" required>
            </div>
            <div>
                <label class="small fw-bold">Email Address</label>
                <input type="email" name="email" placeholder="email@ambou.edu.et" required>
            </div>
            <div>
                <label class="small fw-bold">Default Password</label>
                <input type="password" name="password" placeholder="Min 8 characters" required>
            </div>
            <button type="submit" name="add_admin" class="btn btn-primary">Create Account</button>
        </form>
    </div>

    <div class="card">
        <h3 class="card-title"><i class="fas fa-users-cog me-2"></i>Staff Directory</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User Information</th>
                    <th>System Role</th>
                    <th>Management Actions</th>
                    <th>Danger Zone</th>
                </tr>
            </thead>
            <tbody>
            <?php while($u = $admins->fetch_assoc()): 
                $is_self = ($u['id'] == $current_user_id);
                $is_super = ($u['role'] === 'super_admin');
            ?>
                <tr>
                    <td>#<?= $u['id'] ?></td>
                    <td>
                        <strong><?= htmlspecialchars($u['name']) ?></strong><br>
                        <small class="text-muted"><?= htmlspecialchars($u['email']) ?></small>
                    </td>
                    <td>
                        <span class="role-badge <?= $is_super ? 'badge-super' : 'badge-admin' ?>">
                            <?= $u['role'] ?>
                        </span>
                    </td>
                    <td>
                        <?php if (!$is_super): ?>
                            <form method="POST" class="d-flex gap-2">
                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                <input type="password" name="new_password" placeholder="New Password" style="width: 130px; font-size: 12px;">
                                <button type="submit" name="reset_password" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px;">Reset</button>
                            </form>
                        <?php else: ?>
                            <span class="text-muted small">Protected Account</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!$is_super && !$is_self): ?>
                            <a href="?delete=<?= $u['id'] ?>" class="delete-btn" onclick="return confirm('Permanently remove this admin?')">
                                <i class="fas fa-trash-alt"></i> Delete
                            </a>
                        <?php else: ?>
                            <i class="fas fa-lock text-muted"></i>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div class="text-center">
        <a href="admin.php" class="btn" style="background: #636e72; color: white; text-decoration: none;">
            <i class="fas fa-arrow-left me-2"></i>Return to Dashboard
        </a>
    </div>
</div>

</body>
</html>