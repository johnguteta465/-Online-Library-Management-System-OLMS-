<?php
session_start();
include "db.php"; // Ensure this file has your $conn connection

$error = "";
$success = "";
$step = 1; // 1: Email Search, 2: Reset Password Form

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    // STEP 1: FIND ACCOUNT
    if (isset($_POST['find_account'])) {
        $email = trim($_POST['email'] ?? "");
        
        if ($email !== "") {
            $stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ? LIMIT 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows === 1) {
                $user = $result->fetch_assoc();
                $_SESSION['reset_user_id'] = $user['id'];
                $_SESSION['reset_user_name'] = $user['name'];
                $step = 2; // Move to password entry
            } else {
                $error = "No account found with that email address.";
            }
            $stmt->close();
        } else {
            $error = "Please enter your email.";
        }
    } 
    
    // STEP 2: UPDATE PASSWORD
    elseif (isset($_POST['update_password'])) {
        $new_password = $_POST['new_password'] ?? "";
        $confirm_password = $_POST['confirm_password'] ?? "";
        $user_id = $_SESSION['reset_user_id'] ?? null;

        if (!$user_id) {
            header("Location: forgot_password.php");
            exit;
        }

        if (strlen($new_password) < 8) {
            $error = "Security Error: Password must be at least 8 characters long.";
            $step = 2;
        } elseif ($new_password !== $confirm_password) {
            $error = "Passwords do not match.";
            $step = 2;
        } else {
            // Hash the new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($update_stmt->execute()) {
                $success = "Password updated successfully! You can now login.";
                unset($_SESSION['reset_user_id']);
                unset($_SESSION['reset_user_name']);
                $step = 3; // Finished
            } else {
                $error = "Database error. Please try again later.";
                $step = 2;
            }
            $update_stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | Ambo University Library</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
        }
        .reset-card {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 450px;
        }
        .ambo-logo { width: 150px; margin-bottom: 20px; }
        .btn-primary { background: #0984e3; border: none; padding: 12px; }
        .btn-primary:hover { background: #065fa7; }
        .form-label { font-weight: 600; color: #444; }
    </style>
</head>
<body>

<div class="reset-card text-center">
    <img src="ambo.png" alt="Ambo University" class="ambo-logo">
    <h3 class="mb-4">Reset Your Password</h3>

    <?php if ($error): ?>
        <div class="alert alert-danger text-start"><?= $error ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
        <a href="login.php" class="btn btn-primary w-100 mt-3">Go to Login</a>
    <?php endif; ?>

    <?php if ($step == 1): ?>
        <p class="text-muted">Enter your email address and we'll help you reset your password.</p>
        <form method="POST" class="text-start">
            <div class="mb-3">
                <label class="form-label">Registered Email</label>
                <input type="email" name="email" class="form-control" placeholder="example@ambou.edu.et" required>
            </div>
            <button type="submit" name="find_account" class="btn btn-primary w-100">Find My Account</button>
        </form>
    <?php endif; ?>

    <?php if ($step == 2): ?>
        <p class="text-muted">Hello, <strong><?= htmlspecialchars($_SESSION['reset_user_name']) ?></strong>. Please set a new secure password.</p>
        <form method="POST" class="text-start">
            <div class="mb-3">
                <label class="form-label">New Password</label>
                <input type="password" name="new_password" class="form-control" placeholder="At least 8 characters" required minlength="8">
                <small class="text-muted">Minimum 8 characters required.</small>
            </div>
            <div class="mb-3">
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control" placeholder="Repeat password" required>
            </div>
            <button type="submit" name="update_password" class="btn btn-primary w-100">Update Password</button>
        </form>
    <?php endif; ?>

    <div class="mt-4">
        <a href="login.php" class="text-decoration-none" style="color: #0984e3;">Back to Login</a>
    </div>
</div>

</body>
</html>