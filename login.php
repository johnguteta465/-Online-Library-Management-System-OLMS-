<?php
session_start();
include "db.php";

// Check system settings
$maintenance_mode = false;
$allow_registration = true;

if (isset($conn) && !$conn->connect_error) {
    $settings_query = $conn->query("SELECT maintenance_mode, allow_registration FROM system_settings WHERE id = 1");
    if ($settings_query && $settings_query->num_rows > 0) {
        $settings = $settings_query->fetch_assoc();
        $maintenance_mode = (bool)$settings['maintenance_mode'];
        $allow_registration = (bool)$settings['allow_registration'];
    }
    $settings_query->close();
}

// Redirect logged-in users
if (isset($_SESSION['user_role'])) {
    $role = strtolower($_SESSION['user_role']);
    $redirect = in_array($role, ['admin', 'super_admin']) ? "admin.php" : "dashboard.php";
    header("Location: $redirect");
    exit;
}

$error = "";

// Handle Login
if ($_SERVER['REQUEST_METHOD'] === "POST") {
    $login_id = trim($_POST['login_id'] ?? "");
    $password = trim($_POST['password'] ?? "");

    if ($login_id !== "" && $password !== "") {
        if ($maintenance_mode) {
            $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE (email=? OR name=?) AND role IN ('admin', 'super_admin') LIMIT 1");
            if (!$stmt) {
                $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email=? AND role IN ('admin', 'super_admin') LIMIT 1");
                $stmt->bind_param("s", $login_id);
            } else {
                $stmt->bind_param("ss", $login_id, $login_id);
            }
        } else {
            $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email=? OR name=? LIMIT 1");
            if (!$stmt) {
                $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email=? LIMIT 1");
                $stmt->bind_param("s", $login_id);
            } else {
                $stmt->bind_param("ss", $login_id, $login_id);
            }
        }

        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result && $result->num_rows === 1) {
                $row = $result->fetch_assoc();
                $user_role = strtolower($row['role']); 
                $login_success = false;

                if (password_verify($password, $row['password'])) {
                    $login_success = true;
                } else if (in_array($user_role, ['admin', 'super_admin']) && $password === $row['password']) {
                    $login_success = true;
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    if ($update_stmt) {
                        $update_stmt->bind_param("si", $hashed_password, $row['id']);
                        $update_stmt->execute();
                        $update_stmt->close();
                    }
                } else if (substr($row['password'], 0, 1) === '$' && strlen($row['password']) > 50) {
                    $error = "Incorrect password.";
                } else {
                    $error = "Incorrect password.";
                }

                if ($login_success) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['user_role'] = $user_role;
                    $_SESSION['user_name'] = $row['name'];
                    $target = in_array($user_role, ['admin', 'super_admin']) ? "admin.php" : "dashboard.php";
                    header("Location: " . $target);
                    exit;
                }
            } else {
                $error = $maintenance_mode ? 
                    "System is under maintenance. Only administrators can login." : 
                    "User not found (Check Username/Email).";
            }
            $stmt->close();
        } else {
            $error = "Database query failed.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Ambo University Library</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* HEADER */
        header {
            background: linear-gradient(135deg, #0984e3 0%, #065fa7 100%);
            color: white;
            padding: 15px 30px;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }
        .logo-container {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .logo-img {
            width: 180px;
            height: 80px;
            object-fit: contain;
        }
        .logo-text h1 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 700;
        }
        .logo-text p {
            margin: 2px 0 0 0;
            font-size: 0.9rem;
            opacity: 0.9;
        }
        nav a {
            color: white;
            text-decoration: none;
            margin-left: 25px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            padding: 8px 16px;
            border-radius: 6px;
        }
        nav a:hover {
            background: rgba(255,255,255,0.15);
            transform: translateY(-2px);
        }

        /* MAIN CONTENT */
        main {
            flex: 1;
            padding: 120px 20px 60px;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }

        /* Page Title */
        .page-title {
            text-align: center;
            margin-bottom: 40px;
        }
        .page-title h1 {
            color: #0984e3;
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .page-title h2 {
            color: #333;
            font-size: 1.5rem;
            font-weight: 500;
            margin-bottom: 30px;
        }

        /* Status Messages */
        .status-alert {
            max-width: 800px;
            margin: 0 auto 30px;
            border-radius: 10px;
            padding: 15px 20px;
            text-align: center;
            font-weight: 500;
        }
        .status-enabled {
            background: #d4edda;
            border: 2px solid #c3e6cb;
            color: #155724;
        }
        .status-disabled {
            background: #f8d7da;
            border: 2px solid #f5c6cb;
            color: #721c24;
        }

        /* Error Message */
        .error-alert {
            max-width: 800px;
            margin: 0 auto 30px;
            background: #f8d7da;
            border: 2px solid #f5c6cb;
            color: #721c24;
            border-radius: 10px;
            padding: 15px 20px;
            text-align: center;
            font-weight: 500;
        }

        /* Form Section - Two Column Layout */
        .form-section {
            display: flex;
            gap: 50px;
            margin-bottom: 60px;
            align-items: center;
        }
        .form-container {
            flex: 1;
            min-width: 400px;
        }
        .image-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .library-image {
            width: 100%;
            max-width: 500px;
            height: auto;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            transition: transform 0.3s ease;
        }
        .library-image:hover {
            transform: scale(1.02);
        }

        /* Form Card */
        .form-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .form-header {
            background: linear-gradient(135deg, #0984e3 0%, #065fa7 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }
        .form-header h3 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }
        .form-body {
            padding: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .input-with-icon {
            position: relative;
        }
        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #0984e3;
            font-size: 1.1rem;
        }
        .form-input {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        .form-input:focus {
            outline: none;
            border-color: #0984e3;
            box-shadow: 0 0 0 3px rgba(9, 132, 227, 0.1);
        }
        .forgot-link {
            text-align: right;
            margin-bottom: 20px;
        }
        .forgot-link a {
            color: #0984e3;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .forgot-link a:hover {
            text-decoration: underline;
        }
        .submit-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #0984e3 0%, #065fa7 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(9, 132, 227, 0.3);
        }
        .form-footer {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        .form-footer a {
            color: #0984e3;
            text-decoration: none;
            font-weight: 600;
        }
        .form-footer a:hover {
            text-decoration: underline;
        }

        /* Image Gallery */
        .gallery-section {
            margin-bottom: 60px;
        }
        .gallery-title {
            text-align: center;
            color: #333;
            font-size: 1.8rem;
            margin-bottom: 30px;
            font-weight: 600;
        }
        .image-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }
        .gallery-item {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .gallery-item:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.2);
        }
        .gallery-img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        /* Feature Section */
        .feature-section {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
        }
        .feature-title {
            color: #0984e3;
            font-size: 2rem;
            margin-bottom: 20px;
            font-weight: 700;
        }
        .feature-description {
            color: #666;
            font-size: 1.1rem;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto 30px;
        }
        .feature-image {
            max-width: 500px;
            width: 100%;
            border-radius: 12px;
            margin: 0 auto;
            display: block;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        /* FOOTER */
        footer {
            background: linear-gradient(135deg, #003366 0%, #002244 100%);
            color: white;
            padding: 50px 0 20px;
            margin-top: auto;
        }
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
        }
        .footer-section h3 {
            font-size: 1.3rem;
            margin-bottom: 20px;
            color: #0984e3;
            border-bottom: 2px solid #0984e3;
            padding-bottom: 10px;
            display: inline-block;
        }
        .footer-section p {
            line-height: 1.6;
            color: #ccc;
        }
        .footer-links {
            list-style: none;
            padding: 0;
        }
        .footer-links li {
            margin-bottom: 10px;
        }
        .footer-links a {
            color: #ccc;
            text-decoration: none;
            transition: color 0.3s;
        }
        .footer-links a:hover {
            color: #0984e3;
        }
        .contact-info li {
            margin-bottom: 10px;
            color: #ccc;
        }
        .social-icons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        .social-icons a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: #0984e3;
            color: white;
            border-radius: 50%;
            text-decoration: none;
            transition: all 0.3s;
        }
        .social-icons a:hover {
            background: white;
            color: #0984e3;
            transform: translateY(-3px);
        }
        .footer-bottom {
            text-align: center;
            padding-top: 30px;
            margin-top: 40px;
            border-top: 1px solid rgba(255,255,255,0.1);
            color: #ccc;
            font-size: 0.9rem;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .form-section {
                flex-direction: column;
                gap: 30px;
            }
            .form-container {
                min-width: 100%;
            }
            .image-container {
                order: -1;
            }
            .library-image {
                max-width: 100%;
            }
        }
        @media (max-width: 768px) {
            main {
                padding: 140px 15px 40px;
            }
            .header-content {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            nav {
                display: flex;
                gap: 15px;
                justify-content: center;
            }
            nav a {
                margin: 0;
            }
            .page-title h1 {
                font-size: 1.8rem;
            }
            .page-title h2 {
                font-size: 1.3rem;
            }
            .image-gallery {
                grid-template-columns: 1fr;
            }
            .feature-section {
                padding: 30px 20px;
            }
            .footer-content {
                grid-template-columns: 1fr;
                gap: 30px;
            }
        }
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
            <a href="register.php">Register</a>
        </nav>
    </div>
</header>

<main>
    <!-- Page Title -->
    <div class="page-title">
        <h1>Ambo University Online Library System</h1>
        <h2>Login to Your Account</h2>
    </div>

    <!-- Status Message -->
    <?php if ($maintenance_mode): ?>
    <div class="status-alert status-disabled">
        <i class="fas fa-tools"></i> System Maintenance in Progress. Only administrator logins are allowed.
    </div>
    <?php else: ?>
    <div class="status-alert status-enabled">
        <i class="fas fa-sign-in-alt"></i> Please login with your credentials to access the library system.
    </div>
    <?php endif; ?>

    <!-- Error Message -->
    <?php if ($error): ?>
    <div class="error-alert">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>

    <!-- Form and Image Section -->
    <div class="form-section">
        <!-- Form Container -->
        <div class="form-container">
            <div class="form-card">
                <div class="form-header">
                    <h3><i class="fas fa-sign-in-alt me-2"></i>User Login</h3>
                </div>
                <div class="form-body">
                    <?php if ($maintenance_mode): ?>
                    <div style="text-align: center; padding: 30px;">
                        <i class="fas fa-exclamation-triangle fa-3x mb-3" style="color: #dc3545;"></i>
                        <h4 style="color: #721c24;">Maintenance Mode Active</h4>
                        <p>The system is currently under maintenance. Only administrator accounts can login at this time.</p>
                        <p class="mt-3">Regular users will be able to login once maintenance is complete.</p>
                    </div>
                    <?php else: ?>
                    <form action="login.php" method="post">
                        <div class="form-group">
                            <div class="input-with-icon">
                                <i class="fas fa-user input-icon"></i>
                                <input type="text" name="login_id" class="form-input" placeholder="Email or Username" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <div class="input-with-icon">
                                <i class="fas fa-lock input-icon"></i>
                                <input type="password" name="password" class="form-input" placeholder="Password" required>
                            </div>
                        </div>
                        
                        <div class="forgot-link">
                            <a href="forgot_password.php">Forgot Password?</a>
                        </div>

                        <button type="submit" class="submit-btn">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </button>

                        <div class="form-footer">
                            Don't have an account? 
                            <?php if (!$allow_registration): ?>
                            <span class="text-muted">Registration is currently disabled</span>
                            <?php else: ?>
                            <a href="register.php">Register Here</a>
                            <?php endif; ?>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Image Container -->
        <div class="image-container">
            <img src="ambos.jpg" alt="Ambo University Library" class="library-image">
        </div>
    </div>

    <!-- Image Gallery -->
    <div class="gallery-section">
        <h3 class="gallery-title">Library Resources & Facilities</h3>
        <div class="image-gallery">
            <div class="gallery-item">
                <img src="li.png" alt="Library Interior" class="gallery-img">
            </div>
            <div class="gallery-item">
                <img src="hachalu.jpg" alt="Study Area" class="gallery-img">
            </div>
            <div class="gallery-item">
                <img src="la.png" alt="Digital Resources" class="gallery-img">
            </div>
        </div>
    </div>

    <!-- Feature Section -->
    <div class="feature-section">
        <h2 class="feature-title">24/7 Digital Access</h2>
        <p class="feature-description">
            Access thousands of e-books, journals, and research papers anytime, anywhere with our online library portal. 
            Our digital resources are constantly updated to support your academic and research needs.
        </p>
        <img src="la.png" alt="Digital Access" class="feature-image">
    </div>
</main>

<footer>
    <div class="footer-content">
        <div class="footer-section">
            <h3>ABOUT US</h3>
            <p>Ambo University Library (AUL) supports the academic community with reliable information for research, innovation, and quality education.</p>
        </div>
        
        <div class="footer-section">
            <h3>QUICK LINKS</h3>
            <ul class="footer-links">
                <li><a href="#">Home</a></li>
                <li><a href="#">About</a></li>
                <li><a href="#">Services</a></li>
                <li><a href="#">Contact</a></li>
            </ul>
        </div>
        
        <div class="footer-section">
            <h3>CONTACT INFO</h3>
            <ul class="footer-links contact-info">
                <li><i class="fas fa-map-marker-alt me-2"></i> Ambo University, Ethiopia</li>
                <li><i class="fas fa-phone me-2"></i> (+25) 1112 36 81 60</li>
                <li><i class="fas fa-envelope me-2"></i> info@ambou.edu.et</li>
            </ul>
            <div class="social-icons">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-linkedin-in"></i></a>
            </div>
        </div>
    </div>
    
    <div class="footer-bottom">
        <p>&copy; 2024 Ambo University Library Management System. All Rights Reserved.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Password toggle functionality
    document.addEventListener('DOMContentLoaded', function() {
        const passwordInput = document.querySelector('input[name="password"]');
        if (passwordInput) {
            const passwordGroup = passwordInput.parentElement;
            const toggleBtn = document.createElement('button');
            toggleBtn.type = 'button';
            toggleBtn.className = 'password-toggle';
            toggleBtn.innerHTML = '<i class="fas fa-eye"></i>';
            toggleBtn.style.position = 'absolute';
            toggleBtn.style.right = '15px';
            toggleBtn.style.top = '50%';
            toggleBtn.style.transform = 'translateY(-50%)';
            toggleBtn.style.background = 'none';
            toggleBtn.style.border = 'none';
            toggleBtn.style.color = '#666';
            toggleBtn.style.cursor = 'pointer';
            
            toggleBtn.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
            });
            
            passwordGroup.appendChild(toggleBtn);
        }
        
        // Auto-focus on login field
        const loginInput = document.querySelector('input[name="login_id"]');
        if (loginInput) {
            loginInput.focus();
        }
    });
</script>
</body>
</html>
<?php
if (isset($conn)) {
    $conn->close();
}
?>