<?php
declare(strict_types=1);

/**
 * Rwanda Connect - Footer Component
 * Reusable footer
 */
$siteName = getSetting('site_name', 'Rwanda Connect');
$currentYear = date('Y');
?>
<footer class="site-footer">
    <div class="footer-container">
        <div class="footer-grid">
            <div class="footer-brand">
                <a class="brand" href="/rwanda_connect/index.php">
                    <span class="brand-mark">RC</span>
                    <span class="brand-text"><?= e($siteName) ?></span>
                </a>
                <p class="footer-tagline">Connecting citizens to opportunities and services across Rwanda.</p>
                <div class="footer-social">
                    <a href="#" aria-label="Twitter" class="social-link">𝕏</a>
                    <a href="#" aria-label="Facebook" class="social-link">𝖿</a>
                    <a href="#" aria-label="LinkedIn" class="social-link">in</a>
                    <a href="#" aria-label="YouTube" class="social-link">▶</a>
                </div>
            </div>
            
            <nav class="footer-nav" aria-label="Footer navigation">
                <div class="footer-nav-column">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="/rwanda_connect/index.php">Home</a></li>
                        <li><a href="/rwanda_connect/services.php">Services</a></li>
                        <li><a href="/rwanda_connect/about.php">About Us</a></li>
                        <li><a href="/rwanda_connect/contact.php">Contact</a></li>
                    </ul>
                </div>
                
                <div class="footer-nav-column">
                    <h4>Modules</h4>
                    <ul>
                        <li><a href="/rwanda_connect/modules/agriculture/">🌱 Agriculture</a></li>
                        <li><a href="/rwanda_connect/modules/employment/">💼 Employment</a></li>
                        <li><a href="/rwanda_connect/modules/education/">🎓 Education</a></li>
                        <li><a href="/rwanda_connect/modules/healthcare/">🏥 Healthcare</a></li>
                    </ul>
                </div>
                
                <div class="footer-nav-column">
                    <h4>Citizen Services</h4>
                    <ul>
                        <li><a href="/rwanda_connect/auth/register.php">Register</a></li>
                        <li><a href="/rwanda_connect/auth/login.php">Login</a></li>
                        <li><a href="/rwanda_connect/dashboard/">Dashboard</a></li>
                        <li><a href="/rwanda_connect/faq.php">FAQ</a></li>
                    </ul>
                </div>
                
                <div class="footer-nav-column">
                    <h4>Legal</h4>
                    <ul>
                        <li><a href="/rwanda_connect/privacy.php">Privacy Policy</a></li>
                        <li><a href="/rwanda_connect/terms.php">Terms of Service</a></li>
                        <li><a href="/rwanda_connect/accessibility.php">Accessibility</a></li>
                    </ul>
                </div>
            </nav>
            
            <div class="footer-contact">
                <h4>Contact Us</h4>
                <address>
                    <p>📍 Kigali, Rwanda</p>
                    <p>📞 +250 788 123 456</p>
                    <p>✉️ info@rwandaconnect.rw</p>
                </address>
                <div class="footer-newsletter">
                    <h4>Stay Updated</h4>
                    <form action="/rwanda_connect/newsletter.php" method="POST" class="newsletter-form">
                        <input type="email" name="email" placeholder="Your email" required aria-label="Email for newsletter">
                        <button type="submit" class="btn btn-sm btn-primary">Subscribe</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom">
            <div class="footer-bottom-left">
                <p>&copy; <?= $currentYear ?> <?= e($siteName) ?>. All rights reserved.</p>
                <p class="footer-disclaimer">Student project prototype — Not an official government service.</p>
            </div>
            <div class="footer-bottom-right">
                <p>Built with PHP, MySQL, HTML, CSS & JavaScript</p>
            </div>
        </div>
    </div>
</footer>

<!-- Flash Messages Container -->
<div id="flash-container" class="flash-container" aria-live="polite"></div>

<!-- Back to Top Button -->
<button class="back-to-top" aria-label="Back to top" hidden>↑</button>

<script src="/rwanda_connect/assets/js/script.js"></script>
</body>
</html>