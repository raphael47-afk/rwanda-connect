<?php
declare(strict_types=1);

/**
 * Rwanda Connect - About Page
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'About Us - Rwanda Connect';
$pageDescription = 'Learn about Rwanda Connect, our mission, team, and impact.';

require_once __DIR__ . '/includes/header.php';
?>

<main class="page about-page">
    <div class="page-container">
        <!-- Hero -->
        <section class="about-hero">
            <div class="hero-content">
                <span class="eyebrow">ABOUT RWANDA CONNECT</span>
                <h1>Connecting Citizens to Opportunities</h1>
                <p class="hero-description">We're building Rwanda's most comprehensive citizen services platform - making information, opportunities, and services accessible to everyone.</            </div>
            <div class="hero-stats">
                <div class="stat">
                    <span class="stat-number">50K+</span>
                    <span class="stat-label">Active Citizens</span>
                </div>
                <div class="stat">
                    <span class="stat-number">10K+</span>
                    <span class="stat-label">Jobs Posted</span>
                </div>
                <div class="stat">
                    <span class="stat-number">500+</span>
                    <span class="stat-label">Scholarships</span>
                </div>
                <div class="stat">
                    <span class="stat-number">1K+</span>
                    <span class="stat-label">Health Facilities</span>
                </div>
            </div>
        </section>
        
        <!-- Mission -->
        <section class="mission-section">
            <div class="mission-content">
                <div class="mission-text">
                    <h2>Our Mission</h2>
                    <p>Rwanda Connect was born from a simple idea: every citizen deserves easy access to the opportunities and services that can transform their lives. Whether it's finding a job, applying for a scholarship, booking a medical appointment, or getting the best price for crops - we believe information should be accessible, transparent, and actionable.</p>
                    <p>We're not just a website. We're a bridge between citizens and the services they need. We work with government agencies, educational institutions, healthcare providers, employers, and agricultural experts to bring everything together in one place.</p>
                </div>
                <div class="mission-visual">
                    <div class="mission-icon">🇷🇼</div>
                </div>
            </div>
        </section>
        
        <!-- Values -->
        <section class="values-section">
            <h2>Our Values</h2>
            <div class="values-grid">
                <article class="value-card">
                    <span class="value-icon">🤝</span>
                    <h3>Inclusive</h3>
                    <p>We design for everyone - from urban professionals to rural farmers, ensuring no citizen is left behind.</                </article>
                
                <article class="value-card">
                    <span class="value-icon">🔒</span>
                    <h3>Secure & Private</h3>
                    <p>Your data belongs to you. We use industry-leading security and give you full control over your information.
                </article>
                
                <article class="value-card">
                    <span class="value-icon">🚀</span>
                    <h3>Innovative</h3>
                    <p>We continuously evolve, adopting new technologies to serve citizens better and stay ahead of their needs.
                </article>
                
                <article class="value-card">
                    <span class="value-icon">🌍</span>
                    <h3>Locally Relevant</h3>
                    <p>Built for Rwanda, by people who understand Rwanda. Available in Kinyarwanda, English, and French.
                </article>
                
                <article class="value-card">
                    <span class="value-icon">♿</span>
                    <h3>Accessible</h3>
                    <p>Designed for all abilities - screen reader compatible, keyboard navigable, and works on low-bandwidth connections.
                </article>
                
                <article class="value-card">
                    <span class="value-icon">🤝</span>
                    <h3>Collaborative</h3>
                    <p>We partner with government, NGOs, and private sector to amplify impact and reach more citizens.
                </article>
            </div>
        </section>
        
        <!-- Impact -->
        <section class="impact-section">
            <h2>Our Impact</h2>
            <div class="impact-grid">
                <article class="impact-card">
                    <div class="impact-icon">👥</div>
                    <h3>50,000+</h3>
                    <p>Registered citizens using the platform monthly</p>
                </article>
                
                <article class="impact-card">
                    <div class="impact-icon">💼</div>
                    <h3>10,000+</h3>
                    <p>Job applications submitted through the platform</p>
                </article>
                
                <article class="impact-card">
                    <div class="impact-icon">🎓</div>
                    <h3>2,500+</h3>
                    <p>Scholarship applications processed</p>
                </article>
                
                <article class="impact-card">
                    <div class="impact-icon">🏥</div>
                    <h3>15,000+</h3>
                    <p>Medical appointments booked online</p>
                </article>
                
                <article class="impact-card">
                    <div class="impact-icon">🌱</div>
                    <h3>5,000+</h3>
                    <p>Farms registered with crop tracking</p>
                </article>
                
                <article class="impact-card">
                    <div class="impact-icon">💰</div>
                    <h3>50M+ RWF</h3>
                    <p>In scholarship value connected to students</p>
                </article>
            </div>
        </section>
        
        <!-- Team -->
        <section class="team-section">
            <h2>The Team Behind Rwanda Connect</h2>
            <p class="section-subtitle">A diverse team of developers, designers, and domain experts passionate about digital inclusion.</p>
            <div class="team-grid">
                <article class="team-member">
                    <div class="member-avatar">👨‍💻</div>
                    <h3>Jean-Baptiste N.</h3>
                    <p class="member-role">Founder & CEO</p>
                    <p class="member-bio">Full-stack developer with 10+ years experience in civic tech across East Africa.</p>
                </article>
                
                <article class="team-member">
                    <div class="member-avatar">👩‍💻</div>
                    <h3>Grace U.</h3>
                    <p class="member-role">Lead Developer</p>
                    <p class="member-bio">Backend specialist passionate about scalable systems and data privacy.</p>
                </article>
                
                <article class="team-member">
                    <div class="member-avatar">👨‍🎨</div>
                    <h3>Eric M.</h3>
                    <p class="member-role">UX Designer</p>
                    <p class="member-bio">Human-centered designer focused on accessibility and inclusive design.</p>
                </article>
                
                <article class="team-member">
                    <div class="member-avatar">👩‍🌾</div>
                    <h3>Diane K.</h3>
                    <p class="member-role">Agriculture Specialist</p>
                    <p class="member-bio">Agronomist with 15 years experience in Rwanda's agricultural sector.</p>
                </article>
                
                <article class="team-member">
                    <div class="member-avatar">👨‍⚕️</div>
                    <h3>Dr. Pierre H.</h3>
                    <p class="member-role">Health Advisor</p>
                    <p class="member-bio">Public health physician ensuring healthcare module accuracy and relevance.</p>
                </article>
                
                <article class="team-member">
                    <div class="member-avatar">👩‍🎓</div>
                    <h3>Alice T.</h3>
                    <p class="member-role">Education Lead</p>
                    <p class="member-bio">Former university administrator with expertise in scholarship programs.</p>
                </article>
            </div>
        </section>
        
        <!-- Partners -->
        <section class="partners-section">
            <h2>Our Partners</h2>
            <p class="section-subtitle">We collaborate with leading organizations to deliver the best services to citizens.</p>
            <div class="partners-grid">
                <div class="partner-logo">🏛️ Government of Rwanda</div>
                <div class="partner-logo">🏥 Ministry of Health</div>
                <div class="partner-logo">🌱 Ministry of Agriculture</div>
                <div class="partner-logo">🎓 Ministry of Education</div>
                <div class="partner-logo">💼 Ministry of Labour</div>
                <div class="partner-logo">🏥 Rwanda Biomedical Centre</div>
                <div class="partner-logo">🌱 Rwanda Agriculture Board</div>
                <div class="partner-logo">🎓 Higher Education Council</div>
            </div>
        </section>
        
        <!-- CTA -->
        <section class="cta-section">
            <h2>Join Us in Building Rwanda's Digital Future</h2>
            <p>Whether you're a citizen looking for opportunities, a partner organization, or a developer wanting to contribute - there's a place for you.</p>
            <div class="cta-buttons">
                <a href="/rwanda_connect/auth/register.php" class="btn btn-primary">Create Free Account</a>
                <a href="/rwanda_connect/contact.php" class="btn btn-secondary">Partner With Us</a>
            </div>
        </section>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<style>
/* About Page Styles */
.about-page {
    min-height: 100vh;
}

