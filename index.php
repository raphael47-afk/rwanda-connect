<?php
declare(strict_types=1);
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Rwanda Connect - connecting citizens to opportunities and community services.">
    <title>Rwanda Connect | Home</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="site-header">
    <a class="brand" href="index.php"><span class="brand-mark">RC</span> Rwanda Connect</a>
    <nav aria-label="Main navigation">
        <a href="#services">Services</a>
        <a href="#about">About</a>
        <a class="nav-button" href="#contact">Contact</a>
    </nav>
</header>

<main>
    <section class="hero">
        <div class="hero-content">
            <p class="eyebrow">ONE PLATFORM. MANY SOLUTIONS.</p>
            <h1>Connecting citizens to opportunities and services.</h1>
            <p class="hero-copy">Rwanda Connect is a student-developed platform concept designed to make useful information and community services easier to find.</p>
            <a class="primary-button" href="#services">Explore services</a>
        </div>
        <div class="hero-panel" aria-label="Platform summary">
            <div class="hero-panel-icon">🇷🇼</div>
            <h2>A connected Rwanda</h2>
            <p>Information, opportunities and community participation in one place.</p>
            <div class="panel-stats">
                <div><strong>7</strong><span>Focus areas</span></div>
                <div><strong>1</strong><span>Shared platform</span></div>
            </div>
        </div>
    </section>

    <section class="section" id="services">
        <div class="section-heading">
            <p class="eyebrow">WHAT YOU CAN FIND</p>
            <h2>Services for everyday needs</h2>
            <p>These are the planned service areas for the first version of the project.</p>
        </div>
        <div class="service-grid">
            <article class="service-card"><span>🌱</span><h3>Agriculture</h3><p>Farming information and market opportunities.</p></article>
            <article class="service-card"><span>💼</span><h3>Employment</h3><p>Job announcements and career resources.</p></article>
            <article class="service-card"><span>🎓</span><h3>Education</h3><p>Scholarships, learning and training information.</p></article>
            <article class="service-card"><span>🏥</span><h3>Healthcare</h3><p>Information about health facilities and services.</p></article>
            <article class="service-card"><span>🤝</span><h3>Community</h3><p>A channel for reporting local issues.</p></article>
            <article class="service-card"><span>♻️</span><h3>Environment</h3><p>Environmental and sanitation reports.</p></article>
            <article class="service-card"><span>📈</span><h3>Business</h3><p>Entrepreneurship and economic resources.</p></article>
        </div>
    </section>

    <section class="about-section" id="about">
        <div>
            <p class="eyebrow">ABOUT THE PROJECT</p>
            <h2>Technology with a community purpose</h2>
        </div>
        <p>Rwanda Connect is an educational web application project built with HTML, CSS, JavaScript, PHP and MySQL. It will be developed in stages, tested locally and improved based on feedback. It is not an official government service.</p>
    </section>

    <section class="contact-section" id="contact">
        <h2>Be part of a connected community</h2>
        <p>This first version is a demonstration homepage. Registration, service listings and reporting will be added in later development stages.</p>
        <a class="secondary-button" href="README.txt">Read project setup instructions</a>
    </section>
</main>

<footer class="site-footer">
    <p>© <?php echo date('Y'); ?> Rwanda Connect — Student project prototype.</p>
</footer>
<script src="assets/js/script.js"></script>
</body>
</html>
