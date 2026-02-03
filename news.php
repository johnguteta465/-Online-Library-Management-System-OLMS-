<?php
// news.php - News & Events with W3Schools-inspired color palette
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
<title>News & Events - Ambo University Digital Library</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
    :root {
        --w3-bg: #96D4D4; /* Light Teal background from image */
        --w3-green: #04AA6D; /* Main Green button color */
        --w3-green-hover: #059862;
        --w3-dark-blue: #282A35; /* Dark footer/header color */
        --w3-light-gray: #F1F1F1; /* Light gray for cards */
        --text-color: #212529;
    }

    body {
        font-family: 'Poppins', sans-serif;
        background-color: var(--w3-bg);
        color: var(--text-color);
        margin: 0;
        padding: 0;
        min-height: 100vh;
    }

    /* Header Styles - Same as about.php */
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

    .header-logo { 
        height: 50px; 
        margin-right: 15px; 
    }
    
    .header-title { 
        font-weight: 700; 
        color: var(--w3-dark-blue); 
        font-size: 1.2rem; 
    }

    .nav-links { 
        list-style: none; 
        display: flex; 
        gap: 25px; 
        margin: 0; 
        padding: 0;
    }
    
    .nav-links a { 
        text-decoration: none; 
        color: var(--w3-dark-blue); 
        font-weight: 500; 
        transition: 0.3s;
        padding: 8px 15px;
        border-radius: 4px;
    }
    
    .nav-links a:hover { 
        color: var(--w3-green); 
        background-color: rgba(4, 170, 109, 0.1);
    }
    
    .nav-links a.active { 
        color: var(--w3-green); 
        background-color: rgba(4, 170, 109, 0.1);
    }

    /* Main Content */
    .main-content {
        margin-top: 100px;
        padding-bottom: 60px;
    }

    .container-custom {
        max-width: 1000px;
        margin: auto;
    }

    /* Page Title */
    .page-title {
        font-size: 3rem;
        font-weight: 800;
        text-align: center;
        margin-bottom: 50px;
        color: var(--w3-dark-blue);
    }

    /* Section Styling */
    .section {
        background: white;
        border-radius: 12px;
        padding: 45px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        margin-bottom: 40px;
    }

    .section-title {
        font-weight: 700;
        margin-bottom: 25px;
        color: var(--w3-dark-blue);
        border-left: 6px solid var(--w3-green);
        padding-left: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    /* News Cards */
    .news-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 25px;
        margin-top: 30px;
    }

    .news-card {
        background: var(--w3-light-gray);
        border-radius: 8px;
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border-top: 4px solid var(--w3-green);
    }

    .news-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.15);
    }

    .news-image {
        height: 180px;
        background: linear-gradient(135deg, var(--w3-green) 0%, #059862 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 40px;
    }

    .news-content {
        padding: 25px;
    }

    .news-date {
        color: #666;
        font-size: 14px;
        font-weight: 500;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .news-title {
        color: var(--w3-dark-blue);
        font-size: 1.2rem;
        font-weight: 600;
        margin-bottom: 15px;
        line-height: 1.4;
    }

    .news-excerpt {
        color: #555;
        line-height: 1.6;
        font-size: 15px;
        margin-bottom: 20px;
    }

    .news-category {
        display: inline-block;
        padding: 4px 12px;
        background: rgba(4, 170, 109, 0.1);
        color: var(--w3-green);
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
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
        cursor: pointer;
    }

    .w3-btn:hover {
        background-color: var(--w3-green-hover);
        color: white;
        transform: translateY(-2px);
    }

    /* Event Cards */
    .event-item {
        display: flex;
        gap: 20px;
        padding: 25px;
        background: var(--w3-light-gray);
        border-radius: 8px;
        margin-bottom: 20px;
        border-left: 4px solid #FFC107;
    }

    .event-date {
        text-align: center;
        min-width: 80px;
        background: white;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.08);
    }

    .event-day {
        font-size: 2rem;
        font-weight: 700;
        color: var(--w3-green);
        line-height: 1;
    }

    .event-month {
        font-size: 14px;
        color: var(--w3-dark-blue);
        font-weight: 600;
        text-transform: uppercase;
    }

    .event-details {
        flex: 1;
    }

    .event-title {
        color: var(--w3-dark-blue);
        font-weight: 600;
        font-size: 1.1rem;
        margin-bottom: 10px;
    }

    .event-time {
        color: #666;
        font-size: 14px;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .event-description {
        color: #555;
        font-size: 14px;
        line-height: 1.6;
    }

    /* Announcements */
    .announcements-section {
        background: linear-gradient(135deg, var(--w3-dark-blue) 0%, #1a1d28 100%);
        color: white;
        border-radius: 12px;
        padding: 45px;
        margin-bottom: 40px;
    }

    .announcements-section .section-title {
        color: white;
        border-color: var(--w3-bg);
    }

    .announcement-item {
        padding: 15px 0;
        border-bottom: 1px solid rgba(255,255,255,0.1);
        display: flex;
        align-items: flex-start;
        gap: 15px;
    }

    .announcement-item:last-child {
        border-bottom: none;
    }

    .announcement-icon {
        color: var(--w3-bg);
        font-size: 18px;
        margin-top: 3px;
    }

    .announcement-title {
        font-weight: 500;
        font-size: 1rem;
        margin-bottom: 5px;
    }

    .announcement-date {
        font-size: 12px;
        opacity: 0.8;
        font-style: italic;
    }

    /* Footer */
    footer {
        background-color: var(--w3-dark-blue);
        color: white;
        padding: 50px 0 20px;
    }

    footer a { 
        color: #DDD; 
        text-decoration: none; 
        transition: color 0.3s;
    }
    
    footer a:hover { 
        color: var(--w3-bg); 
    }

    .footer-bottom {
        border-top: 1px solid #444;
        margin-top: 30px;
        padding-top: 20px;
        text-align: center;
        font-size: 0.9rem;
        color: #AAA;
    }

    /* Responsive Design */
    @media (max-width: 992px) {
        .app-header {
            padding: 0 20px;
            flex-wrap: wrap;
            height: auto;
            padding: 15px;
        }
        
        .nav-links {
            margin-top: 15px;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .main-content {
            margin-top: 120px;
        }
        
        .page-title {
            font-size: 2.5rem;
        }
    }
    
    @media (max-width: 768px) {
        .page-title {
            font-size: 2rem;
        }
        
        .section {
            padding: 30px;
        }
        
        .event-item {
            flex-direction: column;
            text-align: center;
        }
        
        .event-date {
            margin: 0 auto;
        }
        
        .news-grid {
            grid-template-columns: 1fr;
        }
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
            
            <li><a href="news.php" class="active">News</a></li>
            <li><a href="about.php">About</a></li>
        </ul>
    </nav>
    
    <div>
        <?php if ($is_logged_in): ?>
            <a href="dashboard.php" class="w3-btn" style="padding: 8px 20px;">
                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
            </a>
        <?php else: ?>
            <a href="login.php" class="w3-btn" style="padding: 8px 20px;">
                <i class="fas fa-sign-in-alt me-2"></i>Login
            </a>
        <?php endif; ?>
    </div>
</header>

<main class="main-content">
    <div class="container container-custom">
        <h1 class="page-title">Library News & Events</h1>
        
        <!-- Latest News Section -->
        <section class="section">
            <h2 class="section-title">
                <i class="fas fa-newspaper"></i> Latest News
            </h2>
            
            <div class="news-grid">
                <!-- News Card 1 -->
                <div class="news-card">
                    <div class="news-image">
                        <i class="fas fa-laptop-code"></i>
                    </div>
                    <div class="news-content">
                        <div class="news-date">
                            <i class="far fa-calendar"></i> March 15, 2024
                        </div>
                        <span class="news-category">System Update</span>
                        <h3 class="news-title">New Library System Launch</h3>
                        <p class="news-excerpt">
                            We're excited to announce the launch of our new Online Library Management System. 
                            Enjoy enhanced features and improved accessibility.
                        </p>
                        <button class="w3-btn w-100">
                            <i class="fas fa-book-reader me-2"></i> Read More
                        </button>
                    </div>
                </div>
                
                <!-- News Card 2 -->
               
               
        
        <!-- Important Announcements Section -->
        <section class="announcements-section">
            <h2 class="section-title">
                <i class="fas fa-bullhorn"></i> Important Announcements
            </h2>
            
            <div class="announcements-list">
                <div class="announcement-item">
                    <div class="announcement-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="announcement-content">
                        <div class="announcement-title">
                            Library Closed on March 8 for International Women's Day
                        </div>
                        <div class="announcement-date">Posted: March 1, 2024</div>
                    </div>
                </div>
                
                <div class="announcement-item">
                    <div class="announcement-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="announcement-content">
                        <div class="announcement-title">
                            Book Donation Drive - March 15-30, 2024
                        </div>
                        <div class="announcement-date">Posted: March 1, 2024</div>
                    </div>
                </div>
                
                <div class="announcement-item">
                    <div class="announcement-icon">
                        <i class="fas fa-wifi"></i>
                    </div>
                    <div class="announcement-content">
                        <div class="announcement-title">
                            Library Wi-Fi Upgrade - Faster Speeds Available
                        </div>
                        <div class="announcement-date">Posted: February 28, 2024</div>
                    </div>
                </div>
                
                <div class="announcement-item">
                    <div class="announcement-icon">
                        <i class="fas fa-door-open"></i>
                    </div>
                    <div class="announcement-content">
                        <div class="announcement-title">
                            New Study Rooms Now Available for Online Booking
                        </div>
                        <div class="announcement-date">Posted: February 25, 2024</div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</main>

<footer>
    <div class="container container-custom">
        <div class="row">
            <div class="col-md-6">
                <h4 style="color: var(--w3-bg);">AMBO UNIVERSITY LIBRARY</h4>
                <p>Stay updated with the latest library news, events, and system updates.</p>
            </div>
            <div class="col-md-6 text-md-end">
                
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
<script>
    // Event Registration Function
    function registerEvent(button, eventId) {
        const eventTitle = button.closest('.event-details').querySelector('.event-title').textContent;
        
        if (confirm(`Register for "${eventTitle}"?`)) {
            // Update button state
            button.innerHTML = '<i class="fas fa-check me-2"></i> Registered';
            button.style.backgroundColor = '#28a745';
            button.onclick = null;
            button.disabled = true;
            
            // Show success message
            const successMsg = document.createElement('div');
            successMsg.className = 'alert alert-success mt-3';
            successMsg.innerHTML = `
                <i class="fas fa-check-circle me-2"></i>
                Successfully registered for "${eventTitle}". Confirmation email will be sent.
            `;
            
            button.closest('.event-details').appendChild(successMsg);
            
            // Remove message after 5 seconds
            setTimeout(() => successMsg.remove(), 5000);
            
            // Save to localStorage
            const registrations = JSON.parse(localStorage.getItem('eventRegistrations') || '{}');
            registrations[eventId] = true;
            localStorage.setItem('eventRegistrations', JSON.stringify(registrations));
        }
    }
    
    // Check for existing registrations on page load
    document.addEventListener('DOMContentLoaded', function() {
        const registrations = JSON.parse(localStorage.getItem('eventRegistrations') || '{}');
        
        document.querySelectorAll('.w3-btn').forEach(btn => {
            const eventId = btn.getAttribute('onclick');
            if (eventId) {
                const match = eventId.match(/registerEvent\(this, '([^']+)'\)/);
                if (match && registrations[match[1]]) {
                    btn.innerHTML = '<i class="fas fa-check me-2"></i> Registered';
                    btn.style.backgroundColor = '#28a745';
                    btn.onclick = null;
                    btn.disabled = true;
                }
            }
        });
    });
    
    // Read More buttons
    document.querySelectorAll('.news-card .w3-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const newsTitle = this.closest('.news-card').querySelector('.news-title').textContent;
            alert(`This is a demo. In a real system, this would show full article for: "${newsTitle}"`);
        });
    });
</script>
</body>
</html>