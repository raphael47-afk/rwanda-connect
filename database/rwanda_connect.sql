-- Rwanda Connect complete database schema
-- Import this file using phpMyAdmin.
CREATE DATABASE IF NOT EXISTS rwanda_connect_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE rwanda_connect_db;

-- Users table (enhanced)
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    phone VARCHAR(20) NULL,
    national_id VARCHAR(16) NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('citizen', 'admin', 'moderator') NOT NULL DEFAULT 'citizen',
    status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    email_verified_at TIMESTAMP NULL,
    last_login_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Service categories
CREATE TABLE IF NOT EXISTS service_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL UNIQUE,
    slug VARCHAR(80) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    icon VARCHAR(50) NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    display_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- User profiles (extended info)
CREATE TABLE IF NOT EXISTS user_profiles (
    user_id INT UNSIGNED PRIMARY KEY,
    date_of_birth DATE NULL,
    gender ENUM('male', 'female', 'other') NULL,
    province VARCHAR(80) NULL,
    district VARCHAR(80) NULL,
    sector VARCHAR(80) NULL,
    cell VARCHAR(80) NULL,
    village VARCHAR(80) NULL,
    education_level VARCHAR(80) NULL,
    occupation VARCHAR(120) NULL,
    skills TEXT NULL,
    bio TEXT NULL,
    profile_image VARCHAR(255) NULL,
    cover_image VARCHAR(255) NULL,
    website VARCHAR(255) NULL,
    linkedin VARCHAR(255) NULL,
    twitter VARCHAR(255) NULL,
    github VARCHAR(255) NULL,
    facebook VARCHAR(255) NULL,
    notification_prefs JSON NULL,
    privacy_settings JSON NULL,
    theme_preference ENUM('light', 'dark', 'system') NOT NULL DEFAULT 'system',
    two_factor_enabled BOOLEAN NOT NULL DEFAULT FALSE,
    two_factor_secret VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ==================== AGRICULTURE MODULE ====================

-- Farming seasons
CREATE TABLE IF NOT EXISTS farming_seasons (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    start_month TINYINT UNSIGNED NOT NULL,
    end_month TINYINT UNSIGNED NOT NULL,
    description TEXT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Crops
CREATE TABLE IF NOT EXISTS crops (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    scientific_name VARCHAR(150) NULL,
    category ENUM('food', 'cash', 'horticulture', 'livestock_feed') NOT NULL,
    growing_period_days INT UNSIGNED NULL,
    season_id INT UNSIGNED NULL,
    description TEXT NULL,
    best_practices TEXT NULL,
    common_diseases TEXT NULL,
    market_price_rwf DECIMAL(10,2) NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (season_id) REFERENCES farming_seasons(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- User farms
CREATE TABLE IF NOT EXISTS user_farms (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    size_hectares DECIMAL(8,2) NOT NULL,
    province VARCHAR(80) NOT NULL,
    district VARCHAR(80) NOT NULL,
    sector VARCHAR(80) NOT NULL,
    cell VARCHAR(80) NULL,
    village VARCHAR(80) NULL,
    soil_type VARCHAR(100) NULL,
    irrigation_type ENUM('rainfed', 'drip', 'sprinkler', 'flood', 'other') NULL,
    gps_latitude DECIMAL(10,8) NULL,
    gps_longitude DECIMAL(11,8) NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Farm crops (what user is growing)
CREATE TABLE IF NOT EXISTS farm_crops (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    farm_id INT UNSIGNED NOT NULL,
    crop_id INT UNSIGNED NOT NULL,
    variety VARCHAR(100) NULL,
    area_hectares DECIMAL(8,2) NOT NULL,
    planting_date DATE NULL,
    expected_harvest_date DATE NULL,
    actual_harvest_date DATE NULL,
    yield_kg DECIMAL(10,2) NULL,
    status ENUM('planted', 'growing', 'harvested', 'failed') NOT NULL DEFAULT 'planted',
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (farm_id) REFERENCES user_farms(id) ON DELETE CASCADE,
    FOREIGN KEY (crop_id) REFERENCES crops(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Market prices
CREATE TABLE IF NOT EXISTS market_prices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    crop_id INT UNSIGNED NOT NULL,
    market_name VARCHAR(150) NOT NULL,
    province VARCHAR(80) NOT NULL,
    district VARCHAR(80) NOT NULL,
    price_per_kg DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) NOT NULL DEFAULT 'RWF',
    recorded_at DATE NOT NULL,
    source VARCHAR(100) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (crop_id) REFERENCES crops(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Agricultural advisors/experts
CREATE TABLE IF NOT EXISTS agri_advisors (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    specialization VARCHAR(255) NULL,
    experience_years INT UNSIGNED NULL,
    certification VARCHAR(255) NULL,
    is_available BOOLEAN NOT NULL DEFAULT TRUE,
    rating DECIMAL(3,2) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ==================== EMPLOYMENT MODULE ====================

-- Job categories
CREATE TABLE IF NOT EXISTS job_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL,
    icon VARCHAR(50) NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Employers/Companies
CREATE TABLE IF NOT EXISTS employers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    company_name VARCHAR(200) NOT NULL,
    company_description TEXT NULL,
    industry VARCHAR(150) NULL,
    website VARCHAR(255) NULL,
    logo VARCHAR(255) NULL,
    province VARCHAR(80) NULL,
    district VARCHAR(80) NULL,
    contact_email VARCHAR(190) NULL,
    contact_phone VARCHAR(20) NULL,
    employee_count_range ENUM('1-10', '11-50', '51-200', '201-500', '500+') NULL,
    is_verified BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Job postings
CREATE TABLE IF NOT EXISTS job_postings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employer_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NULL,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(220) NOT NULL,
    description TEXT NOT NULL,
    requirements TEXT NULL,
    responsibilities TEXT NULL,
    benefits TEXT NULL,
    employment_type ENUM('full_time', 'part_time', 'contract', 'internship', 'volunteer', 'remote') NOT NULL,
    experience_level ENUM('entry', 'junior', 'mid', 'senior', 'lead', 'executive') NULL,
    education_level ENUM('primary', 'secondary', 'diploma', 'bachelor', 'master', 'phd', 'any') NULL,
    salary_min DECIMAL(12,2) NULL,
    salary_max DECIMAL(12,2) NULL,
    salary_currency VARCHAR(3) NOT NULL DEFAULT 'RWF',
    salary_period ENUM('hourly', 'daily', 'monthly', 'yearly') NOT NULL DEFAULT 'monthly',
    province VARCHAR(80) NULL,
    district VARCHAR(80) NULL,
    is_remote BOOLEAN NOT NULL DEFAULT FALSE,
    application_deadline DATE NULL,
    status ENUM('draft', 'published', 'closed', 'filled', 'expired') NOT NULL DEFAULT 'draft',
    views_count INT UNSIGNED NOT NULL DEFAULT 0,
    applications_count INT UNSIGNED NOT NULL DEFAULT 0,
    featured_until DATE NULL,
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employer_id) REFERENCES employers(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES job_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Job applications
CREATE TABLE IF NOT EXISTS job_applications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    cover_letter TEXT NULL,
    resume_path VARCHAR(255) NULL,
    status ENUM('pending', 'reviewed', 'shortlisted', 'interviewed', 'rejected', 'hired') NOT NULL DEFAULT 'pending',
    applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES job_postings(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_application (job_id, user_id)
) ENGINE=InnoDB;

-- User saved jobs
CREATE TABLE IF NOT EXISTS user_saved_jobs (
    user_id INT UNSIGNED NOT NULL,
    job_id INT UNSIGNED NOT NULL,
    saved_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, job_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (job_id) REFERENCES job_postings(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Job alerts
CREATE TABLE IF NOT EXISTS job_alerts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    keywords VARCHAR(500) NULL,
    category_id INT UNSIGNED NULL,
    province VARCHAR(80) NULL,
    employment_type ENUM('full_time', 'part_time', 'contract', 'internship', 'volunteer', 'remote', 'any') NOT NULL DEFAULT 'any',
    min_salary DECIMAL(12,2) NULL,
    frequency ENUM('daily', 'weekly', 'instant') NOT NULL DEFAULT 'daily',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    last_sent_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES job_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ==================== EDUCATION MODULE ====================

-- Institutions
CREATE TABLE IF NOT EXISTS institutions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(220) NOT NULL UNIQUE,
    type ENUM('university', 'college', 'tvet', 'secondary', 'primary', 'online', 'other') NOT NULL,
    description TEXT NULL,
    website VARCHAR(255) NULL,
    logo VARCHAR(255) NULL,
    province VARCHAR(80) NULL,
    district VARCHAR(80) NULL,
    contact_email VARCHAR(190) NULL,
    contact_phone VARCHAR(20) NULL,
    is_accredited BOOLEAN NOT NULL DEFAULT TRUE,
    established_year YEAR NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Courses/Programs
CREATE TABLE IF NOT EXISTS courses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    institution_id INT UNSIGNED NOT NULL,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(220) NOT NULL,
    code VARCHAR(50) NULL,
    description TEXT NULL,
    level ENUM('certificate', 'diploma', 'bachelor', 'master', 'phd', 'short_course', 'professional') NOT NULL,
    field_of_study VARCHAR(150) NULL,
    duration_months INT UNSIGNED NULL,
    tuition_fee_rwf DECIMAL(12,2) NULL,
    currency VARCHAR(3) NOT NULL DEFAULT 'RWF',
    entry_requirements TEXT NULL,
    career_prospects TEXT NULL,
    study_mode ENUM('full_time', 'part_time', 'distance', 'online', 'hybrid') NOT NULL DEFAULT 'full_time',
    language VARCHAR(50) NOT NULL DEFAULT 'English',
    intake_months VARCHAR(100) NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (institution_id) REFERENCES institutions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Scholarships
CREATE TABLE IF NOT EXISTS scholarships (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(220) NOT NULL UNIQUE,
    provider VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    eligibility_criteria TEXT NOT NULL,
    benefits TEXT NULL,
    application_url VARCHAR(500) NULL,
    contact_email VARCHAR(190) NULL,
    target_level ENUM('secondary', 'diploma', 'bachelor', 'master', 'phd', 'any') NOT NULL DEFAULT 'any',
    target_field VARCHAR(255) NULL,
    target_country VARCHAR(100) NULL,
    coverage ENUM('full', 'partial', 'tuition_only', 'living_only', 'other') NOT NULL,
    amount_rwf DECIMAL(12,2) NULL,
    deadline DATE NOT NULL,
    start_date DATE NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    views_count INT UNSIGNED NOT NULL DEFAULT 0,
    applications_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- User education profile
CREATE TABLE IF NOT EXISTS user_education (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    institution_id INT UNSIGNED NULL,
    course_id INT UNSIGNED NULL,
    qualification VARCHAR(200) NOT NULL,
    field_of_study VARCHAR(150) NULL,
    start_year YEAR NULL,
    end_year YEAR NULL,
    is_current BOOLEAN NOT NULL DEFAULT FALSE,
    grade VARCHAR(50) NULL,
    certificate_path VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (institution_id) REFERENCES institutions(id) ON DELETE SET NULL,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Scholarship applications
CREATE TABLE IF NOT EXISTS scholarship_applications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    scholarship_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    motivation_letter TEXT NULL,
    documents_path VARCHAR(500) NULL,
    status ENUM('pending', 'submitted', 'under_review', 'shortlisted', 'awarded', 'rejected') NOT NULL DEFAULT 'pending',
    applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (scholarship_id) REFERENCES scholarships(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_scholarship_app (scholarship_id, user_id)
) ENGINE=InnoDB;

-- User work experience
CREATE TABLE IF NOT EXISTS user_work_experience (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    company_name VARCHAR(200) NOT NULL,
    position VARCHAR(200) NOT NULL,
    location VARCHAR(200) NULL,
    start_date DATE NULL,
    end_date DATE NULL,
    is_current BOOLEAN NOT NULL DEFAULT FALSE,
    description TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ==================== HEALTHCARE MODULE ====================

-- Health facilities
CREATE TABLE IF NOT EXISTS health_facilities (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(220) NOT NULL UNIQUE,
    type ENUM('hospital', 'health_center', 'clinic', 'pharmacy', 'laboratory', 'dental', 'optical', 'mental_health', 'other') NOT NULL,
    ownership ENUM('public', 'private', 'ngo', 'faith_based', 'community') NOT NULL,
    description TEXT NULL,
    province VARCHAR(80) NOT NULL,
    district VARCHAR(80) NOT NULL,
    sector VARCHAR(80) NULL,
    cell VARCHAR(80) NULL,
    village VARCHAR(80) NULL,
    gps_latitude DECIMAL(10,8) NULL,
    gps_longitude DECIMAL(11,8) NULL,
    phone VARCHAR(20) NULL,
    email VARCHAR(190) NULL,
    website VARCHAR(255) NULL,
    opening_hours VARCHAR(255) NULL,
    emergency_services BOOLEAN NOT NULL DEFAULT FALSE,
    ambulance_service BOOLEAN NOT NULL DEFAULT FALSE,
    lab_services BOOLEAN NOT NULL DEFAULT FALSE,
    pharmacy BOOLEAN NOT NULL DEFAULT FALSE,
    maternity_services BOOLEAN NOT NULL DEFAULT FALSE,
    dental_services BOOLEAN NOT NULL DEFAULT FALSE,
    mental_health_services BOOLEAN NOT NULL DEFAULT FALSE,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    rating DECIMAL(3,2) NULL,
    review_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Health services offered
CREATE TABLE IF NOT EXISTS health_services (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    facility_id INT UNSIGNED NOT NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT NULL,
    category VARCHAR(100) NULL,
    cost_rwf DECIMAL(10,2) NULL,
    insurance_accepted BOOLEAN NOT NULL DEFAULT TRUE,
    requires_appointment BOOLEAN NOT NULL DEFAULT FALSE,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (facility_id) REFERENCES health_facilities(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Doctors/Healthcare providers
CREATE TABLE IF NOT EXISTS healthcare_providers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    facility_id INT UNSIGNED NULL,
    license_number VARCHAR(100) NOT NULL UNIQUE,
    specialization VARCHAR(200) NULL,
    qualification VARCHAR(300) NULL,
    experience_years INT UNSIGNED NULL,
    consultation_fee_rwf DECIMAL(10,2) NULL,
    languages VARCHAR(255) NULL,
    bio TEXT NULL,
    profile_image VARCHAR(255) NULL,
    is_available BOOLEAN NOT NULL DEFAULT TRUE,
    rating DECIMAL(3,2) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (facility_id) REFERENCES health_facilities(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Appointments
CREATE TABLE IF NOT EXISTS appointments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    facility_id INT UNSIGNED NULL,
    provider_id INT UNSIGNED NULL,
    service_id INT UNSIGNED NULL,
    appointment_date DATETIME NOT NULL,
    duration_minutes INT UNSIGNED NOT NULL DEFAULT 30,
    reason TEXT NULL,
    status ENUM('scheduled', 'confirmed', 'in_progress', 'completed', 'cancelled', 'no_show') NOT NULL DEFAULT 'scheduled',
    notes TEXT NULL,
    cancellation_reason TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (facility_id) REFERENCES health_facilities(id) ON DELETE SET NULL,
    FOREIGN KEY (provider_id) REFERENCES healthcare_providers(id) ON DELETE SET NULL,
    FOREIGN KEY (service_id) REFERENCES health_services(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Health records (basic)
CREATE TABLE IF NOT EXISTS health_records (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    record_type ENUM('vaccination', 'checkup', 'lab_result', 'prescription', 'diagnosis', 'allergy', 'chronic_condition', 'surgery', 'other') NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NULL,
    facility_id INT UNSIGNED NULL,
    provider_id INT UNSIGNED NULL,
    record_date DATE NOT NULL,
    attachment_path VARCHAR(255) NULL,
    is_private BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (facility_id) REFERENCES health_facilities(id) ON DELETE SET NULL,
    FOREIGN KEY (provider_id) REFERENCES healthcare_providers(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Health campaigns/alerts
CREATE TABLE IF NOT EXISTS health_campaigns (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(220) NOT NULL UNIQUE,
    description TEXT NOT NULL,
    campaign_type ENUM('vaccination', 'screening', 'awareness', 'outbreak', 'wellness', 'other') NOT NULL,
    target_group VARCHAR(255) NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    location VARCHAR(500) NULL,
    contact_info VARCHAR(500) NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Farming tips
CREATE TABLE IF NOT EXISTS farming_tips (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    category VARCHAR(100) NULL,
    season_id INT UNSIGNED NULL,
    crop_id INT UNSIGNED NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (season_id) REFERENCES farming_seasons(id) ON DELETE SET NULL,
    FOREIGN KEY (crop_id) REFERENCES crops(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Insert farming tips
INSERT IGNORE INTO farming_tips (title, content, category, season_id) VALUES
('Soil Testing Before Planting', 'Test your soil pH and nutrient levels before each season to determine the right fertilizer type and amount.', 'Soil Management', 1),
('Proper Crop Rotation', 'Rotate crops each season to prevent soil depletion and reduce pest buildup. Legumes like beans fix nitrogen for the next crop.', 'Crop Management', 1),
('Integrated Pest Management', 'Use a combination of biological, cultural, and chemical methods to control pests. Monitor fields regularly and use traps.', 'Pest Control', 1),
('Mulching for Moisture Retention', 'Apply organic mulch around plants to retain soil moisture, suppress weeds, and improve soil structure as it decomposes.', 'Water Management', 2),
('Timely Harvesting', 'Harvest crops at the right maturity stage to maximize yield and quality. Delayed harvesting can lead to losses.', 'Harvest Management', 1),
('Post-Harvest Handling', 'Clean, sort, and store produce properly to reduce post-harvest losses. Use appropriate storage facilities for each crop.', 'Post-Harvest', 1);

-- ==================== COMMUNITY/COMMON TABLES ====================

-- Notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    type VARCHAR(100) NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    related_type VARCHAR(50) NULL,
    related_id INT UNSIGNED NULL,
    action_url VARCHAR(500) NULL,
    is_read BOOLEAN NOT NULL DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- User activity log
CREATE TABLE IF NOT EXISTS activity_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Settings
CREATE TABLE IF NOT EXISTS settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(100) NOT NULL UNIQUE,
    `value` TEXT NULL,
    `group` VARCHAR(50) NOT NULL DEFAULT 'general',
    description VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insert default data
INSERT IGNORE INTO service_categories (name, slug, description, icon, display_order) VALUES
('Agriculture', 'agriculture', 'Farming information and market opportunities', '🌱', 1),
('Employment', 'employment', 'Job announcements and career resources', '💼', 2),
('Education', 'education', 'Scholarships, learning and training information', '🎓', 3),
('Healthcare', 'healthcare', 'Health facility and service information', '🏥', 4),
('Community', 'community', 'Community issue reporting', '🤝', 5),
('Environment', 'environment', 'Environmental and sanitation reporting', '♻️', 6),
('Business', 'business', 'Entrepreneurship and economic resources', '📈', 7);

INSERT IGNORE INTO farming_seasons (name, start_month, end_month, description) VALUES
('Season A (September - January)', 9, 1, 'Main rainy season for most crops'),
('Season B (February - June)', 2, 6, 'Secondary rainy season'),
('Season C (July - August)', 7, 8, 'Dry season - irrigation farming');

INSERT IGNORE INTO crops (name, scientific_name, category, growing_period_days, season_id, description, market_price_rwf) VALUES
('Maize', 'Zea mays', 'food', 120, 1, 'Staple food crop, widely grown across Rwanda', 350.00),
('Beans', 'Phaseolus vulgaris', 'food', 90, 1, 'Important protein source, intercropped with maize', 600.00),
('Rice', 'Oryza sativa', 'food', 130, 1, 'Grown in marshlands', 800.00),
('Coffee', 'Coffea arabica', 'cash', 1095, 1, 'Major export crop', 2500.00),
('Tea', 'Camellia sinensis', 'cash', 1095, 1, 'Major export crop', 800.00),
('Banana', 'Musa spp.', 'food', 365, 1, 'Staple food and cash crop', 400.00),
('Cassava', 'Manihot esculenta', 'food', 270, 1, 'Drought-resistant staple', 250.00),
('Sweet Potato', 'Ipomoea batatas', 'food', 120, 1, 'Fast-growing food security crop', 300.00),
('Irish Potato', 'Solanum tuberosum', 'food', 120, 1, 'High-value food crop', 450.00),
('Tomatoes', 'Solanum lycopersicum', 'horticulture', 100, 1, 'High-value vegetable', 600.00);

INSERT IGNORE INTO job_categories (name, slug, description, icon) VALUES
('Information Technology', 'it', 'Software development, IT support, data analysis', '💻'),
('Agriculture & Agribusiness', 'agriculture', 'Farming, agronomy, veterinary, agribusiness', '🌾'),
('Healthcare', 'healthcare', 'Medical, nursing, pharmacy, public health', '🏥'),
('Education', 'education', 'Teaching, training, administration', '🎓'),
('Engineering', 'engineering', 'Civil, mechanical, electrical, software', '⚙️'),
('Finance & Accounting', 'finance', 'Banking, accounting, auditing, finance', '💰'),
('Sales & Marketing', 'sales', 'Sales, marketing, business development', '📈'),
('Administration', 'admin', 'Office administration, HR, management', '📋'),
('Construction', 'construction', 'Building, architecture, surveying', '🏗️'),
('Hospitality & Tourism', 'hospitality', 'Hotels, restaurants, tourism, travel', '🏨');

INSERT IGNORE INTO institutions (name, slug, type, province, district, contact_email, is_accredited) VALUES
('University of Rwanda', 'university-of-rwanda', 'university', 'Kigali City', 'Nyarugenge', 'info@ur.ac.rw', TRUE),
('Rwanda Polytechnic', 'rwanda-polytechnic', 'tvet', 'Kigali City', 'Kicukiro', 'info@rp.ac.rw', TRUE),
('African Leadership University', 'african-leadership-university', 'university', 'Kigali City', 'Gasabo', 'admissions@alueducation.com', TRUE),
('Kepler', 'kepler', 'university', 'Kigali City', 'Kicukiro', 'info@kepler.org', TRUE),
('Akilah Institute', 'akilah-institute', 'college', 'Kigali City', 'Kicukiro', 'info@akilahinstitute.org', TRUE);

INSERT IGNORE INTO health_facilities (name, slug, type, ownership, province, district, phone, emergency_services, ambulance_service, lab_services, pharmacy, maternity_services) VALUES
('Kigali University Teaching Hospital', 'kigali-university-teaching-hospital', 'hospital', 'public', 'Kigali City', 'Nyarugenge', '+250788123456', TRUE, TRUE, TRUE, TRUE, TRUE),
('King Faisal Hospital', 'king-faisal-hospital', 'hospital', 'private', 'Kigali City', 'Gasabo', '+250788123457', TRUE, TRUE, TRUE, TRUE, TRUE),
('Rwanda Military Hospital', 'rwanda-military-hospital', 'hospital', 'public', 'Kigali City', 'Kicukiro', '+250788123458', TRUE, TRUE, TRUE, TRUE, TRUE),
('Butare University Teaching Hospital', 'butare-university-teaching-hospital', 'hospital', 'public', 'Southern Province', 'Huye', '+250788123459', TRUE, TRUE, TRUE, TRUE, TRUE),
('Kibagabaga Hospital', 'kibagabaga-hospital', 'hospital', 'public', 'Kigali City', 'Gasabo', '+250788123460', TRUE, TRUE, TRUE, TRUE, TRUE);

INSERT IGNORE INTO settings (`key`, `value`, `group`, description) VALUES
('site_name', 'Rwanda Connect', 'general', 'Website name'),
('site_tagline', 'Connecting citizens to opportunities and services', 'general', 'Website tagline'),
('registration_enabled', '1', 'auth', 'Allow new user registration'),
('max_file_upload_mb', '10', 'general', 'Maximum file upload size in MB'),
('default_language', 'en', 'general', 'Default language');

-- Remember tokens for "remember me" functionality
CREATE TABLE IF NOT EXISTS remember_tokens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB;

-- Password reset tokens
CREATE TABLE IF NOT EXISTS password_resets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB;

-- User data exports
CREATE TABLE IF NOT EXISTS user_exports (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token CHAR(64) NOT NULL,
    file_path VARCHAR(500) NULL,
    expires_at DATETIME NOT NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB;

-- Create indexes for performance
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_status ON users(status);
CREATE INDEX idx_job_postings_status ON job_postings(status);
CREATE INDEX idx_job_postings_category ON job_postings(category_id);
CREATE INDEX idx_job_postings_location ON job_postings(province, district);
CREATE INDEX idx_job_applications_user ON job_applications(user_id);
CREATE INDEX idx_job_applications_job ON job_applications(job_id);
CREATE INDEX idx_courses_institution ON courses(institution_id);
CREATE INDEX idx_courses_level ON courses(level);
CREATE INDEX idx_scholarships_deadline ON scholarships(deadline);
CREATE INDEX idx_scholarships_active ON scholarships(is_active);
CREATE INDEX idx_health_facilities_type ON health_facilities(type);
CREATE INDEX idx_health_facilities_location ON health_facilities(province, district);
CREATE INDEX idx_appointments_user ON appointments(user_id);
CREATE INDEX idx_appointments_date ON appointments(appointment_date);
CREATE INDEX idx_notifications_user ON notifications(user_id);
CREATE INDEX idx_notifications_read ON notifications(is_read);
CREATE INDEX idx_activity_log_user ON activity_log(user_id);
CREATE INDEX idx_farm_crops_farm ON farm_crops(farm_id);
CREATE INDEX idx_market_prices_crop_date ON market_prices(crop_id, recorded_at);