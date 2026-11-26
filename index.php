<?php
declare(strict_types=1);
<<<<<<< HEAD

/**
 * Rwanda Connect - Homepage
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// Check remember token
checkRememberToken();

$pageTitle = 'Rwanda Connect | Home';
$pageDescription = 'Connecting citizens to opportunities and services across Rwanda.';

require_once __DIR__ . '/includes/header.php';
?>
=======
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
>>>>>>> a033a458f66fdfbb1d6ecd7311dc9936ea333cfb

<main>
    <section class="hero">
        <div class="hero-content">
            <p class="eyebrow">ONE PLATFORM. MANY SOLUTIONS.</p>
            <h1>Connecting citizens to opportunities and services.</h1>
<<<<<<< HEAD
            <p class="hero-copy">Rwanda Connect is a comprehensive platform designed to make useful information, community services, and opportunities easier to find and access.</p>
            <div class="hero-buttons">
                <a class="primary-button" href="/rwanda_connect/auth/register.php">Get Started Free</a>
                <a class="secondary-button" href="#services">Explore Services</a>
            </div>
=======
            <p class="hero-copy">Rwanda Connect is a student-developed platform concept designed to make useful information and community services easier to find.</p>
            <a class="primary-button" href="#services">Explore services</a>
>>>>>>> a033a458f66fdfbb1d6ecd7311dc9936ea333cfb
        </div>
        <div class="hero-panel" aria-label="Platform summary">
            <div class="hero-panel-icon">🇷🇼</div>
            <h2>A connected Rwanda</h2>
            <p>Information, opportunities and community participation in one place.</p>
            <div class="panel-stats">
<<<<<<< HEAD
                <div><strong>7</strong><span>Service Modules</span></div>
                <div><strong>1</strong><span>Unified Platform</span></div>
                <div><strong>24/7</strong><span>Access</span></div>
=======
                <div><strong>7</strong><span>Focus areas</span></div>
                <div><strong>1</strong><span>Shared platform</span></div>
>>>>>>> a033a458f66fdfbb1d6ecd7311dc9936ea333cfb
            </div>
        </div>
    </section>

    <section class="section" id="services">
        <div class="section-heading">
<<<<<<< HEAD
            <p class="eyebrow">WHAT YOU CAN ACCESS</p>
            <h2>Services for Every Need</h2>
            <p>Comprehensive modules covering key aspects of daily life and development.</p>
        </div>
        <div class="service-grid">
            <article class="service-card" onclick="location.href='/rwanda_connect/modules/agriculture/'">
                <span>🌱</span>
                <h3>Agriculture</h3>
                <p>Farm management, market prices, crop guides, season calendars, and expert advisory.</p>
                <ul class="service-features">
                    <li>🏞️ Farm & crop tracking</li>
                    <li>💰 Real-time market prices</li>
                    <li>📅 Season calendars</li>
                    <li>👨‍🌾 Expert advisors</li>
                </ul>
            </article>
            <article class="service-card" onclick="location.href='/rwanda_connect/modules/employment/'">
                <span>💼</span>
                <h3>Employment</h3>
                <p>Job search, applications tracking, saved jobs, alerts, and career resources.</p>
                <ul class="service-features">
                    <li>🔍 Advanced job search</li>
                    <li>📄 Application tracking</li>
                    <li>⭐ Save & compare jobs</li>
                    <li>🔔 Email job alerts</li>
                </ul>
            </article>
            <article class="service-card" onclick="location.href='/rwanda_connect/modules/education/'">
                <span>🎓</span>
                <h3>Education</h3>
                <p>Scholarships, courses, institutions, education profile, and learning resources.</p>
                <ul class="service-features">
                    <li>💰 Scholarship database</li>
                    <li>📚 Course finder</li>
                    <li>🏫 Institution directory</li>
                    <li>👤 Education profile</li>
                </ul>
            </article>
            <article class="service-card" onclick="location.href='/rwanda_connect/modules/healthcare/'">
                <span>🏥</span>
                <h3>Healthcare</h3>
                <p>Facility finder, appointment booking, health records, campaigns, and emergency info.</p>
                <ul class="service-features">
                    <li>🏥 Facility search</li>
                    <li>📅 Book appointments</li>
                    <li>📋 Health records</li>
                    <li>💉 Health campaigns</li>
                </ul>
            </article>
            <article class="service-card">
                <span>🤝</span>
                <h3>Community</h3>
                <p>A channel for reporting local issues and community engagement.</p>
                <ul class="service-features">
                    <li>📍 Issue reporting</li>
                    <li>🗳️ Community voting</li>
                    <li>📊 Progress tracking</li>
                </ul>
            </article>
            <article class="service-card">
                <span>♻️</span>
                <h3>Environment</h3>
                <p>Environmental and sanitation reporting for cleaner communities.</p>
                <ul class="service-features">
                    <li>🗑️ Waste reporting</li>
                    <li>🌳 Green initiatives</li>
                    <li>📈 Impact tracking</li>
                </ul>
            </article>
            <article class="service-card">
                <span>📈</span>
                <h3>Business</h3>
                <p>Entrepreneurship resources, business registration, and economic opportunities.</p>
                <ul class="service-features">
                    <li>💼 Business resources</li>
                    <li>📋 Registration guides</li>
                    <li>💰 Funding opportunities</li>
                </ul>
            </article>
        </div>
    </section>

    <section class="section features-section" id="features">
        <div class="section-heading">
            <p class="eyebrow">WHY RWANDA CONNECT</p>
            <h2>Built for Citizens</h2>
            <p>Designed with your needs in mind - accessible, secure, and comprehensive.</p>
        </div>
        <div class="features-grid">
            <article class="feature-card">
                <span class="feature-icon">🔐</span>
                <h3>Secure & Private</h3>
                <p>Your data is protected with encryption, secure authentication, and privacy controls.</p>
            </article>
            <article class="feature-card">
                <span class="feature-icon">📱</span>
                <h3>Mobile First</h3>
                <p>Works seamlessly on any device - phone, tablet, or desktop computer.</p>
            </article>
            <article class="feature-card">
                <span class="feature-icon">🇷🇼</span>
                <h3>Local Focus</h3>
                <p>Built specifically for Rwanda with local languages, locations, and services.</p>
            </article>
            <article class="feature-card">
                <span class="feature-icon">⚡</span>
                <h3>Real-time Updates</h3>
                <p>Get instant notifications for jobs, scholarships, health alerts, and market prices.</p>
            </article>
            <article class="feature-card">
                <span class="feature-icon">🤝</span>
                <h3>Community Driven</h3>
                <p>Connect with experts, advisors, and fellow citizens in your community.</p>
            </article>
            <article class="feature-card">
                <span class="feature-icon">📊</span>
                <h3>Personal Dashboard</h3>
                <p>Track everything in one place - applications, appointments, farms, and more.</p>
            </article>
=======
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
>>>>>>> a033a458f66fdfbb1d6ecd7311dc9936ea333cfb
        </div>
    </section>

    <section class="about-section" id="about">
        <div>
            <p class="eyebrow">ABOUT THE PROJECT</p>
            <h2>Technology with a community purpose</h2>
        </div>
<<<<<<< HEAD
        <p>Rwanda Connect is an educational web application project built with PHP, MySQL, HTML, CSS, and JavaScript. It demonstrates a comprehensive citizen services platform with modules for agriculture, employment, education, and healthcare. It is not an official government service.</p>
    </section>

    <section class="cta-section" id="cta">
        <h2>Ready to get connected?</h2>
        <p>Join thousands of citizens accessing services, opportunities, and information through Rwanda Connect.</p>
        <div class="cta-buttons">
            <a class="primary-button" href="/rwanda_connect/auth/register.php">Create Free Account</a>
            <a class="secondary-button" href="/rwanda_connect/auth/login.php">Sign In</a>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
=======
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
>>>>>>> a033a458f66fdfbb1d6ecd7311dc9936ea333cfb
