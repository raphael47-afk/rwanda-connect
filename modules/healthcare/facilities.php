<?php
declare(strict_types=1);

/**
 * Rwanda Connect - Healthcare: Facilities
 */

require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';

requireLogin();

$db = getDb();

// Filters
$typeFilter = $_GET['type'] ?? '';
$provinceFilter = $_GET['province'] ?? '';
$districtFilter = $_GET['district'] ?? '';
$search = trim($_GET['q'] ?? '');
$serviceFilter = $_GET['service'] ?? ''; // emergency, ambulance, lab, pharmacy, maternity, dental, mental_health

// Build query
$where = ['is_active = TRUE'];
$params = [];

if ($typeFilter) {
    $where[] = 'type = ?';
    $params[] = $typeFilter;
}

if ($provinceFilter) {
    $where[] = 'province = ?';
    $params[] = $provinceFilter;
}

if ($districtFilter) {
    $where[] = 'district = ?';
    $params[] = $districtFilter;
}

if ($search) {
    $where[] = '(name LIKE ? OR description LIKE ?)';
    $searchTerm = "%{$search}%";
    $params = array_merge($params, [$searchTerm, $searchTerm]);
}

if ($serviceFilter) {
    $serviceColumn = match($serviceFilter) {
        'emergency' => 'emergency_services',
        'ambulance' => 'ambulance_service',
        'lab' => 'lab_services',
        'pharmacy' => 'pharmacy',
        'maternity' => 'maternity_services',
        'dental' => 'dental_services',
        'mental_health' => 'mental_health_services',
        default => null
    };
    if ($serviceColumn) {
        $where[] = "{$serviceColumn} = TRUE";
    }
}

$whereClause = implode(' AND ', $where);

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Get total
$stmt = $db->prepare("SELECT COUNT(*) FROM health_facilities WHERE {$whereClause}");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();

