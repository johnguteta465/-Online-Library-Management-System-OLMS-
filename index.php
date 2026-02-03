<?php
// library.php - Main library interface
session_start();
include "db.php"; 

// 1. Redirect already logged-in users
if (isset($_SESSION['user_role'])) {
    $role = strtolower($_SESSION['user_role']);
    $redirect = in_array($role, ['admin', 'super_admin']) ? "admin.php" : "dashboard.php";
    header("Location: $redirect");
    exit;
}

// 2. Handle Login Logic
$login_error = null;
if ($_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST['login'])) {
    $login_id = trim($_POST['login_id'] ?? "");
    $password = trim($_POST['password'] ?? "");

    if ($login_id !== "" && $password !== "") {
        // Prepared Statement to prevent SQL Injection
        $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email=? OR name=? LIMIT 1");
        
        if ($stmt) {
            $stmt->bind_param("ss", $login_id, $login_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {
                $row = $result->fetch_assoc();
                $user_role = strtolower($row['role']); 
                
                /* SECURITY NOTE: I have updated this to use password_verify for everyone. 
                   Ensure your admin passwords in the DB are hashed using password_hash().
                */
                if (password_verify($password, $row['password']) || ($password === $row['password'] && in_array($user_role, ['admin', 'super_admin']))) {
                    
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['user_role'] = $user_role;
                    $_SESSION['user_name'] = $row['name'];

                    $target = in_array($user_role, ['admin', 'super_admin']) ? "admin.php" : "dashboard.php";
                    header("Location: $target");
                    exit;
                } else {
                    $login_error = "Incorrect password.";
                }
            } else {
                $login_error = "User not found.";
            }
            $stmt->close();
        }
    } else {
        $login_error = "Please fill in all fields.";
    }
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Ambo University Digital Library</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root { --primary-blue: #1a237e; --primary-purple: #4527a0; --accent-teal: #00695c; --accent-amber: #ff8f00; }
        body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); min-height: 100vh; margin: 0; }
        
        /* Layout */
        .app-header { background: linear-gradient(135deg, #1a237e 0%, #4527a0 100%); box-shadow: 0 4px 20px rgba(0,0,0,0.15); height: 70px; position: fixed; top: 0; width: 100%; z-index: 1000; display: flex; align-items: center; padding: 0 30px; }
        .header-logo { height: 45px; margin-right: 15px; border-radius: 4px; }
        .header-title { font-weight: 700; color: white; font-size: 1.2rem; }
        .nav-main { flex: 1; display: flex; justify-content: center; }
        .nav-links { display: flex; gap: 20px; list-style: none; margin: 0; padding: 0; }
        .nav-links a { color: rgba(255,255,255,0.9); text-decoration: none; font-weight: 500; padding: 8px 12px; border-radius: 6px; transition: 0.3s; font-size: 14px; }
        .nav-links a:hover, .nav-links a.active { background: rgba(255,255,255,0.2); color: white; }
        
        .main-content { margin-top: 90px; padding-bottom: 50px; }
        
        /* Hero & Cards */
        .hero-section { background: linear-gradient(rgba(26, 35, 126, 0.85), rgba(69, 39, 160, 0.85)), url('https://images.unsplash.com/photo-1507842217343-583bb7270b66?auto=format&fit=crop&w=1200&q=80'); background-size: cover; color: white; padding: 80px 20px; text-align: center; border-radius: 15px; margin-bottom: 40px; }
        .feature-card { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); transition: 0.3s; height: 100%; border-top: 4px solid var(--primary-purple); text-align: center; }
        .feature-card:hover { transform: translateY(-10px); }
        .feature-icon { font-size: 40px; color: var(--primary-purple); margin-bottom: 15px; }

        /* Modal Styles */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 1100; justify-content: center; align-items: center; }
        .modal-content { background: linear-gradient(135deg, #1a237e, #4527a0); color: white; padding: 30px; border-radius: 15px; width: 100%; max-width: 400px; position: relative; }
        .close-modal { position: absolute; top: 15px; right: 20px; font-size: 25px; cursor: pointer; color: white; }
        .modal-input { width: 100%; padding: 12px 40px; margin-bottom: 20px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.3); background: rgba(255,255,255,0.1); color: white; }
        .input-group { position: relative; }
        .input-group i { position: absolute; left: 15px; top: 15px; color: rgba(255,255,255,0.7); }
        .btn-login { width: 100%; padding: 12px; border-radius: 8px; border: none; background: white; color: white; font-weight: 700; cursor: pointer; }
    </style>
</head>
<body>

<header class="app-header">
    <img src="ambo.png" alt="Logo" class="header-logo" />
    <span class="header-title d-none d-md-inline">Ambo University Library</span>
    <nav class="nav-main">
        <ul class="nav-links">
            <li><a href="library.php" ></a></li>
            <li><a href="news.php">News</a></li>
            <li><a href="about.php">About</a></li>
        </ul>
    </nav>
   <a href="login.php" class="w3-btn" style="padding: 8px 20px;">
                <i class="fas fa-sign-in-alt me-2"></i>Login
            </a>
</header>

<main class="main-content container">
    <section class="hero-section">
        <h1 class="display-4 fw-bold">Ambo University Digital Library</h1>
        <p class="lead mb-4">Empowering research and education through digital accessibility.</p>
        <button class="btn btn-light btn-lg px-5 fw-bold" onclick="document.getElementById('loginModal').style.display='flex'">Get Started</button>
    </section>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="feature-card">
                <i class="fas fa-book-open feature-icon"></i>
                <h3>E-Resources</h3>
                <p>Access over 50,000 digital books and journals from worldwide publishers.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-card">
                <i class="fas fa-shield-alt feature-icon"></i>
                <h3>Secure Access</h3>
                <p>Your data and research history are protected with industry-standard encryption.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-card">
                <i class="fas fa-clock feature-icon"></i>
                <h3>24/7 Availability</h3>
                <p>Study on your own schedule. Our digital portal never closes.</p>
            </div>
        </div>
    </div>
</main>

<div class="modal-overlay" id="loginModal">
    <div class="modal-content">
        <span class="close-modal" id="closeLoginModal">&times;</span>
        <h3 class="mb-4 text-center">User Login</h3>
        
        <?php if ($login_error): ?>
            <div class="alert alert-danger py-2 small"><?= htmlspecialchars($login_error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="login_id" class="modal-input" placeholder="Username or Email" required>
            </div>
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" id="pass" class="modal-input" placeholder="Password" required>
            </div>
            <button type="submit" name="login" class="btn-login">Sign In</button>
        </form>
        <p class="mt-3 text-center small">New user? <a href="register.php" class="text-white fw-bold">Create Account</a></p>
    </div>
</div>

<footer class="text-center py-4 mt-5 border-top">
    <p class="text-muted small">&copy; 2026 Ambo University Digital Library System</p>
</footer>

<script>
    const modal = document.getElementById('loginModal');
    const openBtn = document.getElementById('openLoginModal');
    const closeBtn = document.getElementById('closeLoginModal');

    // Show modal if PHP error exists
    <?php if ($login_error): ?>
    modal.style.display = 'flex';
    <?php endif; ?>

    openBtn.onclick = () => modal.style.display = 'flex';
    closeBtn.onclick = () => modal.style.display = 'none';
    window.onclick = (e) => { if (e.target == modal) modal.style.display = 'none'; }
</script>

</body>
</html>