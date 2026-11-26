<?php
declare(strict_types=1);

/**
 * Rwanda Connect - Healthcare: Appointments
 */

require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';

requireLogin();

$userId = getCurrentUserId();
$db = getDb();

$errors = [];
$success = false;
$action = $_GET['action'] ?? 'list';
$appointmentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$tab = $_GET['tab'] ?? 'upcoming'; // upcoming, past

// Handle appointment booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'book') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors['general'] = 'Invalid request.';
    } else {
        $facilityId = (int)($_POST['facility_id'] ?? 0);
        $providerId = $_POST['provider_id'] ? (int)$_POST['provider_id'] : null;
        $serviceId = $_POST['service_id'] ? (int)$_POST['service_id'] : null;
        $appointmentDate = $_POST['appointment_date'] ?? '';
        $duration = (int)($_POST['duration'] ?? 30);
        $reason = trim($_POST['reason'] ?? '');
        
        if (!$facilityId) $errors['facility_id'] = 'Please select a facility';
        if (!$appointmentDate) $errors['appointment_date'] = 'Please select date and time';
        if (new DateTime($appointmentDate) < new DateTime()) $errors['appointment_date'] = 'Appointment must be in the future';
        
        if (empty($errors)) {
            try {
                $stmt = $db->prepare('INSERT INTO appointments (user_id, facility_id, provider_id, service_id, appointment_date, duration_minutes, reason) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$userId, $facilityId, $providerId, $serviceId, $appointmentDate, $duration, $reason]);
                
                logActivity('appointment_booked', "Booked appointment at facility ID: {$facilityId}", $userId);
                
                $success = 'Appointment booked successfully!';
                header('Location: /rwanda_connect/modules/healthcare/appointments.php?tab=upcoming');
                exit;
            } catch (Exception $e) {
                $errors['general'] = 'Failed to book appointment: ' . $e->getMessage();
            }
        }
    }
}

// Handle cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'cancel') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        redirectWith('/rwanda_connect/modules/healthcare/appointments.php', 'error', 'Invalid request.');
    }
    
    $appointmentId = (int)($_POST['appointment_id'] ?? 0);
    $reason = trim($_POST['cancellation_reason'] ?? '');
    
    $stmt = $db->prepare('SELECT * FROM appointments WHERE id = ? AND user_id = ?');
    $stmt->execute([$appointmentId, $userId]);
    $appointment = $stmt->fetch();
    
    if ($appointment && in_array($appointment['status'], ['scheduled', 'confirmed'], true)) {
        $stmt = $db->prepare('UPDATE appointments SET status = "cancelled", cancellation_reason = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
        $stmt->execute([$reason, $appointmentId]);
        
        logActivity('appointment_cancelled', "Cancelled appointment ID: {$appointmentId}", $userId);
        redirectWith('/rwanda_connect/modules/healthcare/appointments.php', 'success', 'Appointment cancelled.');
    } else {
        redirectWith('/rwanda_connect/modules/healthcare/appointments.php', 'error', 'Cannot cancel this appointment.');
    }
}

// Handle reschedule
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'reschedule') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        redirectWith('/rwanda_connect/modules/healthcare/appointments.php', 'error', 'Invalid request.');
    }
    
    $appointmentId = (int)($_POST['appointment_id'] ?? 0);
    $newDate = $_POST['new_date'] ?? '';
    
    $stmt = $db->prepare('SELECT * FROM appointments WHERE id = ? AND user_id = ?');
    $stmt->execute([$appointmentId, $userId]);
    $appointment = $stmt->fetch();
    
    if ($appointment && in_array($appointment['status'], ['scheduled', 'confirmed'], true) && $newDate) {
        if (new DateTime($newDate) < new DateTime()) {
            redirectWith('/rwanda_connect/modules/healthcare/appointments.php', 'error', 'New date must be in the future.');
        }
        
        $stmt = $db->prepare('UPDATE appointments SET appointment_date = ?, status = "scheduled", updated_at = CURRENT_TIMESTAMP WHERE id = ?');
        $stmt->execute([$newDate, $appointmentId]);
        
        redirectWith('/rwanda_connect/modules/healthcare/appointments.php', 'success', 'Appointment rescheduled.');
    } else {
        redirectWith('/rwanda_connect/modules/healthcare/appointments.php', 'error', 'Cannot reschedule this appointment.');
    }
}

// Get appointments
$where = ['user_id = ?'];
$params = [$userId];

