<<<<<<< HEAD
RWANDA CONNECT - COMPREHENSIVE CITIZEN SERVICES PLATFORM
==========================================================

WHAT IS INCLUDED
----------------
- index.php: Enhanced homepage with module overview
- config/database.php: Secure PDO database connection with prepared statements
- database/rwanda_connect.sql: Complete database schema (32 tables)
- includes/functions.php: Reusable utility functions (auth, validation, formatting, etc.)
- includes/auth.php: Complete authentication system (register, login, logout, password reset)
- includes/header.php: Reusable header with responsive navigation
- includes/footer.php: Reusable footer with flash messages
- auth/: Citizen registration, login, logout, password reset
- dashboard/: Personal dashboard with stats, notifications, activity log
- modules/agriculture/: Farm management, crops, market prices, advisors
- modules/employment/: Job search, applications, saved jobs, alerts
- modules/education/: Scholarships, courses, institutions, education profile
- modules/healthcare/: Facilities, appointments, health records, campaigns
- api/: AJAX endpoints for dynamic features
- assets/css/style.css: Comprehensive responsive styling (2000+ lines)
- assets/js/script.js: Interactive JavaScript (modals, validation, AJAX)

FEATURES
--------
🌱 AGRICULTURE MODULE
  - Farm registration with GPS, soil type, irrigation
  - Crop tracking with planting/harvest dates, progress
  - Real-time market prices by crop, market, location
  - Season calendar with planting guides
  - Expert advisor directory with ratings
  - Farming tips and best practices

💼 EMPLOYMENT MODULE
  - Advanced job search with filters (category, location, type, salary)
  - Job application tracking with status updates
  - Save/unsave jobs for comparison
  - Email job alerts with customizable criteria
  - Company profiles and reviews
  - Career resources

🎓 EDUCATION MODULE
  - Scholarship database with filtering (level, field, country, coverage)
  - Scholarship application with motivation letter & documents
  - Course/program finder from accredited institutions
  - Institution directory with accreditation status
  - Personal education profile management
  - Application tracking

🏥 HEALTHCARE MODULE
  - Health facility finder (hospitals, clinics, pharmacies, labs)
  - Appointment booking with provider selection
  - Personal health records (vaccinations, checkups, prescriptions)
  - Health campaigns (vaccinations, screenings, awareness)
  - Doctor/specialist directory with ratings
  - Emergency information

👤 CITIZEN DASHBOARD
  - Unified statistics across all modules
  - Recent activity feed
  - Notification center with read/unread status
  - Profile management (personal info, location, skills)
  - Account settings (password, notifications, privacy, data export)
  - Activity log for security monitoring

🔐 AUTHENTICATION & SECURITY
  - Secure registration with email, phone, national ID validation
  - Login with "remember me" (30-day tokens)
  - Password reset via secure tokens
  - CSRF protection on all forms
  - Password strength meter
  - Role-based access control (citizen, admin, moderator)
  - Activity logging for audit trail

REQUIREMENTS
------------
- XAMPP with Apache, PHP 8.0+, MySQL 5.7+
- A web browser
- A code editor (Visual Studio Code recommended)

INSTALLATION
------------
1. Extract/copy the "rwanda_connect" folder into:
   C:\xampp\htdocs\
2. Open XAMPP Control Panel
3. Start Apache and MySQL
4. Open phpMyAdmin: http://localhost/phpmyadmin
5. Select the Import tab
6. Choose database/rwanda_connect.sql from this project folder
7. Click Import / Go
8. Open the homepage: http://localhost/rwanda_connect/

DATABASE CONNECTION
-------------------
Default config in config/database.php uses:
=======
RWANDA CONNECT - STARTER PACKAGE
================================

WHAT IS INCLUDED
----------------
- index.php: the first project homepage
- config/database.php: reusable PDO database connection
- database/rwanda_connect.sql: starter database and tables
- assets/css/style.css: responsive page styling
- assets/js/script.js: JavaScript starter file

REQUIREMENTS
------------
- XAMPP with Apache, PHP and MySQL
- A web browser
- A code editor (Visual Studio Code is recommended)

INSTALLATION
------------
1. Extract the ZIP archive.
2. Copy the folder named "rwanda_connect" into:
   C:\xampp\htdocs\
3. Open XAMPP Control Panel.
4. Start Apache and MySQL.
5. Open phpMyAdmin:
   http://localhost/phpmyadmin
6. Select the Import tab.
7. Choose database/rwanda_connect.sql from this project folder.
8. Click Import / Go.
9. Open the homepage:
   http://localhost/rwanda_connect/

DATABASE CONNECTION
-------------------
The default config in config/database.php uses:
>>>>>>> a033a458f66fdfbb1d6ecd7311dc9936ea333cfb
- Host: 127.0.0.1
- Database: rwanda_connect_db
- Username: root
- Password: blank

<<<<<<< HEAD
These are common XAMPP defaults. If your MySQL uses a password
or different username, edit config/database.php.

DATABASE SCHEMA
---------------
32 tables including:
- users, user_profiles (extended citizen info)
- service_categories (7 modules)
- Agriculture: farming_seasons, crops, user_farms, farm_crops, market_prices, agri_advisors, farming_tips
- Employment: job_categories, employers, job_postings, job_applications, user_saved_jobs, job_alerts
- Education: institutions, courses, scholarships, user_education, scholarship_applications
- Healthcare: health_facilities, health_services, healthcare_providers, appointments, health_records, health_campaigns
- System: notifications, activity_log, settings, remember_tokens, password_resets, user_exports

IMPORTANT NOTES
---------------
⚠️  This is an educational student project prototype
⚠️  NOT an official Government of Rwanda service
⚠️  Do not deploy publicly or collect real personal data without:
    - HTTPS/SSL certificates
    - Production-grade security audit
    - Data protection compliance (GDPR/Local laws)
    - Proper backup & disaster recovery
    - Rate limiting & DDoS protection
    - Secure file upload handling
    - Content Security Policy headers

DEVELOPMENT
-----------
- All code follows PSR-12 coding standards
- Uses prepared statements for all database queries
- Reusable components via includes/ directory
- Modular architecture for easy extension
- Responsive design (mobile-first)
- Accessible (ARIA labels, semantic HTML)
- No external dependencies (vanilla PHP/JS/CSS)

LICENSE
-------
Educational use only. Student project prototype.
=======
These are common XAMPP defaults. If your MySQL account uses a password
or a different username, edit config/database.php.

IMPORTANT
---------
The homepage is a starter demonstration. Registration, login, admin tools,
service listings and reporting workflows have not been implemented yet.
Do not publish this starter online or use it to collect real citizens'
personal information. We will add and test security features before
implementing accounts or collecting data.

This is an independent educational student project, not an official
Government of Rwanda website or service.
>>>>>>> a033a458f66fdfbb1d6ecd7311dc9936ea333cfb
