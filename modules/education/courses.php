<?php
declare(strict_types=1);

/**
 * Rwanda Connect - Education: Courses
 */

require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';

requireLogin();

$db = getDb();

// Filters
$search = trim($_GET['q'] ?? '');
$levelFilter = $_GET['level'] ?? '';
$institutionFilter = $_GET['institution'] ?? '';
$fieldFilter = $_GET['field'] ?? '';
$modeFilter = $_GET['mode'] ?? '';

// Build query
$where = ['c.is_active = TRUE'];
$params = [];

if ($search) {
    $where[] = '(c.name LIKE ? OR c.description LIKE ? OR i.name LIKE ?)';
    $searchTerm = "%{$search}%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
}

if ($levelFilter) {
    $where[] = 'c.level = ?';
    $params[] = $levelFilter;
}

if ($institutionFilter) {
    $where[] = 'c.institution_id = ?';
    $params[] = $institutionFilter;
}

if ($fieldFilter) {
    $where[] = 'c.field_of_study LIKE ?';
    $params[] = "%{$fieldFilter}%";
}

if ($modeFilter) {
    $where[] = 'c.study_mode = ?';
    $params[] = $modeFilter;
}

$whereClause = implode(' AND ', $where);

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Get total
$stmt = $db->prepare("SELECT COUNT(*) FROM courses c JOIN institutions i ON c.institution_id = i.id WHERE {$whereClause}");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();

// Get courses
$paramsPaged = [...$params, $perPage, $offset];
$stmt = $db->prepare("
    SELECT c.*, i.name as institution_name, i.type as institution_type, i.province, i.district
    FROM courses c
    JOIN institutions i ON c.institution_id = i.id
    WHERE {$whereClause}
    ORDER BY c.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute($paramsPaged);
$courses = $stmt->fetchAll();

// Get filter options
$stmt = $db->query('SELECT * FROM institutions WHERE is_accredited = TRUE ORDER BY name');
$institutions = $stmt->fetchAll();

$levels = ['certificate', 'diploma', 'bachelor', 'master', 'phd', 'short_course', 'professional'];
$modes = ['full_time', 'part_time', 'distance', 'online', 'hybrid'];

$pagination = paginate($total, $perPage, $page);

$pageTitle = 'Courses & Programs - Education - Rwanda Connect';
$pageDescription = 'Explore courses and programs from accredited institutions across Rwanda.';

require_once __DIR__ . '/../../../includes/header.php';
?>

<main class="module-page education-page courses-page">
    <div class="module-container">
        <header class="module-header">
            <div class="module-brand">
                <a href="/rwanda_connect/modules/education/" class="back-link">← Education</a>
                <div>
                    <h1>Courses & Programs</h1>
                    <p>Find the right program for your career goals</p>
                </div>
            </div>
        </header>

        <!-- Filters -->
        <section class="module-section filters-section">
            <form method="GET" action="" class="search-filters">
                <div class="filter-row">
                    <div class="form-group search-group">
                        <span class="search-icon">🔍</span>
                        <input type="search" name="q" placeholder="Search courses..." value="<?= e($search) ?>">
                    </div>

                    <div class="form-group">
                        <label for="level">Level</label>
                        <select id="level" name="level">
                            <option value="">Any Level</option>
                            <?php foreach ($levels as $level): ?>
                                <option value="<?= $level ?>" <?= $levelFilter === $level ? 'selected' : '' ?>><?= e(ucfirst(str_replace('_', ' ', $level))) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="institution">Institution</label>
                        <select id="institution" name="institution">
                            <option value="">Any Institution</option>
                            <?php foreach ($institutions as $inst): ?>
                                <option value="<?= $inst['id'] ?>" <?= $institutionFilter == $inst['id'] ? 'selected' : '' ?>><?= e($inst['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="field">Field of Study</label>
                        <input type="text" id="field" name="field" value="<?= e($fieldFilter) ?>" placeholder="e.g., Computer Science, Medicine" list="fields-list">
                        <datalist id="fields-list">
                            <option value="Computer Science">
                            <option value="Medicine">
                            <option value="Engineering">
                            <option value="Business Administration">
                            <option value="Education">
                            <option value="Agriculture">
                            <option value="Nursing">
                            <option value="Law">
                            <option value="Economics">
                            <option value="Public Health">
                        </datalist>
                    </div>

                    <div class="form-group">
                        <label for="mode">Study Mode</label>
                        <select id="mode" name="mode">
                            <option value="">Any Mode</option>
                            <?php foreach ($modes as $mode): ?>
                                <option value="<?= $mode ?>" <?= $modeFilter === $mode ? 'selected' : '' ?>><?= e(ucfirst(str_replace('_', ' ', $mode))) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group filter-actions">
                        <a href="/rwanda_connect/modules/education/courses.php" class="btn btn-secondary">Clear</a>
                    </div>
                </div>
            </form>
        </section>

        <!-- Results -->
        <section class="module-section">
            <div class="section-header">
                <h2>Available Programs</h2>
                <span class="results-count"><?= $total ?> program<?= $total !== 1 ? 's' : '' ?> found</span>
            </div>

            <?php if (empty($courses)): ?>
                <div class="empty-state-card">
                    <div class="empty-icon">📚</div>
                    <h2>No programs found</h2>
                    <p>Try adjusting your filters or search terms.</p>
                    <a href="/rwanda_connect/modules/education/courses.php" class="btn btn-primary">Clear Filters</a>
                </div>
            <?php else: ?>
                <div class="courses-grid">
                    <?php foreach ($courses as $course): ?>
                        <article class="course-card">
                            <div class="course-header">
                                <div class="course-level"><?= e(ucfirst(str_replace('_', ' ', $course['level']))) ?></div>
                                <div class="course-mode"><?= e(ucfirst(str_replace('_', ' ', $course['study_mode']))) ?></div>
                            </div>
                            <h3><?= e($course['name']) ?></h3>
                            <p class="course-institution">🏫 <?= e($course['institution_name']) ?></p>
                            <?php if ($course['field_of_study']): ?>
                                <p class="course-field">📚 <?= e($course['field_of_study']) ?></p>
                            <?php endif; ?>
                            <div class="course-meta">
                                <?php if ($course['duration_months']): ?>
                                    <span class="meta-item">📅 <?= $course['duration_months'] ?> months</span>
                                <?php endif; ?>
                                <?php if ($course['tuition_fee_rwf']): ?>
                                    <span class="meta-item">💰 <?= formatCurrency($course['tuition_fee_rwf']) ?></span>
                                <?php endif; ?>
                                <span class="meta-item">📍 <?= e($course['province'] ?? '') ?><?= $course['district'] ? ', ' . e($course['district']) : '' ?></span>
                            </div>
                            <div class="course-description"><?= e(substr($course['description'] ?? '', 0, 150)) ?><?= strlen($course['description'] ?? '') > 150 ? '...' : '' ?></div>
                            <div class="course-actions">
                                <a href="/rwanda_connect/modules/education/course-detail.php?id=<?= $course['id'] ?>" class="btn btn-sm btn-primary">View Details</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($pagination['total_pages'] > 1): ?>
                    <nav class="pagination" aria-label="Courses pagination">
                        <?php
                        $baseParams = array_filter([
                            'q' => $search,
                            'level' => $levelFilter,
                            'institution' => $institutionFilter,
                            'field' => $fieldFilter,
                            'mode' => $modeFilter,
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