.page-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 40px 24px;
}

.about-hero {
    text-align: center;
    padding: 60px 24px;
    background: linear-gradient(135deg, var(--green-light) 0%, var(--background) 100%);
    border-radius: var(--radius-xl);
    margin-bottom: 60px;
}

.eyebrow {
    display: inline-block;
    color: var(--green);
    font-size: .76rem;
    font-weight: 800;
    letter-spacing: .13em;
    text-transform: uppercase;
    margin-bottom: 16px;
}

.about-hero h1 {
    margin: 0 0 16px;
    font-size: clamp(2.5rem, 5vw, 4rem);
}

.hero-description {
    max-width: 700px;
    margin: 0 auto 40px;
    color: var(--muted);
    font-size: 1.15rem;
}

.hero-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 24px;
    margin-top: 40px;
}

.stat {
    text-align: center;
    padding: 24px;
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: var(--radius-lg);
}

.stat-number {
    display: block;
    font-size: 2.5rem;
    font-weight: 800;
    color: var(--green);
}

.stat-label {
    display: block;
    font-size: .9rem;
    color: var(--muted);
    margin-top: 4px;
}

.mission-section {
    margin-bottom: 60px;
}

.mission-content {
    display: grid;
    grid-template-columns: 1.5fr 1fr;
    gap: 48px;
    align-items: center;
}

