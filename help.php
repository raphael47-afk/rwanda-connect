<?php
declare(strict_types=1);

/**
 * Rwanda Connect - Help Center
 */

require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

$pageTitle = 'Help Center - Rwanda Connect';
$pageDescription = 'Find answers to common questions and get support for using Rwanda Connect.';

require_once __DIR__ . '/../../includes/header.php';
?>

<main class="page help-center-page">
    <div class="page-container">
        <header class="page-header">
            <h1>Help Center</h1>
            <p>Find answers, learn how to use features, and get support.</p>
        </header>
        
        <!-- Search -->
        <section class="help-search">
            <form action="/rwanda_connect/search.php" method="GET" class="search-form">
                <span class="search-icon">🔍</span>
                <input type="search" name="q" placeholder="Search help articles..." autocomplete="off">
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
        </section>
        
        <!-- Categories -->
        <section class="help-categories">
            <h2>Browse by Category</h2>
            <div class="category-grid">
                <article class="category-card" onclick="location.href='/rwanda_connect/help.php?cat=getting-started'">
                    <span class="category-icon">🚀</span>
                    <h3>Getting Started</h3>
                    <p>Create account, set up profile, navigate the platform</p>
                    <span class="article-count">8 articles</span>
                </article>
                
                <article class="category-card" onclick="location.href='/rwanda_connect/help.php?cat=agriculture'">
                    <span class="category-icon">🌱</span>
                    <h3>Agriculture</h3>
                    <p>Farm management, market prices, crop guides, advisors</p>
                    <span class="article-count">12 articles</span>
                </article>
                
                <article class="category-card" onclick="location.href='/rwanda_connect/help.php?cat=employment'">
                    <span class="category-icon">💼</span>
                    <h3>Employment</h3>
                    <p>Job search, applications, alerts, career resources</p>
                    <span class="article-count">10 articles</span>
                </article>
                
                <article class="category-card" onclick="location.href='/rwanda_connect/help.php?cat=education'">
                    <span class="category-icon">🎓</span>
                    <h3>Education</h3>
                    <p>Scholarships, courses, institutions, applications</p>
                    <span class="article-count">9 articles</span>
                </article>
                
                <article class="category-card" onclick="location.href='/rwanda_connect/help.php?cat=healthcare'">
                    <span class="category-icon">🏥</span>
                    <h3>Healthcare</h3>
                    <p>Facilities, appointments, records, campaigns</p>
                    <span class="article-count">11 articles</span>
                </article>
                
                <article class="category-card" onclick="location.href='/rwanda_connect/help.php?cat=account'">
                    <span class="category-icon">👤</span>
                    <h3>Account & Security</h3>
                    <p>Password, 2FA, privacy, data, account deletion</p>
                    <span class="article-count">7 articles</span>
                </article>
                
                <article class="category-card" onclick="location.href='/rwanda_connect/help.php?cat=technical'">
                    <span class="category-icon">🔧</span>
                    <h3>Technical Issues</h3>
                    <p>Browser support, troubleshooting, bug reports</p>
                    <span class="article-count">5 articles</span>
                </article>
                
                <article class="category-card" onclick="location.href='/rwanda_connect/help.php?cat=billing'">
                    <span class="category-icon">💳</span>
                    <h3>Payments & Billing</h3>
                    <p>Premium features, subscriptions, refunds</p>
                    <span class="article-count">4 articles</span>
                </article>
            </div>
        </section>
        
        <!-- Popular Articles -->
        <section class="popular-articles">
            <h2>Popular Articles</h2>
            <div class="article-list">
                <article class="article-item">
                    <h3><a href="/rwanda_connect/help.php?article=create-account">How to create your Rwanda Connect account</a></h3>
                    <p>Step-by-step guide to registering and verifying your account.</p>
                    <span class="article-meta">📖 5 min read • Updated Jan 2026</span>
                </article>
                
                <article class="article-item">
                    <h3><a href="/rwanda_connect/help.php?article=job-search">How to search and apply for jobs</a></h3>
                    <p>Complete guide to finding, saving, and applying for jobs on the platform.</p>
                    <span class="article-meta">📖 7 min read • Updated Jan 2026</span>
                </article>
                
                <article class="article-item">
                    <h3><a href="/rwanda_connect/help.php?article=scholarship-apply">How to apply for scholarships</a></h3>
                    <p>Tips for writing motivation letters and preparing documents.</p>
                    <span class="article-meta">📖 6 min read • Updated Jan 2026</span>
                </article>
                
                <article class="article-item">
                    <h3><a href="/rwanda_connect/help.php?article=book-appointment">How to book a medical appointment</a></h3>
                    <p>Find facilities, choose providers, and book appointments online.</p>
                    <span class="article-meta">📖 5 min read • Updated Jan 2026</span>
                </article>
                
                <article class="article-item">
                    <h3><a href="/rwanda_connect/help.php?article=2fa-setup">Setting up two-factor authentication</a></h3>
                    <p>Secure your account with 2FA using authenticator apps.</p>
                    <span class="article-meta">📖 4 min read • Updated Jan 2026</span>
                </article>
                
                <article class="article-item">
                    <h3><a href="/rwanda_connect/help.php?article=farm-management">Managing your farm and crops</a></h3>
                    <p>Add farms, track crops, monitor harvests, and check market prices.</p>
                    <span class="article-meta">📖 8 min read • Updated Jan 2026</span>
                </article>
            </div>
            <a href="/rwanda_connect/help.php?all=1" class="btn btn-secondary">View All Articles</a>
        </section>
        
        <!-- Contact Support -->
        <section class="contact-support">
            <h2>Still Need Help?</h2>
            <p>Can't find what you're looking for? Our support team is here to help.</p>
            <div class="support-options">
                <a href="/rwanda_connect/contact.php" class="support-option">
                    <span class="support-icon">📧</span>
                    <div>
                        <h3>Email Support</h3>
                        <p>Send us a message, we'll reply within 24 hours</p>
                    </div>
                </a>
                <a href="/rwanda_connect/contact.php?chat=1" class="support-option">
                    <span class="support-icon">💬</span>
                    <div>
                        <h3>Live Chat</h3>
                        <p>Chat with our team during business hours</p>
                    </div>
                </a>
                <a href="/rwanda_connect/contact.php?callback=1" class="support-option">
                    <span class="support-icon">📞</span>
                    <div>
                        <h3>Request Callback</h3>
                        <p>We'll call you at a convenient time</p>
                    </div>
                </a>
            </div>
        </section>
        
        <!-- Feedback -->
        <section class="feedback-section">
            <h2>Help Us Improve</h2>
            <p>Was this article helpful? Your feedback helps us create better content.</p>
            <div class="feedback-buttons">
                <button class="btn btn-primary" onclick="showFeedback('yes')">Yes, helpful</button>
                <button class="btn btn-secondary" onclick="showFeedback('no')">No, needs improvement</button>
            </div>
        </section>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