if ($tab === 'upcoming') {
    $where[] = 'appointment_date >= CURDATE()';
    $where[] = 'status IN ("scheduled", "confirmed", "in_progress")';
} else {
    $where[] = '(appointment_date < CURDATE() OR status IN ("completed", "cancelled", "no_show"))';
}

$whereClause = implode(' AND ', $where);

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Get total
$stmt = $db->prepare("SELECT COUNT(*) FROM appointments WHERE {$whereClause}");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();

// Get appointments
$paramsPaged = [...$params, $perPage, $offset];
$stmt = $db->prepare("
    SELECT a.*, hf.name as facility_name, hf.type as facility_type, hf.phone as facility_phone,
           hp.specialization, u.full_name as provider_name, hs.name as service_name
    FROM appointments a
    JOIN health_facilities hf ON a.facility_id = hf.id
    LEFT JOIN healthcare_providers hp ON a.provider_id = hp.id
    LEFT JOIN users u ON hp.user_id = u.id
    LEFT JOIN health_services hs ON a.service_id = hs.id
    WHERE {$whereClause}
    ORDER BY a.appointment_date " . ($tab === 'upcoming' ? 'ASC' : 'DESC') . "
    LIMIT ? OFFSET ?
");
$stmt->execute($paramsPaged);
$appointments = $stmt->fetchAll();

// Get facilities for booking
$stmt = $db->query('SELECT id, name, type, province, district FROM health_facilities WHERE is_active = TRUE ORDER BY name');
$facilities = $stmt->fetchAll();

$csrfToken = generateCsrfToken();
$pagination = paginate($total, $perPage, $page);

$pageTitle = 'Appointments - Healthcare - Rwanda Connect';
$pageDescription = 'Manage your medical appointments and book new ones.';

require_once __DIR__ . '/../../../includes/header.php';
?>

<main class="module-page healthcare-page appointments-page">
    <div class="module-container">
        <header class="module-header">
            <div class="module-brand">
                <a href="/rwanda_connect/modules/healthcare/" class="back-link">← Healthcare</a>
                <div>
                    <h1>Appointments</h1>
                    <p>Book and manage your medical appointments</p>
                </div>
            </div>
            <?php if ($action === 'list'): ?>
                <button class="btn btn-primary" data-modal="book-appointment">📅 Book Appointment</button>
            <?php endif; ?>
        </header>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>
        
        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-error"><?= e($errors['general']) ?></div>
        <?php endif; ?>
        
        <!-- Tabs -->
        <nav class="module-tabs" aria-label="Appointment tabs">
            <a href="?tab=upcoming" class="<?= $tab === 'upcoming' ? 'active' : '' ?>">📅 Upcoming</a>
            <a href="?tab=past" class="<?= $tab === 'past' ? 'active' : '' ?>">📋 Past Appointments</a>
        </nav>
        
        <!-- Appointment List -->
        <?php if ($action === 'list'): ?>
            <section class="module-section">
                <?php if (empty($appointments)): ?>
                    <div class="empty-state-card">
                        <div class="empty-icon"><?= $tab === 'upcoming' ? '📅' : '📋' ?></div>
                        <h2><?= $tab === 'upcoming' ? 'No upcoming appointments' : 'No past appointments' ?></h2>
                        <p><?= $tab === 'upcoming' ? 'Book your next medical appointment' : 'Your completed and cancelled appointments will appear here' ?></p>
                        <?php if ($tab === 'upcoming'): ?>
                            <button class="btn btn-primary" data-modal="book-appointment">Book Appointment</button>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="appointments-list">
                        <?php foreach ($appointments as $appt): ?>
                            <article class="appointment-card">
                                <div class="appointment-date-large">
                                    <span class="day"><?= (new DateTime($appt['appointment_date']))->format('d') ?></span>
                                    <span class="month"><?= (new DateTime($appt['appointment_date']))->format('M Y') ?></span>
                                    <span class="time"><?= (new DateTime($appt['appointment_date']))->format('H:i') ?></span>
                                </div>
                                <div class="appointment-main">
                                    <div class="appointment-facility">
                                        <h4><?= e($appt['facility_name']) ?></h4>
                                        <span class="facility-type"><?= e(ucfirst(str_replace('_', ' ', $appt['facility_type']))) ?></span>
                                    </div>
                                    <?php if ($appt['provider_name']): ?>
                                        <p class="appointment-provider">👨‍⚕️ Dr. <?= e($appt['provider_name']) ?><?= $appt['specialization'] ? ' - ' . e($appt['specialization']) : '' ?></p>
                                    <?php endif; ?>
                                    <?php if ($appt['service_name']): ?>
                                        <p class="appointment-service">🏥 <?= e($appt['service_name']) ?></p>
                                    <?php endif; ?>
                                    <?php if ($appt['reason']): ?>
                                        <p class="appointment-reason"><?= e($appt['reason']) ?></p>
                                    <?php endif; ?>
                                    <div class="appointment-location">
                                        📍 <?= e($appt['facility_name']) ?><?= $appt['facility_phone'] ? ' • 📞 ' . e($appt['facility_phone']) : '' ?>
                                    </div>
                                </div>
                                <div class="appointment-status-actions">
                                    <span class="status-badge status-<?= $appt['status'] ?>">
                                        <?= e(ucfirst($appt['status'])) ?>
                                    </span>
                                    <div class="appointment-actions">
                                        <?php if ($tab === 'upcoming' && in_array($appt['status'], ['scheduled', 'confirmed'], true)): ?>
                                            <button class="btn btn-sm btn-outline" data-modal="reschedule" data-appointment-id="<?= $appt['id'] ?>" data-current-date="<?= e($appt['appointment_date']) ?>">Reschedule</button>
                                            <form method="POST" action="?action=cancel" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                                <input type="hidden" name="appointment_id" value="<?= $appt['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Cancel this appointment?')">Cancel</button>
                                            </form>
                                        <?php endif; ?>
                                        <a href="/rwanda_connect/modules/healthcare/appointment-detail.php?id=<?= $appt['id'] ?>" class="btn btn-sm btn-secondary">Details</a>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($pagination['total_pages'] > 1): ?>
                        <nav class="pagination" aria-label="Appointments pagination">
                            <?php
                            $params = ['tab' => $tab];
                            ?>
                            <?php if ($pagination['has_prev']): ?>
                                <a href="?" . http_build_query(array_merge($params, ['page' => $pagination['prev_page']])) class="page-btn">← Previous</a>
                            <?php endif; ?>
                            
                            <span class="page-info">Page <?= $pagination['current_page'] ?> of <?= $pagination['total_pages'] ?></span>
                            
                            <?php if ($pagination['has_next']): ?>
                                <a href="?" . http_build_query(array_merge($params, ['page' => $pagination['next_page']])) class="page-btn">Next →</a>
                            <?php endif; ?>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        <?php endif; ?>
        
        <!-- Book Appointment Modal -->
        <div class="modal" id="book-appointment-modal" role="dialog" aria-modal="true" aria-labelledby="book-modal-title" hidden>
            <div class="modal-overlay" data-close-modal></div>
            <div class="modal-content modal-large">
                <div class="modal-header">
                    <h2 id="book-modal-title">Book New Appointment</h2>
                    <button class="modal-close" data-close-modal aria-label="Close">✕</button>
                </div>
                <form method="POST" action="?action=book" class="modal-form">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="action" value="book">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="facility_id">Health Facility <span class="required" aria-hidden="true">*</span></label>
                            <select id="facility_id" name="facility_id" required class="<?= isset($errors['facility_id']) ? 'error' : '' ?>">
                                <option value="">Select Facility</option>
                                <?php foreach ($facilities as $facility): ?>
                                    <option value="<?= $facility['id'] ?>"><?= e($facility['name']) ?> (<?= e(ucfirst(str_replace('_', ' ', $facility['type']))) ?>) - <?= e($facility['district'] . ', ' . $facility['province']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['facility_id'])): ?>
                                <span class="error-message"><?= e($errors['facility_id']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="provider_id">Healthcare Provider (Optional)</label>
                            <select id="provider_id" name="provider_id">
                                <option value="">Any Available Provider</option>
                            </select>
                            <span class="form-hint">Providers will load based on selected facility</span>
                        </div>
                        
                        <div class="form-group">
                            <label for="service_id">Service (Optional)</label>
                            <select id="service_id" name="service_id">
                                <option value="">Any Service</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="appointment_date">Date & Time <span class="required" aria-hidden="true">*</span></label>
                            <input type="datetime-local" id="appointment_date" name="appointment_date" required min="<?= date('Y-m-d\TH:i') ?>" class="<?= isset($errors['appointment_date']) ? 'error' : '' ?>">
                            <?php if (isset($errors['appointment_date'])): ?>
                                <span class="error-message"><?= e($errors['appointment_date']) ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="duration">Duration (minutes)</label>
                            <select id="duration" name="duration">
                                <option value="15">15 minutes</option>
                                <option value="30" selected>30 minutes</option>
                                <option value="45">45 minutes</option>
                                <option value="60">60 minutes</option>
                                <option value="90">90 minutes</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="reason">Reason for Visit</label>
                            <textarea id="reason" name="reason" rows="3" placeholder="Describe the reason for your appointment..."></textarea>
                        </div>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                        <button type="submit" class="btn btn-primary">Book Appointment</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Reschedule Modal -->
        <div class="modal" id="reschedule-modal" role="dialog" aria-modal="true" aria-labelledby="reschedule-modal-title" hidden>
            <div class="modal-overlay" data-close-modal></div>
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="reschedule-modal-title">Reschedule Appointment</h2>
                    <button class="modal-close" data-close-modal aria-label="Close">✕</button>
                </div>
                <form method="POST" action="?action=reschedule" class="modal-form">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="action" value="reschedule">
                    <input type="hidden" name="appointment_id" id="reschedule_appointment_id">
                    
                    <div class="form-group">
                        <label for="new_date">New Date & Time <span class="required" aria-hidden="true">*</span></label>
                        <input type="datetime-local" id="new_date" name="new_date" required min="<?= date('Y-m-d\TH:i') ?>">
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                        <button type="submit" class="btn btn-primary">Reschedule</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Modal handling
    const modals = {
        'book-appointment': document.getElementById('book-appointment-modal'),
        'reschedule': document.getElementById('reschedule-modal'),
    };
    
    function openModal(modalId) {
        const modal = modals[modalId];
        if (modal) {
            modal.hidden = false;
            document.body.style.overflow = 'hidden';
        }
    }
    
    function closeModal(modalId) {
        const modal = modals[modalId];
        if (modal) {
            modal.hidden = true;
            document.body.style.overflow = '';
        }
    }
    
    document.querySelectorAll('[data-modal]').forEach(btn => {
        btn.addEventListener('click', () => openModal(btn.dataset.modal));
    });
    
    document.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', () => {
            const modal = btn.closest('.modal');
            if (modal) {
                modal.hidden = true;
                document.body.style.overflow = '';
            }
        });
    });
    
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            Object.values(modals).forEach(m => { if (m) { m.hidden = true; document.body.style.overflow = ''; } });
        }
    });
    
    // Reschedule modal
    document.querySelectorAll('[data-modal="reschedule"]').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('reschedule_appointment_id').value = btn.dataset.appointmentId;
            document.getElementById('new_date').value = btn.dataset.currentDate;
            openModal('reschedule');
        });
    });
    
    // Load providers and services when facility changes
    const facilitySelect = document.getElementById('facility_id');
    const providerSelect = document.getElementById('provider_id');
    const serviceSelect = document.getElementById('service_id');
    
    facilitySelect?.addEventListener('change', async () => {
        const facilityId = facilitySelect.value;
        
        // Reset selects
        providerSelect.innerHTML = '<option value="">Loading...</option>';
        serviceSelect.innerHTML = '<option value="">Loading...</option>';
        
        if (!facilityId) {
            providerSelect.innerHTML = '<option value="">Any Available Provider</option>';
            serviceSelect.innerHTML = '<option value="">Any Service</option>';
            return;
        }
        
        try {
            // Load providers
            const providersResponse = await fetch(`/rwanda_connect/api/healthcare/providers.php?facility_id=${facilityId}`);
            const providers = await providersResponse.json();
            
            providerSelect.innerHTML = '<option value="">Any Available Provider</option>';
            providers.forEach(p => {
                const option = document.createElement('option');
                option.value = p.id;
                option.textContent = `Dr. ${p.name}${p.specialization ? ' - ' + p.specialization : ''}`;
                providerSelect.appendChild(option);
            });
            
            // Load services
            const servicesResponse = await fetch(`/rwanda_connect/api/healthcare/services.php?facility_id=${facilityId}`);
            const services = await servicesResponse.json();
            
            serviceSelect.innerHTML = '<option value="">Any Service</option>';
            services.forEach(s => {
                const option = document.createElement('option');
                option.value = s.id;
                option.textContent = `${s.name}${s.cost_rwf ? ' - ' + new Intl.NumberFormat('en-RW', {style: 'currency', currency: 'RWF'}).format(s.cost_rwf) : ''}`;
                serviceSelect.appendChild(option);
            });
        } catch (err) {
            console.error('Failed to load providers/services:', err);
            providerSelect.innerHTML = '<option value="">Error loading providers</option>';
            serviceSelect.innerHTML = '<option value="">Error loading services</option>';
        }
    });
});
</script>