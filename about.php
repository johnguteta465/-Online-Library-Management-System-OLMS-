<?php
// about.php - Updated with W3Schools-inspired color palette
session_start();
include "db.php";

$is_logged_in = isset($_SESSION['user_id']);
$user_name = $is_logged_in ? $_SESSION['user_name'] : '';
$user_role = $is_logged_in ? $_SESSION['user_role'] : '';
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>About Library - Ambo University Digital Library</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
    :root {
        --w3-bg: #96D4D4; /* Light Teal background from image */
        --w3-green: #04AA6D; /* Main Green button color */
        --w3-green-hover: #059862;
        --w3-dark-blue: #282A35; /* Dark footer/header color */
        --text-color: #212529;
    }

    body {
        font-family: 'Poppins', sans-serif;
        background-color: var(--w3-bg);
        color: var(--text-color);
        margin: 0;
        padding: 0;
    }

    /* Header Styles */
    .app-header {
        background-color: white;
        height: 75px;
        position: fixed;
        top: 0;
        width: 100%;
        z-index: 1000;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0 40px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .header-logo { height: 50px; margin-right: 15px; }
    .header-title { font-weight: 700; color: var(--w3-dark-blue); font-size: 1.2rem; }

    .nav-links { list-style: none; display: flex; gap: 25px; margin: 0; }
    .nav-links a { 
        text-decoration: none; 
        color: var(--w3-dark-blue); 
        font-weight: 500; 
        transition: 0.3s;
    }
    .nav-links a:hover, .nav-links a.active { color: var(--w3-green); }

    /* Main Content */
    .main-content {
        margin-top: 100px;
        padding-bottom: 60px;
    }

    .container-custom {
        max-width: 1000px;
        margin: auto;
    }

    /* Cards inspired by W3Schools Example Box */
    .about-section {
        background: white;
        border-radius: 12px;
        padding: 45px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        margin-bottom: 40px;
    }

    .page-title {
        font-size: 3rem;
        font-weight: 800;
        text-align: center;
        margin-bottom: 50px;
        color: var(--w3-dark-blue);
    }

    .section-title {
        font-weight: 700;
        margin-bottom: 25px;
        color: var(--w3-dark-blue);
        border-left: 6px solid var(--w3-green);
        padding-left: 15px;
    }

    /* Buttons inspired by "Try it Yourself" */
    .w3-btn {
        background-color: var(--w3-green);
        color: white;
        padding: 12px 30px;
        border-radius: 25px;
        border: none;
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
        transition: 0.3s;
    }

    .w3-btn:hover {
        background-color: var(--w3-green-hover);
        color: white;
        transform: translateY(-2px);
    }

    /* Statistics Section */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-top: 30px;
    }

    .stat-card {
        background: #F1F1F1;
        padding: 25px;
        text-align: center;
        border-radius: 8px;
    }

    .stat-number { font-size: 2rem; font-weight: 700; color: var(--w3-green); }

    /* Footer */
    footer {
        background-color: var(--w3-dark-blue);
        color: white;
        padding: 50px 0 20px;
    }

    footer a { color: #DDD; text-decoration: none; }
    footer a:hover { color: var(--w3-bg); }

    .footer-bottom {
        border-top: 1px solid #444;
        margin-top: 30px;
        padding-top: 20px;
        text-align: center;
        font-size: 0.9rem;
    }
</style>
</head>
<body>

<header class="app-header">
    <div class="d-flex align-items-center">
        <img src="ambo.png" alt="Ambo University Logo" class="header-logo" />
        <span class="header-title d-none d-md-block">Ambo University Library</span>
    </div>
    
    <nav class="d-none d-lg-block">
        <ul class="nav-links">
           
            <li><a href="news.php">News</a></li>
            <li><a href="about.php" class="active">About</a></li>
        </ul>
    </nav>
    
    <div>
        <?php if ($is_logged_in): ?>
            <a href="dashboard.php" class="w3-btn" style="padding: 8px 20px;">Dashboard</a>
        <?php else: ?>
            <a href="login.php" class="w3-btn" style="padding: 8px 20px;">Login</a>
        <?php endif; ?>
    </div>
</header>

<main class="main-content">
    <div class="container container-custom">
        <h1 class="page-title">Digital Library SQL</h1>
        
        <section class="about-section">
            <h2 class="section-title">Our Digital Mission</h2>
            <p class="lead">
                Like a well-structured query, our library aims to <code>SELECT</code> the best knowledge resources and <code>JOIN</code> them with our academic community.
            </p>
            <p>
                Ambo University Digital Library provides a seamless interface for students and faculty to access academic resources 24/7. Our system is built for speed, security, and accessibility.
            </p>
            <div class="mt-4">
                <a href="register.php" class="w3-btn">Get Started Now</a>
            </div>
        </section>

        <section class="about-section">
            <h2 class="section-title">System Features</h2>
            <div class="row g-4">
                <div class="col-md-6">
                    <h5><i class="fas fa-bolt text-warning me-2"></i> Fast Retrieval</h5>
                    <p class="text-muted">Instant access to over 50,000 digital copies of textbooks and research papers.</p>
                </div>
                <div class="col-md-6">
                    <h5><i class="fas fa-lock text-primary me-2"></i> Secure Access</h5>
                    <p class="text-muted">Role-based authentication ensuring data integrity for all university members.</p>
                </div>
            </div>
        </section>

        <section class="about-section" style="background-color: var(--w3-dark-blue); color: white;">
            <h2 class="section-title" style="color: white; border-color: var(--w3-bg);">Library Statistics</h2>
            <div class="stats-grid">
                <div class="stat-card" style="background: rgba(255,255,255,0.1);">
                    <div class="stat-number">50K+</div>
                    <div class="stat-label">Resources</div>
                </div>
                <div class="stat-card" style="background: rgba(255,255,255,0.1);">
                    <div class="stat-number">5K+</div>
                    <div class="stat-label">Active Users</div>
                </div>
                <div class="stat-card" style="background: rgba(255,255,255,0.1);">
                    <div class="stat-number">99.9%</div>
                    <div class="stat-label">Uptime</div>
                </div>
            </div>
        </section>

        <section class="about-section">
            <h2 class="section-title">Contact Information</h2>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <p><strong><i class="fas fa-map-marker-alt me-2"></i> Location:</strong><br>Main Campus, Ambo, Ethiopia</p>
                </div>
                <div class="col-md-6 mb-3">
                    <p><strong><i class="fas fa-envelope me-2"></i> Email:</strong><br>library@ambou.edu.et</p>
                </div>
            </div>
        </section>
    </div>
</main>

<footer>
    <div class="container container-custom">
        <div class="row">
            <div class="col-md-6">
                <h4 style="color: var(--w3-bg);">AMBO UNIVERSITY</h4>
                <p>Developing the next generation of scholars through digital innovation.</p>
            </div>
            <div class="col-md-6 text-md-end">
                <a href="library.php" class="me-3">Home</a>
                <a href="news.php" class="me-3">News</a>
                <a href="about.php">About</a>
            </div>
        </div>
        <div class="footer-bottom">
            &copy; <?php echo date("Y"); ?> Ambo University Digital Library. All Rights Reserved.
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>