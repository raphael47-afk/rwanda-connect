<?php
declare(strict_types=1);

/**
 * Rwanda Connect - Healthcare Module Home
 */

require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';

requireLogin();

$userId = getCurrentUserId();
$db = getDb();

// Get user stats
$stmt = $db->prepare('SELECT COUNT(*) as count FROM appointments WHERE user_id = ? AND appointment_date >= CURDATE() AND status IN ("scheduled", "confirmed")');
$stmt->execute([$userId]);
$upcomingAppointments = (int)$stmt->fetchColumn();

$stmt = $db->prepare('SELECT COUNT(*) as count FROM health_records WHERE user_id = ?');
$stmt->execute([$userId]);
$healthRecordsCount = (int)$stmt->fetchColumn();

$stmt = $db->prepare('SELECT COUNT(*) as count FROM appointments WHERE user_id = ? AND status = "completed"');
$stmt->execute([$userId]);
$completedAppointments = (int)$stmt->fetchColumn();

// Get upcoming appointments
$stmt = $db->prepare('
    SELECT a.*, hf.name as facility_name, hf.type as facility_type, hp.specialization, u.full_name as provider_name
    FROM appointments a
    JOIN health_facilities hf ON a.facility_id = hf.id
    LEFT JOIN healthcare_providers hp ON a.provider_id = hp.id
    LEFT JOIN users u ON hp.user_id = u.id
    WHERE a.user_id = ? AND a.appointment_date >= CURDATE() AND a.status IN ("scheduled", "confirmed")
    ORDER BY a.appointment_date ASC
    LIMIT 4
');
$stmt->execute([$userId]);
$appointments = $stmt->fetchAll();

// Get health records
$stmt = $db->prepare('
    SELECT * FROM health_records 
    WHERE user_id = ?
    ORDER BY record_date DESC
    LIMIT 4
');
$stmt->execute([$userId]);
$healthRecords = $stmt->fetchAll();

// Get health campaigns
$stmt = $db->query('
    SELECT * FROM health_campaigns 
    WHERE is_active = TRUE AND (end_date IS NULL OR end_date >= CURDATE())
    ORDER BY start_date ASC
    LIMIT 4
');
$campaigns = $stmt->fetchAll();

// Get facilities by type
$stmt = $db->query('
    SELECT type, COUNT(*) as count 
    FROM health_facilities 
    WHERE is_active = TRUE 
    GROUP BY type
');
$facilityTypes = $stmt->fetchAll();

$pageTitle = 'Healthcare - Rwanda Connect';
$pageDescription = 'Find health facilities, book appointments, manage health records, and stay informed about health campaigns.';

require_once __DIR__ . '/../../../includes/header.php';
?>

<main class="module-page healthcare-page">
    <div class="module-container">
        <!-- Module Header -->
        <header class="module-header">
            <div class="module-brand">
                <span class="module-icon">🏥</span>
                <div>
                    <h1>Healthcare</h1>
                    <p>Health facilities, appointments, records, and wellness</p>
                </div>
            </div>
            <div class="module-actions">
                <a href="/rwanda_connect/modules/healthcare/facilities.php" class="btn btn-primary">🏥 Find Facilities</a>
                <a href="/rwanda_connect/modules/healthcare/appointments.php" class="btn btn-secondary">📅 Book Appointment</a>
            </div>
        </header>
        
        <!-- Stats Overview -->
        <section class="module-stats" aria-label="Healthcare Statistics">
            <div class="stat-card">
                <div class="stat-icon">📅</div>
                <div class="stat-content">
                    <span class="stat-value"><?= $upcomingAppointments ?></span>
                    <span class="stat-label">Upcoming Appointments</span>
                </div>
                <a href="/rwanda_connect/modules/healthcare/appointments.php" class="stat-link">View →</a>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📋</div>
                <div class="stat-content">
                    <span class="stat-value"><?= $healthRecordsCount ?></span>
                    <span class="stat-label">Health Records</span>
                </div>
                <a href="/rwanda_connect/modules/healthcare/records.php" class="stat-link">Manage →</a>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-content">
                    <span class="stat-value"><?= $completedAppointments ?></span>
                    <span class="stat-label">Completed Visits</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🏥</div>
                <div class="stat-content">
                    <span class="stat-value"><?= array_sum(array_column($facilityTypes, 'count')) ?></span>
                    <span class="stat-label">Facilities</span>
                </div>
                <a href="/rwanda_connect/modules/healthcare/facilities.php" class="stat-link">Find →</a>
            </div>
        </section>
        
        <!-- Quick Actions -->
        <section class="module-section actions-section">
            <h2>Quick Actions</h2>
            <div class="actions-grid">
                <a href="/rwanda_connect/modules/healthcare/facilities.php" class="action-card">
                    <span class="action-icon">🏥</span>
                    <h3>Find Facilities</h3>
                    <p>Search hospitals, clinics, pharmacies</p>
                </a>
                <a href="/rwanda_connect/modules/healthcare/appointments.php" class="action-card">
                    <span class="action-icon">📅</span>
                    <h3>Book Appointment</h3>
                    <p>Schedule doctor visits</p>
                </a>
                <a href="/rwanda_connect/modules/healthcare/records.php" class="action-card">
                    <span class="action-icon">📋</span>
                    <h3>Health Records</h3>
                    <p>Manage your medical history</p>
                </a>
                <a href="/rwanda_connect/modules/healthcare/campaigns.php" class="action-card">
                    <span class="action-icon">💉</span>
                    <h3>Health Campaigns</h3>
                    <p>Vaccinations, screenings, awareness</p>
                </a>
                <a href="/rwanda_connect/modules/healthcare/providers.php" class="action-card">
                    <span class="action-icon">👨‍⚕️</span>
                    <h3>Find Doctors</h3>
                    <p>Search healthcare providers</p>
                </a>
                <a href="/rwanda_connect/modules/healthcare/emergency.php" class="action-card">
                    <span class="action-icon">🚨</span>
                    <h3>Emergency Info</h3>
                    <p>Emergency numbers & locations</p>
                </a>
            </div>
        </section>
        
        <!-- Upcoming Appointments -->
        <section class="module-section">
            <div class="section-header">
                <h2>Upcoming Appointments</h2>
                <a href="/rwanda_connect/modules/healthcare/appointments.php" class="section-link">View All →</a>
            </div>
            
            <?php if (!empty($appointments)): ?>
                <div class="appointments-list">
                    <?php foreach ($appointments as $appt): ?>
                        <article class="appointment-card">
                            <div class="appointment-date">
                                <span class="day"><?= (new DateTime($appt['appointment_date']))->format('d') ?></span>
                                <span class="month"><?= (new DateTime($appt['appointment_date']))->format('M') ?></span>
                                <span class="time"><?= (new DateTime($appt['appointment_date']))->format('H:i') ?></span>
                            </div>
                            <div class="appointment-info">
                                <h4><?= e($appt['facility_name']) ?></h4>
                                <p class="appointment-facility-type"><?= e(ucfirst(str_replace('_', ' ', $appt['facility_type']))) ?></p>
                                <?php if ($appt['provider_name']): ?>
                                    <p class="appointment-provider">👨‍⚕️ Dr. <?= e($appt['provider_name']) ?><?= $appt['specialization'] ? ' - ' . e($appt['specialization']) : '' ?></p>
                                <?php endif; ?>
                                <p class="appointment-reason"><?= e($appt['reason'] ?? 'General consultation') ?></p>
                            </div>
                            <div class="appointment-status">
                                <span class="status-badge status-<?= $appt['status'] ?>">
                                    <?= e(ucfirst($appt['status'])) ?>
                                </span>
                                <a href="/rwanda_connect/modules/healthcare/appointment-detail.php?id=<?= $appt['id'] ?>" class="btn btn-sm btn-outline">Details</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state-inline">
                    <span class="empty-icon">📅</span>
                    <p>No upcoming appointments</p>
                    <a href="/rwanda_connect/modules/healthcare/appointments.php" class="btn btn-primary">Book Appointment</a>
                </div>
            <?php endif; ?>
        </section>
        
        <!-- Health Records & Campaigns -->
        <div class="module-grid">
            <!-- Recent Health Records -->
            <section class="module-section">
                <div class="section-header">
                    <h2>Recent Health Records</h2>
                    <a href="/rwanda_connect/modules/healthcare/records.php" class="section-link">View All →</a>
                </div>
                
                <?php if (!empty($healthRecords)): ?>
                    <div class="records-list">
                        <?php foreach ($healthRecords as $record): ?>
                            <article class="record-item">
                                <div class="record-icon"><?= match($record['record_type']) {
                                    'vaccination' => '💉',
                                    'checkup' => '🩺',
                                    'lab_result' => '🧪',
                                    'prescription' => '💊',
                                    'diagnosis' => '📋',
                                    'allergy' => '⚠️',
                                    'chronic_condition' => '🏥',
                                    'surgery' => '🏥',
                                    default => '📄'
                                } ?></div>
                                <div class="record-info">
                                    <h4><?= e($record['title']) ?></h4>
                                    <p class="record-type"><?= e(ucfirst(str_replace('_', ' ', $record['record_type']))) ?></p>
                                    <p class="record-date">📅 <?= formatDate($record['record_date']) ?></p>
                                </div>
                                <?php if ($record['facility_id']): ?>
                                    <span class="record-facility">🏥 Facility</span>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state-inline">
                        <span class="empty-icon">📋</span>
                        <p>No health records yet</p>
                        <a href="/rwanda_connect/modules/healthcare/records.php" class="btn btn-sm btn-primary">Add Record</a>
                    </div>
                <?php endif; ?>
            </section>
            
            <!-- Health Campaigns -->
            <section class="module-section">
                <div class="section-header">
                    <h2>Health Campaigns</h2>
                    <a href="/rwanda_connect/modules/healthcare/campaigns.php" class="section-link">View All →</a>
                </div>
                
                <?php if (!empty($campaigns)): ?>
                    <div class="campaigns-list">
                        <?php foreach ($campaigns as $campaign): 
                            $daysUntilStart = (new DateTime($campaign['start_date']))->diff(new DateTime())->days;
                            $isOngoing = $daysUntilStart <= 0 && (!$campaign['end_date'] || (new DateTime($campaign['end_date'])) >= new DateTime());
                        ?>
                            <article class="campaign-card">
                                <div class="campaign-type"><?= match($campaign['campaign_type']) {
                                    'vaccination' => '💉',
                                    'screening' => '🔍',
                                    'awareness' => '📢',
                                    'outbreak' => '🚨',
                                    'wellness' => '🧘',
                                    default => '🏥'
                                } ?></div>
                                <div class="campaign-info">
                                    <h4><?= e($campaign['title']) ?></h4>
                                    <p class="campaign-type-label"><?= e(ucfirst($campaign['campaign_type'])) ?></p>
                                    <p class="campaign-dates">
                                        📅 <?= $isOngoing ? 'Ongoing' : 'Starts in ' . $daysUntilStart . ' days' ?>
                                        <?= $campaign['end_date'] ? ' - Ends: ' . formatDate($campaign['end_date']) : '' ?>
                                    </p>
                                    <?php if ($campaign['location']): ?>
                                        <p class="campaign-location">📍 <?= e($campaign['location']) ?></p>
                                    <?php endif; ?>
                                </div>
                                <a href="/rwanda_connect/modules/healthcare/campaign-detail.php?id=<?= $campaign['id'] ?>" class="btn btn-sm btn-outline">Details</a>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state-inline">
                        <span class="empty-icon">💉</span>
                        <p>No active campaigns</p>
                    </div>
                <?php endif; ?>
            </section>
        </div>
        
        <!-- Facility Types -->
        <section class="module-section">
            <h2>Facilities by Type</h2>
            <div class="facility-types-grid">
                <?php foreach ($facilityTypes as $type): ?>
                    <a href="/rwanda_connect/modules/healthcare/facilities.php?type=<?= $type['type'] ?>" class="facility-type-card">
                        <span class="facility-type-icon"><?= match($type['type']) {
                            'hospital' => '🏥',
                            'health_center' => '🏥',
                            'clinic' => '🏥',
                            'pharmacy' => '💊',
                            'laboratory' => '🧪',
                            'dental' => '🦷',
                            'optical' => '👓',
                            'mental_health' => '🧠',
                            default => '🏥'
                        } ?></span>
                        <h3><?= e(ucfirst(str_replace('_', ' ', $type['type']))) ?></h3>
                        <p><?= $type['count'] ?> facilities</p>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</main>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>