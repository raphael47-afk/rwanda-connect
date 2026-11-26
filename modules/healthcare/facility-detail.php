<?php
declare(strict_types=1);

/**
 * Rwanda Connect - Healthcare: Facility Detail
 */

require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';

requireLogin();

$facilityId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$db = getDb();

if (!$facilityId) {
    header('Location: /rwanda_connect/modules/healthcare/facilities.php');
    exit;
}

$stmt = $db->prepare('SELECT * FROM health_facilities WHERE id = ? AND is_active = TRUE');
$stmt->execute([$facilityId]);
$facility = $stmt->fetch();

if (!$facility) {
    header('Location: /rwanda_connect/modules/healthcare/facilities.php');
    exit;
}

// Get services
$stmt = $db->prepare('SELECT * FROM health_services WHERE facility_id = ? AND is_active = TRUE ORDER BY name');
$stmt->execute([$facilityId]);
$services = $stmt->fetchAll();

// Get providers
$stmt = $db->prepare('
    SELECT hp.*, u.full_name, u.profile_image
    FROM healthcare_providers hp
    JOIN users u ON hp.user_id = u.id
    WHERE hp.facility_id = ? AND hp.is_available = TRUE AND u.status = "active"
    ORDER BY hp.rating DESC
');
$stmt->execute([$facilityId]);
$providers = $stmt->fetchAll();

$pageTitle = e($facility['name']) . ' - Healthcare - Rwanda Connect';
$pageDescription = 'View details for ' . e($facility['name']) . ' including services, doctors, and contact information.';

require_once __DIR__ . '/../../../includes/header.php';
?>

<main class="module-page healthcare-page facility-detail-page">
    <div class="module-container">
        <header class="module-header">
            <div class="module-brand">
                <a href="/rwanda_connect/modules/healthcare/facilities.php" class="back-link">← Facilities</a>
                <div>
                    <h1><?= e($facility['name']) ?></h1>
                    <p><?= e(ucfirst(str_replace('_', ' ', $facility['type']))) ?> • <?= e($facility['district'] . ', ' . $facility['province']) ?></p>
                </div>
            </div>
        </header>

        <!-- Facility Header Card -->
        <div class="facility-detail-header">
            <div class="facility-main-info">
                <div class="facility-type-large">
                    <?= match($facility['type']) {
                        'hospital' => '🏥',
                        'health_center' => '🏥',
                        'clinic' => '🏥',
                        'pharmacy' => '💊',
                        'laboratory' => '🧪',
                        'dental' => '🦷',
                        'optical' => '👓',
                        'mental_health' => '🧠',
                        default => '🏥'
                    } ?>
                </div>
                <div>
                    <div class="facility-badges">
                        <span class="facility-type-badge"><?= e(ucfirst(str_replace('_', ' ', $facility['type']))) ?></span>
                        <span class="ownership-badge"><?= e(ucfirst(str_replace('_', ' ', $facility['ownership']))) ?></span>
                        <?php if ($facility['is_accredited'] ?? false): ?>
                            <span class="accredited-badge">✓ Accredited</span>
                        <?php endif; ?>
                    </div>
                    <div class="facility-rating">
                        <?php if ($facility['rating']): ?>
                            <span class="rating">⭐ <?= $facility['rating'] ?></span>
                            <span class="review-count">(<?= $facility['review_count'] ?> reviews)</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="facility-services-summary">
                <?php if ($facility['emergency_services']): ?><span class="service-badge emergency">🚨 Emergency</span><?php endif; ?>
                <?php if ($facility['ambulance_service']): ?><span class="service-badge ambulance">🚑 Ambulance</span><?php endif; ?>
                <?php if ($facility['lab_services']): ?><span class="service-badge lab">🧪 Lab</span><?php endif; ?>
                <?php if ($facility['pharmacy']): ?><span class="service-badge pharmacy">💊 Pharmacy</span><?php endif; ?>
                <?php if ($facility['maternity_services']): ?><span class="service-badge maternity">🤱 Maternity</span><?php endif; ?>
                <?php if ($facility['dental_services']): ?><span class="service-badge dental">🦷 Dental</span><?php endif; ?>
                <?php if ($facility['mental_health_services']): ?><span class="service-badge mental">🧠 Mental Health</span><?php endif; ?>
            </div>
        </div>

        <div class="facility-detail-grid">
            <!-- Main Content -->
            <div class="facility-main">
                <!-- Contact & Location -->
                <section class="detail-section">
                    <h2>Contact & Location</h2>
                    <div class="contact-grid">
                        <div class="contact-item">
                            <span class="contact-icon">📍</span>
                            <div>
                                <strong>Address</strong>
                                <p><?= e($facility['province']) ?><?= $facility['district'] ? ', ' . e($facility['district']) : '' ?><?= $facility['sector'] ? ', ' . e($facility['sector']) : '' ?><?= $facility['cell'] ? ', ' . e($facility['cell']) : '' ?><?= $facility['village'] ? ', ' . e($facility['village']) : '' ?></p>
                            </div>
                        </div>
                        <?php if ($facility['phone']): ?>
                            <div class="contact-item">
                                <span class="contact-icon">📞</span>
                                <div>
                                    <strong>Phone</strong>
                                    <p><a href="tel:<?= e($facility['phone']) ?>"><?= e($facility['phone']) ?></a></p>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if ($facility['email']): ?>
                            <div class="contact-item">
                                <span class="contact-icon">✉️</span>
                                <div>
                                    <strong>Email</strong>
                                    <p><a href="mailto:<?= e($facility['email']) ?>"><?= e($facility['email']) ?></a></p>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if ($facility['website']): ?>
                            <div class="contact-item">
                                <span class="contact-icon">🌐</span>
                                <div>
                                    <strong>Website</strong>
                                    <p><a href="<?= e($facility['website']) ?>" target="_blank" rel="noopener"><?= e($facility['website']) ?></a></p>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if ($facility['opening_hours']): ?>
                            <div class="contact-item">
                                <span class="contact-icon">🕐</span>
                                <div>
                                    <strong>Opening Hours</strong>
                                    <p><?= e($facility['opening_hours']) ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if ($facility['gps_latitude'] && $facility['gps_longitude']): ?>
                            <div class="contact-item">
                                <span class="contact-icon">📡</span>
                                <div>
                                    <strong>GPS Coordinates</strong>
                                    <p><?= $facility['gps_latitude'] ?>, <?= $facility['gps_longitude'] ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- Description -->
                <?php if ($facility['description']): ?>
                    <section class="detail-section">
                        <h2>About</h2>
                        <p><?= nl2br(e($facility['description'])) ?></p>
                    </section>
                <?php endif; ?>

                <!-- Services -->
                <section class="detail-section">
                    <h2>Services Offered</h2>
                    <?php if (!empty($services)): ?>
                        <div class="services-table-wrapper">
                            <table class="services-table">
                                <thead>
                                    <tr>
                                        <th>Service</th>
                                        <th>Category</th>
                                        <th>Cost</th>
                                        <th>Insurance</th>
                                        <th>Appointment</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($services as $service): ?>
                                        <tr>
                                            <td><strong><?= e($service['name']) ?></strong></td>
                                            <td><?= e($service['category'] ?? 'General') ?></td>
                                            <td><?= $service['cost_rwf'] ? formatCurrency($service['cost_rwf']) : 'Contact for pricing' ?></td>
                                            <td><?= $service['insurance_accepted'] ? '✓ Yes' : '✗ No' ?></td>
                                            <td><?= $service['requires_appointment'] ? '✓ Required' : 'Walk-in OK' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="empty-state-inline">No services listed yet.</p>
                    <?php endif; ?>
                </section>

                <!-- Providers -->
                <?php if (!empty($providers)): ?>
                    <section class="detail-section">
                        <h2>Healthcare Providers</h2>
                        <div class="providers-grid">
                            <?php foreach ($providers as $provider): ?>
                                <article class="provider-card">
                                    <div class="provider-avatar">
                                        <?php if (!empty($provider['profile_image'])): ?>
                                            <img src="<?= e($provider['profile_image']) ?>" alt="">
                                        <?php else: ?>
                                            <span><?= e(strtoupper(substr($provider['full_name'], 0, 1))) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="provider-info">
                                        <h4>Dr. <?= e($provider['full_name']) ?></h4>
                                        <p class="provider-specialty"><?= e($provider['specialization'] ?? 'General Practice') ?></p>
                                        <div class="provider-meta">
                                            <?php if ($provider['experience_years']): ?>
                                                <span>📅 <?= $provider['experience_years'] ?> yrs experience</span>
                                            <?php endif; ?>
                                            <?php if ($provider['consultation_fee_rwf']): ?>
                                                <span>💰 Consultation: <?= formatCurrency($provider['consultation_fee_rwf']) ?></span>
                                            <?php endif; ?>
                                            <?php if ($provider['rating']): ?>
                                                <span>⭐ <?= $provider['rating'] ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
            </div>

            <!-- Sidebar Actions -->
            <aside class="facility-sidebar">
                <div class="sidebar-card">
                    <h3>Quick Actions</h3>
                    <div class="action-buttons">
                        <?php if ($facility['phone']): ?>
                            <a href="tel:<?= e($facility['phone']) ?>" class="btn btn-primary btn-block">📞 Call Now</a>
                        <?php endif; ?>
                        <a href="/rwanda_connect/modules/healthcare/appointments.php?action=book&facility=<?= $facilityId ?>" class="btn btn-secondary btn-block">📅 Book Appointment</a>
                        <a href="https://maps.google.com/?q=<?= urlencode($facility['gps_latitude'] . ',' . $facility['gps_longitude']) ?>" target="_blank" rel="noopener" class="btn btn-outline btn-block">🗺️ Get Directions</a>
                        <?php if ($facility['website']): ?>
                            <a href="<?= e($facility['website']) ?>" target="_blank" rel="noopener" class="btn btn-outline btn-block">🌐 Visit Website</a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="sidebar-card">
                    <h3>Facility Details</h3>
                    <dl class="detail-list">
                        <dt>Type</dt>
                        <dd><?= e(ucfirst(str_replace('_', ' ', $facility['type']))) ?></dd>
                        <dt>Ownership</dt>
                        <dd><?= e(ucfirst(str_replace('_', ' ', $facility['ownership']))) ?></dd>
                        <dt>Province</dt>
                        <dd><?= e($facility['province']) ?></dd>
                        <dt>District</dt>
                        <dd><?= e($facility['district']) ?></dd>
                        <?php if ($facility['emergency_services']): ?>
                            <dt>Emergency</dt>
                            <dd>✓ Available 24/7</dd>
                        <?php endif; ?>
                        <?php if ($facility['ambulance_service']): ?>
                            <dt>Ambulance</dt>
                            <dd>✓ Available</dd>
                        <?php endif; ?>
                    </dl>
                </div>
            </aside>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>