function showFeedback(vote) {
    if (vote === 'yes') {
        alert('Thank you! Your feedback helps us improve.');
    } else {
        const reason = prompt('What could we improve?');
        if (reason) {
            alert('Thank you for your feedback: ' + reason);
        }
    }
}
</script>

<style>
/* Help Center Styles */
.help-center-page {
    min-height: 100vh;
}

.page-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 40px 24px;
}

.page-header {
    text-align: center;
    margin-bottom: 48px;
}

.page-header h1 {
    margin: 0 0 12px;
    font-size: clamp(2rem, 4vw, 3rem);
}

.page-header p {
    margin: 0;
    color: var(--muted);
    font-size: 1.1rem;
}

.help-search {
    margin-bottom: 48px;
}

.search-form {
    display: flex;
    gap: 12px;
    max-width: 600px;
    margin: 0 auto;
}

.search-form .search-icon {
    display: flex;
    align-items: center;
    padding: 0 16px;
    font-size: 1.2rem;
}

.search-form input {
    flex: 1;
    padding: 14px 16px;
    border: 1px solid var(--line);
    border-radius: var(--radius-md);
    font-size: 1rem;
}

.search-form input:focus {
    outline: none;
    border-color: var(--green);
    box-shadow: 0 0 0 3px var(--green-light);
}

.help-categories {
    margin-bottom: 48px;
}

.help-categories h2,
.popular-articles h2,
.contact-support h2,
.feedback-section h2 {
    margin: 0 0 24px;
    font-size: 1.5rem;
    font-weight: 700;
}

.category-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
}

.category-card {
    padding: 24px;
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: var(--radius-xl);
    text-decoration: none;
    color: inherit;
    transition: all var(--transition);
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.category-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
    border-color: var(--green);
}

.category-icon {
    font-size: 2.5rem;
}

.category-card h3 {
    margin: 0;
    font-size: 1.2rem;
}

.category-card p {
    margin: 0;
    color: var(--muted);
    font-size: .9rem;
    flex: 1;
}

.article-count {
    font-size: .75rem;
    color: var(--green);
    font-weight: 600;
    background: var(--green-light);
    padding: 4px 10px;
    border-radius: 999px;
    align-self: flex-start;
}

.popular-articles {
    margin-bottom: 48px;
}

.article-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.article-item {
    padding: 20px 24px;
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: var(--radius-lg);
    transition: all var(--transition);
}

.article-item:hover {
    box-shadow: var(--shadow-md);
    border-color: var(--green);
}

.article-item h3 {
    margin: 0 0 8px;
    font-size: 1.1rem;
}

.article-item h3 a {
    color: var(--ink);
}

.article-item h3 a:hover {
    color: var(--green);
}

.article-item p {
    margin: 0 0 8px;
    color: var(--muted);
    font-size: .9rem;
}

.article-meta {
    font-size: .8rem;
    color: var(--muted);
}

.contact-support {
    margin-bottom: 48px;
    padding: 32px;
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: var(--radius-xl);
    text-align: center;
}

.contact-support p {
    margin: 0 0 24px;
    color: var(--muted);
}

.support-options {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-top: 24px;
}

.support-option {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    padding: 24px;
    background: var(--background);
    border: 1px solid var(--line);
    border-radius: var(--radius-lg);
    text-decoration: none;
    color: inherit;
    transition: all var(--transition);
}

.support-option:hover {
    border-color: var(--green);
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
}

.support-icon {
    font-size: 2.5rem;
}

.support-option h3 {
    margin: 0;
    font-size: 1.1rem;
}

.support-option p {
    margin: 0;
    color: var(--muted);
    font-size: .85rem;
}

.feedback-section {
    padding: 32px;
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: var(--radius-xl);
    text-align: center;
}

.feedback-section p {
    margin: 0 0 16px;
    color: var(--muted);
}

.feedback-buttons {
    display: flex;
    justify-content: center;
    gap: 12px;
}

@media (max-width: 768px) {
    .page-container {
        padding: 24px 16px;
    }
    
    .support-options {
        grid-template-columns: 1fr;
    }
    
    .feedback-buttons {
        flex-direction: column;
    }
}
</style>