.mission-text h2 {
    margin: 0 0 16px;
    font-size: 2rem;
}

.mission-text p {
    margin: 0 0 16px;
    color: var(--ink);
    line-height: 1.7;
    font-size: 1.1rem;
}

.mission-visual {
    text-align: center;
}

.mission-icon {
    font-size: 8rem;
    opacity: 0.1;
}

.values-section {
    margin-bottom: 60px;
}

.section-subtitle {
    text-align: center;
    color: var(--muted);
    margin: 0 auto 40px;
    max-width: 600px;
}

.values-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 24px;
}

.value-card {
    padding: 32px;
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: var(--radius-xl);
    text-align: center;
    transition: all var(--transition);
}

.value-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
    border-color: var(--green);
}

.value-icon {
    font-size: 3rem;
    display: block;
    margin-bottom: 16px;
}

.value-card h3 {
    margin: 0 0 12px;
    font-size: 1.3rem;
}

.value-card p {
    margin: 0;
    color: var(--muted);
    line-height: 1.6;
}

.impact-section {
    margin-bottom: 60px;
}

.impact-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 24px;
}

.impact-card {
    padding: 32px 24px;
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: var(--radius-xl);
    text-align: center;
    transition: all var(--transition);
}

.impact-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
    border-color: var(--green);
}

.impact-icon {
    font-size: 3rem;
    display: block;
    margin-bottom: 16px;
}

.impact-card h3 {
    margin: 0 0 8px;
    font-size: 2.5rem;
    font-weight: 800;
    color: var(--green);
}

.impact-card p {
    margin: 0;
    color: var(--muted);
    font-size: .9rem;
}

.team-section {
    margin-bottom: 60px;
}

.team-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 24px;
}

.team-member {
    padding: 32px 24px;
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: var(--radius-xl);
    text-align: center;
    transition: all var(--transition);
}

.team-member:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
    border-color: var(--green);
}

.member-avatar {
    font-size: 4rem;
    margin-bottom: 16px;
}

.team-member h3 {
    margin: 0 0 4px;
    font-size: 1.2rem;
}

.member-role {
    margin: 0 0 8px;
    color: var(--green);
    font-weight: 600;
    font-size: .9rem;
}

.member-bio {
    margin: 0;
    color: var(--muted);
    font-size: .9rem;
    line-height: 1.5;
}

.partners-section {
    margin-bottom: 60px;
}

.partners-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 24px;
}

.partner-logo {
    padding: 24px;
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: var(--radius-lg);
    text-align: center;
    font-weight: 600;
    color: var(--muted);
    font-size: .95rem;
    transition: all var(--transition);
}

.partner-logo:hover {
    border-color: var(--green);
    color: var(--green);
    background: var(--green-light);
}

.cta-section {
    text-align: center;
    padding: 60px 24px;
    background: linear-gradient(135deg, var(--green) 0%, var(--green-dark) 100%);
    border-radius: var(--radius-xl);
    color: white;
}

.cta-section h2 {
    margin: 0 0 16px;
    font-size: clamp(1.8rem, 3.5vw, 2.5rem);
}

.cta-section p {
    margin: 0 0 32px;
    font-size: 1.1rem;
    opacity: 0.9;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

.cta-buttons {
    display: flex;
    gap: 16px;
    justify-content: center;
    flex-wrap: wrap;
}

.cta-section .btn-primary {
    background: white;
    color: var(--green);
}

.cta-section .btn-primary:hover {
    background: var(--background);
}

.cta-section .btn-secondary {
    background: transparent;
    color: white;
    border-color: white;
}

.cta-section .btn-secondary:hover {
    background: rgba(255,255,255,0.1);
}

@media (max-width: 768px) {
    .mission-content {
        grid-template-columns: 1fr;
        gap: 24px;
    }
    
    .mission-icon {
        font-size: 5rem;
    }
    
    .hero-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .impact-card h3 {
        font-size: 2rem;
    }
}
</style>