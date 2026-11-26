<?php
declare(strict_types=1);

/**
 * Rwanda Connect - Services Overview Page
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

checkRememberToken();

$pageTitle = 'Services - Rwanda Connect';
$pageDescription = 'Explore all available services: Agriculture, Employment, Education, Healthcare, and more.';

require_once __DIR__ . '/includes/header.php';
?>

<main>
    <section class="hero" style="padding: 60px max(8vw, 24px);">
        <div class="hero-content" style="max-width: 800px; margin: 0 auto; text-align: center;">
            <p class="eyebrow">OUR SERVICES</p>
            <h1>Services for Every Aspect of Life</h1>
            <p class="hero-copy">Comprehensive modules designed to empower Rwandan citizens with information, opportunities, and essential services.</p>
        </div>
    </section>

    <section class="section">
        <div class="service-grid">
            <article class="service-card" onclick="location.href='/rwanda_connect/modules/agriculture/'">
                <span>🌱</span>
                <h3>Agriculture</h3>
                <p>Complete farm management, real-time market prices, crop guides, season calendars, and expert advisory services.</p>
                <ul class="service-features">
                    <li>🏞️ Farm & crop tracking</li>
                    <li>💰 Market price monitoring</li>
                    <li>📅 Season calendars</li>
                    <li>👨‍🌾 Expert advisors</li>
                    <li>📚 Crop guides & best practices</li>
                    <li>🌾 Farm portfolio management</li>
                </ul>
                <a href="/rwanda_connect/modules/agriculture/" class="btn btn-primary" style="margin-top: 16px; display: inline-block;">Explore Agriculture →</a>
            </article>

            <article class="service-card" onclick="location.href='/rwanda_connect/modules/employment/'">
                <span>💼</span>
                <h3>Employment</h3>
                <p>Job search with advanced filters, application tracking, saved jobs, personalized alerts, and career resources.</p>
                <ul class="service-features">
                    <li>🔍 Advanced job search</li>
                    <li>📄 Application management</li>
                    <li>⭐ Save & compare jobs</li>
                    <li>🔔 Email job alerts</li>
                    <li>📚 Career resources</li>
                    <li>🏢 Company profiles</li>
                </ul>
                <a href="/rwanda_connect/modules/employment/" class="btn btn-primary" style="margin-top: 16px; display: inline-block;">Explore Employment →</a>
            </article>

            <article class="service-card" onclick="location.href='/rwanda_connect/modules/education/'">
                <span>🎓</span>
                <h3>Education</h3>
                <p>Scholarship database, course finder, institution directory, education profile management, and learning resources.</p>
                <ul class="service-features">
                    <li>💰 Scholarship finder</li>
                    <li>📚 Course & program search</li>
                    <li>🏫 Institution directory</li>
                    <li>👤 Education profile</li>
                    <li>📖 Learning resources</li>
                    <li>📄 Application tracking</li>
                </ul>
                <a href="/rwanda_connect/modules/education/" class="btn btn-primary" style="margin-top: 16px; display: inline-block;">Explore Education →</a>
            </article>

            <article class="service-card" onclick="location.href='/rwanda_connect/modules/healthcare/'">
                <span>🏥</span>
                <h3>Healthcare</h3>
                <p>Health facility finder, appointment booking, personal health records, health campaigns, and emergency information.</p>
                <ul class="service-features">
                    <li>🏥 Facility search & filter</li>
                    <li>📅 Appointment booking</li>
                    <li>📋 Health records management</li>
                    <li>💉 Health campaigns</li>
                    <li>👨‍⚕️ Doctor finder</li>
                    <li>🚨 Emergency information</li>
                </ul>
                <a href="/rwanda_connect/modules/healthcare/" class="btn btn-primary" style="margin-top: 16px; display: inline-block;">Explore Healthcare →</a>
            </article>

            <article class="service-card">
                <span>🤝</span>
                <h3>Community</h3>
                <p>Report local issues, participate in community decisions, and track resolution progress.</p>
                <ul class="service-features">
                    <li>📍 Issue reporting</li>
                    <li>🗳️ Community voting</li>
                    <li>📊 Progress tracking</li>
                    <li>🔔 Status notifications</li>
                </ul>
                <p style="margin-top: 16px; color: var(--muted); font-size: .9rem;"><em>Coming soon</em></p>
            </article>

            <article class="service-card">
                <span>♻️</span>
                <h3>Environment</h3>
                <p>Environmental reporting, sanitation monitoring, and green initiative tracking.</p>
                <ul class="service-features">
                    <li>🗑️ Waste reporting</li>
                    <li>🌳 Green initiatives</li>
                    <li>📈 Impact tracking</li>
                    <li>📍 Location-based alerts</li>
                </ul>
                <p style="margin-top: 16px; color: var(--muted); font-size: .9rem;"><em>Coming soon</em></p>
            </article>

            <article class="service-card">
                <span>📈</span>
                <h3>Business</h3>
                <p>Entrepreneurship resources, business registration guides, funding opportunities, and economic data.</p>
                <ul class="service-features">
                    <li>💼 Business resources</li>
                    <li>📋 Registration guides</li>
                    <li>💰 Funding opportunities</li>
                    <li>📊 Market insights</li>
                </ul>
                <p style="margin-top: 16px; color: var(--muted); font-size: .9rem;"><em>Coming soon</em></p>
            </article>
        </div>
    </section>

    <section class="section features-section">
        <div class="section-heading">
            <p class="eyebrow">WHY USE OUR SERVICES</p>
            <h2>Integrated Platform Benefits</h2>
            <p>All services work together seamlessly through your personal dashboard.</p>
        </div>
        <div class="features-grid">
            <article class="feature-card">
                <span class="feature-icon">🔐</span>
                <h3>Single Sign-On</h3>
                <p>Access all modules with one secure account.</p>
            </article>
            <article class="feature-card">
                <span class="feature-icon">🔔</span>
                <h3>Unified Notifications</h3>
                <p>Get alerts from all services in one place.</p>
            </article>
            <article class="feature-card">
                <span class="feature-icon">📊</span>
                <h3>Cross-Module Insights</h3>
                <p>See connections between your activities.</p>
            </article>
            <article class="feature-card">
                <span class="feature-icon">👤</span>
                <h3>Personalized Experience</h3>
                <p>Recommendations based on your profile.</p>
            </article>
            <article class="feature-card">
                <span class="feature-icon">📱</span>
                <h3>Mobile Optimized</h3>
                <p>Full functionality on any device.</p>
            </article>
            <article class="feature-card">
                <span class="feature-icon">🇷🇼</span>
                <h3>Locally Relevant</h3>
                <p>Built for Rwanda with local context.</p>
            </article>
        </div>
    </section>

    <section class="cta-section">
        <h2>Ready to Access All Services?</h2>
        <p>Create your free account to unlock personalized features across all modules.</p>
        <div class="cta-buttons">
            <a class="primary-button" href="/rwanda_connect/auth/register.php">Create Free Account</a>
            <a class="secondary-button" href="/rwanda_connect/auth/login.php">Sign In</a>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>