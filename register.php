<?php
/**
 * register.php
 * Ambo University Library Management System - Registration Page
 * Logic: Handles user registration with system setting checks and password length limitation.
 */
session_start();
include "db.php";

// 1. Redirect if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

// 2. System Settings Verification
$registration_allowed = true;
$maintenance_mode = false;

if (isset($conn) && !$conn->connect_error) {
    $settings_query = $conn->query("SELECT maintenance_mode, allow_registration FROM system_settings WHERE id = 1");
    if ($settings_query && $settings_query->num_rows > 0) {
        $settings = $settings_query->fetch_assoc();
        $maintenance_mode = (bool)$settings['maintenance_mode'];
        $registration_allowed = (bool)$settings['allow_registration'];
    }
    $settings_query->close();
    
    // Redirect if the system is under maintenance
    if ($maintenance_mode) {
        echo "<script>alert('System is under maintenance. Registration is temporarily disabled.'); window.location='index.php';</script>";
        exit;
    }
}

// 3. Form Submission Handling
if ($_SERVER['REQUEST_METHOD'] == "POST" && $registration_allowed) {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? 'user';

    // Basic required field check
    if ($name && $email && $password) {
        
        // --- NEW LOGIC: Password length limitation ---
        if (strlen($password) < 8) {
            echo "<script>alert('Error: Password must be at least 8 characters long.');</script>";
        } 
        elseif (!isset($conn) || $conn->connect_error) {
            echo "<script>alert('Database connection failed.');</script>";
        } else {
            // Double-check if registration is still enabled in DB
            $settings_check = $conn->query("SELECT allow_registration FROM system_settings WHERE id = 1");
            if ($settings_check && $settings_check->num_rows > 0) {
                $current_settings = $settings_check->fetch_assoc();
                
                if (!$current_settings['allow_registration']) {
                    echo "<script>alert('Registration has been disabled. Please contact administrator.');</script>";
                } else {
                    // Check if email is already taken
                    $check = $conn->prepare("SELECT email FROM users WHERE email = ?");
                    $check->bind_param("s", $email);
                    $check->execute();
                    $check->store_result();

                    if ($check->num_rows > 0) {
                        echo "<script>alert('Email already registered! Please login.'); window.location='login.php';</script>";
                    } else {
                        // Insert new user record
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
                        $stmt->bind_param("ssss", $name, $email, $hashedPassword, $role);

                        if ($stmt->execute()) {
                            // Automatic login after successful registration
                            $_SESSION['user_id']    = $conn->insert_id; 
                            $_SESSION['user_name']  = $name;
                            $_SESSION['user_email'] = $email;
                            $_SESSION['user_role']  = $role;
                            
                            header("Location: dashboard.php?msg=registered");
                            exit;
                        } else {
                            echo "<script>alert('Database error: " . $stmt->error . "');</script>";
                        }
                        $stmt->close();
                    }
                    $check->close();
                }
            }
        }
    } else {
        echo "<script>alert('Please fill in all required fields.');</script>";
    }
} elseif ($_SERVER['REQUEST_METHOD'] == "POST" && !$registration_allowed) {
    echo "<script>alert('Registration is currently disabled by administrator.');</script>";
}