// Get facilities
$paramsPaged = [...$params, $perPage, $offset];
$stmt = $db->prepare("
    SELECT * FROM health_facilities 
    WHERE {$whereClause}
    ORDER BY 
        CASE WHEN type = 'hospital' THEN 1 ELSE 2 END,
        name
    LIMIT ? OFFSET ?
");
$stmt->execute($paramsPaged);
$facilities = $stmt->fetchAll();

// Get filter options
$stmt = $db->query('SELECT DISTINCT type FROM health_facilities WHERE is_active = TRUE ORDER BY type');
$types = $stmt->fetchAll();

$stmt = $db->query('SELECT DISTINCT province FROM health_facilities WHERE is_active = TRUE ORDER BY province');
$provinces = $stmt->fetchAll();

$districts = [];
if ($provinceFilter) {
    $stmt = $db->prepare('SELECT DISTINCT district FROM health_facilities WHERE province = ? AND is_active = TRUE ORDER BY district');
    $stmt->execute([$provinceFilter]);
    $districts = $stmt->fetchAll();
}

$pagination = paginate($total, $perPage, $page);

$pageTitle = 'Health Facilities - Healthcare - Rwanda Connect';
$pageDescription = 'Find hospitals, clinics, pharmacies, and other health facilities across Rwanda.';

require_once __DIR__ . '/../../../includes/header.php';
?>

<main class="module-page healthcare-page facilities-page">
    <div class="module-container">
        <header class="module-header">
            <div class="module-brand">
                <a href="/rwanda_connect/modules/healthcare/" class="back-link">← Healthcare</a>
                <div>
                    <h1>Health Facilities</h1>
                    <p>Find hospitals, clinics, pharmacies, and more</p>
                </div>
            </div>
        </header>
        
        <!-- Filters -->
        <section class="module-section filters-section">
            <form method="GET" action="" class="search-filters">
                <div class="filter-row">
                    <div class="form-group search-group">
                        <span class="search-icon">🔍</span>
                        <input type="search" name="q" placeholder="Search facilities..." value="<?= e($search) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="type">Facility Type</label>
                        <select id="type" name="type">
                            <option value="">All Types</option>
                            <?php foreach ($types as $type): ?>
                                <option value="<?= e($type['type']) ?>" <?= $typeFilter === $type['type'] ? 'selected' : '' ?>><?= e(ucfirst(str_replace('_', ' ', $type['type']))) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="province">Province</label>
                        <select id="province" name="province" onchange="this.form.submit()">
                            <option value="">All Provinces</option>
                            <?php foreach ($provinces as $prov): ?>
                                <option value="<?= e($prov['province']) ?>" <?= $provinceFilter === $prov['province'] ? 'selected' : '' ?>><?= e($prov['province']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="district">District</label>
                        <select id="district" name="district" <?= !$provinceFilter ? 'disabled' : '' ?>>
                            <option value="">All Districts</option>
                            <?php foreach ($districts as $dist): ?>
                                <option value="<?= e($dist['district']) ?>" <?= $districtFilter === $dist['district'] ? 'selected' : '' ?>><?= e($dist['district']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="service">Services</label>
                        <select id="service" name="service">
                            <option value="">All Services</option>
                            <option value="emergency" <?= $serviceFilter === 'emergency' ? 'selected' : '' ?>>🚨 Emergency</option>
                            <option value="ambulance" <?= $serviceFilter === 'ambulance' ? 'selected' : '' ?>>🚑 Ambulance</option>
                            <option value="lab" <?= $serviceFilter === 'lab' ? 'selected' : '' ?>>🧪 Lab Services</option>
                            <option value="pharmacy" <?= $serviceFilter === 'pharmacy' ? 'selected' : '' ?>>💊 Pharmacy</option>
                            <option value="maternity" <?= $serviceFilter === 'maternity' ? 'selected' : '' ?>>🤱 Maternity</option>
                            <option value="dental" <?= $serviceFilter === 'dental' ? 'selected' : '' ?>>🦷 Dental</option>
                            <option value="mental_health" <?= $serviceFilter === 'mental_health' ? 'selected' : '' ?>>🧠 Mental Health</option>
                        </select>
                    </div>
                    
                    <div class="form-group filter-actions">
                        <a href="/rwanda_connect/modules/healthcare/facilities.php" class="btn btn-secondary">Clear</a>
                    </div>
                </div>
            </form>
        </section>
        
        <!-- Results -->
        <section class="module-section">
            <div class="section-header">
                <h2>Facilities</h2>
                <span class="results-count"><?= $total ?> facilit<?= $total !== 1 ? 'ies' : 'y' ?> found</span>
            </div>
            
            <?php if (empty($facilities)): ?>
                <div class="empty-state-card">
                    <div class="empty-icon">🏥</div>
                    <h2>No facilities found</h2>
                    <p>Try adjusting your filters or search terms.</p>
                    <a href="/rwanda_connect/modules/healthcare/facilities.php" class="btn btn-primary">Clear Filters</a>
                </div>
            <?php else: ?>
                <div class="facilities-grid">
                    <?php foreach ($facilities as $facility): ?>
                        <article class="facility-card">
                            <div class="facility-header">
                                <div class="facility-type-icon">
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
                                    <h3><?= e($facility['name']) ?></h3>
                                    <span class="facility-type-badge"><?= e(ucfirst(str_replace('_', ' ', $facility['type']))) ?></span>
                                </div>
                            </div>
                            
                            <div class="facility-location">📍 <?= e($facility['district'] . ', ' . $facility['province']) ?></div>
                            
                            <div class="facility-services">
                                <?php if ($facility['emergency_services']): ?><span class="service-badge emergency">🚨 Emergency</span><?php endif; ?>
                                <?php if ($facility['ambulance_service']): ?><span class="service-badge ambulance">🚑 Ambulance</span><?php endif; ?>
                                <?php if ($facility['lab_services']): ?><span class="service-badge lab">🧪 Lab</span><?php endif; ?>
                                <?php if ($facility['pharmacy']): ?><span class="service-badge pharmacy">💊 Pharmacy</span><?php endif; ?>
                                <?php if ($facility['maternity_services']): ?><span class="service-badge maternity">🤱 Maternity</span><?php endif; ?>
                                <?php if ($facility['dental_services']): ?><span class="service-badge dental">🦷 Dental</span><?php endif; ?>
                                <?php if ($facility['mental_health_services']): ?><span class="service-badge mental">🧠 Mental Health</span><?php endif; ?>
                            </div>
                            
                            <?php if ($facility['phone']): ?>
                                <div class="facility-contact">📞 <?= e($facility['phone']) ?></div>
                            <?php endif; ?>
                            
                            <?php if ($facility['opening_hours']): ?>
                                <div class="facility-hours">🕐 <?= e($facility['opening_hours']) ?></div>
                            <?php endif; ?>
                            
                            <div class="facility-actions">
                                <a href="/rwanda_connect/modules/healthcare/facility-detail.php?id=<?= $facility['id'] ?>" class="btn btn-primary">View Details</a>
                                <?php if ($facility['emergency_services']): ?>
                                    <a href="tel:<?= e($facility['phone'] ?? '') ?>" class="btn btn-outline emergency-call">🚨 Call</a>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($pagination['total_pages'] > 1): ?>
                    <nav class="pagination" aria-label="Facilities pagination">
                        <?php
                        $baseParams = array_filter([
                            'type' => $typeFilter,
                            'province' => $provinceFilter,
                            'district' => $districtFilter,
                            'q' => $search,
                            'service' => $serviceFilter,
                        ]);
                        ?>
                        <?php if ($pagination['has_prev']): ?>
                            <a href="?" . http_build_query(array_merge($baseParams, ['page' => $pagination['prev_page']])) class="page-btn">← Previous</a>
                        <?php endif; ?>
                        
                        <span class="page-info">Page <?= $pagination['current_page'] ?> of <?= $pagination['total_pages'] ?></span>
                        
                        <?php if ($pagination['has_next']): ?>
                            <a href="?" . http_build_query(array_merge($baseParams, ['page' => $pagination['next_page']])) class="page-btn">Next →</a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const provinceSelect = document.getElementById('province');
    const districtSelect = document.getElementById('district');
    
    provinceSelect?.addEventListener('change', () => {
        const province = provinceSelect.value;
        districtSelect.disabled = !province;
        
        if (province) {
            // Reload page with province filter to get districts
            const params = new URLSearchParams(window.location.search);
            params.set('province', province);
            params.delete('district');
            params.delete('page');
            window.location.search = params.toString();
        }
    });
});
</script>