// Close DB connection
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | Ambo University Library</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Shared Styles and Theme */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        header {
            background-color: #0984e3;
            color: white;
            padding: 10px 30px;
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            height: 100px;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 100%;
            max-width: 1200px;
            margin: 0 auto;
        }

        .logo-container { display: flex; align-items: center; gap: 15px; }
        .logo-img { width: 200px; height: 80px; object-fit: contain; }
        .logo-text h1 { font-size: 1.5rem; font-weight: 700; margin: 0; }
        .logo-text p { font-size: 0.9rem; margin: 5px 0 0 0; opacity: 0.9; }

        nav a {
            color: white;
            text-decoration: none;
            margin-left: 25px;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 6px;
            transition: 0.3s;
        }

        nav a:hover { background: rgba(255, 255, 255, 0.15); }

        main { flex: 1; padding: 120px 20px 60px; max-width: 1200px; margin: 0 auto; width: 100%; }

        .page-title { text-align: center; margin-bottom: 40px; }
        .page-title h1 { color: #0984e3; font-size: 2rem; font-weight: 700; margin-bottom: 10px; }
        .page-title h2 { color: #333; font-size: 1.4rem; font-weight: 500; }

        .registration-status {
            max-width: 800px;
            margin: 0 auto 30px;
            padding: 15px 20px;
            border-radius: 10px;
            text-align: center;
            font-weight: 500;
        }

        .status-open { background: #d4edda; border: 2px solid #c3e6cb; color: #155724; }
        .status-closed { background: #f8d7da; border: 2px solid #f5c6cb; color: #721c24; }

        .form-section { display: flex; gap: 50px; margin-bottom: 60px; align-items: center; }
        .form-container { flex: 1; min-width: 400px; }
        .image-container { flex: 1; display: flex; justify-content: center; }
        .campus-image { width: 100%; max-width: 500px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); }

        .form-card { background: white; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); overflow: hidden; }
        .form-header { background: linear-gradient(135deg, #0984e3 0%, #065fa7 100%); color: white; padding: 20px; text-align: center; }
        .form-body { padding: 30px; }
        .form-group { margin-bottom: 20px; }

        .input-with-icon { position: relative; }
        .input-icon { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #0984e3; }
        .form-input { width: 100%; padding: 15px 15px 15px 45px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 1rem; transition: 0.3s; }
        .form-input:focus { outline: none; border-color: #0984e3; box-shadow: 0 0 0 3px rgba(9, 132, 227, 0.1); }
        .form-input:disabled { background: #f8f9fa; cursor: not-allowed; }

        .submit-btn {
            width: 100%; padding: 15px;
            background: linear-gradient(135deg, #0984e3 0%, #065fa7 100%);
            color: white; border: none; border-radius: 8px;
            font-size: 1.1rem; font-weight: 600; cursor: pointer; transition: 0.3s;
        }

        .submit-btn:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(9, 132, 227, 0.3); }
        .submit-btn:disabled { background: #6c757d; cursor: not-allowed; }

        .password-toggle { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #666; cursor: pointer; }

        /* Gallery and Feature styles */
        .image-gallery { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px; }
        .gallery-item { border-radius: 12px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.1); transition: 0.3s; }
        .gallery-item:hover { transform: translateY(-10px); }
        .gallery-img { width: 100%; height: 200px; object-fit: cover; }

        .feature-section { background: white; border-radius: 15px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); text-align: center; }
        .feature-title { color: #0984e3; font-size: 2rem; margin-bottom: 20px; font-weight: 700; }
        .feature-image { max-width: 500px; width: 100%; border-radius: 12px; margin-top: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }

        footer { background-color: #003366; color: white; padding: 40px 0 20px; margin-top: auto; }
        .footer-content { max-width: 1200px; margin: 0 auto; padding: 0 20px; display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 40px; }
        .footer-section h3 { font-size: 1.3rem; margin-bottom: 20px; color: #0984e3; border-bottom: 2px solid #0984e3; padding-bottom: 10px; display: inline-block; }
        .footer-links { list-style: none; padding: 0; }
        .footer-links a { color: #ccc; text-decoration: none; transition: 0.3s; }
        .footer-links a:hover { color: #0984e3; }

        @media (max-width: 992px) { .form-section { flex-direction: column; } .image-container { order: -1; } }
    </style>
</head>
<body>

<header>
    <div class="header-content">
        <div class="logo-container">
            <img src="ambo.png" alt="Ambo University Logo" class="logo-img">
            <div class="logo-text">
                <h1>Ambo University</h1>
                <p>Library Management System</p>
            </div>
        </div>
        <nav>
            <a href="index.php">Home</a>
            <a href="login.php">Login</a>
        </nav>
    </div>
</header>

<main>
    <div class="page-title">
        <h1>Ambo University Online Library System</h1>
        <h2>Create New Account</h2>
    </div>

    <?php if ($registration_allowed): ?>
        <div class="registration-status status-open">
            <i class="fas fa-user-check"></i> Registration is open. Fill the form below to create your account.
        </div>
    <?php else: ?>
        <div class="registration-status status-closed">
            <i class="fas fa-user-slash"></i> New User Registration is currently disabled by administrator.
        </div>
        <div class="alert alert-warning text-center mx-auto" style="max-width: 800px;">
            <strong><i class="fas fa-exclamation-triangle"></i> Registration Temporarily Closed:</strong> The registration interface remains accessible for reference only.
        </div>
    <?php endif; ?>

    <div class="form-section">
        <div class="form-container">
            <div class="form-card">
                <div class="form-header">
                    <h3><i class="fas fa-user-plus me-2"></i>Register New User</h3>
                </div>
                <div class="form-body">
                    <form action="register.php" method="post" id="registrationForm">
                        <div class="form-group">
                            <div class="input-with-icon">
                                <i class="fas fa-user input-icon"></i>
                                <input type="text" name="name" class="form-input" placeholder="Full Name" required 
                                       <?php if (!$registration_allowed) echo 'disabled'; ?>>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <div class="input-with-icon">
                                <i class="fas fa-envelope input-icon"></i>
                                <input type="email" name="email" class="form-input" placeholder="Email Address" required
                                       <?php if (!$registration_allowed) echo 'disabled'; ?>>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <div class="input-with-icon">
                                <i class="fas fa-lock input-icon"></i>
                                <input type="password" name="password" id="password" class="form-input" 
                                       placeholder="Create Password (min. 8 characters)" required minlength="8"
                                       <?php if (!$registration_allowed) echo 'disabled'; ?>>
                                <button type="button" class="password-toggle" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <small class="text-muted"><i class="fas fa-info-circle me-1"></i> Use 8 or more characters.</small>
                        </div>
                        
                        <input type="hidden" name="role" value="user">

                        <button type="submit" class="submit-btn" id="submitBtn"
                                <?php if (!$registration_allowed) echo 'disabled'; ?>>
                                <i class="fas <?php echo $registration_allowed ? 'fa-user-plus' : 'fa-user-slash'; ?> me-2"></i>
                                <?php echo $registration_allowed ? 'Sign Up' : 'Registration Closed'; ?>
                        </button>

                        <div class="text-center mt-3">
                            Already have an account? <a href="login.php" class="text-primary fw-bold text-decoration-none">Login Here</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="image-container">
            <img src="ambos.jpg" alt="Ambo University Campus" class="campus-image">
        </div>
    </div>

    <div class="gallery-section mb-5">
        <h3 class="text-center mb-4 fw-bold">Discover Our Library Resources</h3>
        <div class="image-gallery">
            <div class="gallery-item"><img src="li.png" class="gallery-img"></div>
            <div class="gallery-item"><img src="hachalu.jpg" class="gallery-img"></div>
            <div class="gallery-item"><img src="la.png" class="gallery-img"></div>
        </div>
    </div>

    <div class="feature-section">
        <h2 class="feature-title">Automated Library System</h2>
        <p class="lead text-muted mx-auto" style="max-width: 800px;">
            Experience seamless book borrowing and management with our state-of-the-art automated library system. 
            Access digital resources 24/7.
        </p>
        <img src="la.png" alt="Automated System" class="feature-image">
    </div>
</main>

<footer>
    <div class="footer-content">
        <div class="footer-section">
            <h3>ABOUT US</h3>
            <p>Ambo University Library (AUL) supports the academic community with reliable information for research and innovation.</p>
        </div>
        <div class="footer-section">
            <h3>QUICK LINKS</h3>
            <ul class="footer-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="#">Services</a></li>
                <li><a href="#">Contact Us</a></li>
            </ul>
        </div>
        <div class="footer-section">
            <h3>CONTACT INFO</h3>
            <ul class="footer-links">
                <li><i class="fas fa-phone me-2"></i> (+25) 1112 36 81 60</li>
                <li><i class="fas fa-envelope me-2"></i> info@ambou.edu.et</li>
            </ul>
        </div>
    </div>
    <div class="text-center border-top border-secondary mt-4 pt-3">
        <p class="text-secondary">&copy; 2024 Ambo University Library Management System. All Rights Reserved.</p>
    </div>
</footer>

<script>
    // 1. Password Visibility Toggle
    document.getElementById('togglePassword').addEventListener('click', function() {
        const passInput = document.getElementById('password');
        const icon = this.querySelector('i');
        if (passInput.type === 'password') {
            passInput.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            passInput.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    });

    // 2. Form Submission & Client-Side Logic
    document.getElementById('registrationForm').addEventListener('submit', function(e) {
        const submitBtn = document.getElementById('submitBtn');
        const password = document.getElementById('password').value;

        // Final length check before submission
        if (password.length < 8) {
            e.preventDefault();
            alert('Security Requirement: Password must be at least 8 characters long.');
            return false;
        }

        <?php if ($registration_allowed): ?>
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating Account...';
            submitBtn.disabled = true;
        <?php else: ?>
            e.preventDefault();
            alert('Registration is currently disabled.');
        <?php endif; ?>
    });
</script>
</body>
</html>