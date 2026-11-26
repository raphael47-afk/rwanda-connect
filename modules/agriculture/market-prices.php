<?php
declare(strict_types=1);

/**
 * Rwanda Connect - Agriculture: Market Prices
 */

require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';

requireLogin();

$db = getDb();

// Filters
$cropFilter = $_GET['crop'] ?? '';
$marketFilter = $_GET['market'] ?? '';
$provinceFilter = $_GET['province'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

// Build query
$where = ['1=1'];
$params = [];

if ($cropFilter) {
    $where[] = 'c.id = ?';
    $params[] = $cropFilter;
}

if ($marketFilter) {
    $where[] = 'mp.market_name LIKE ?';
    $params[] = "%{$marketFilter}%";
}

if ($provinceFilter) {
    $where[] = 'mp.province = ?';
    $params[] = $provinceFilter;
}

if ($dateFrom) {
    $where[] = 'mp.recorded_at >= ?';
    $params[] = $dateFrom;
}

if ($dateTo) {
    $where[] = 'mp.recorded_at <= ?';
    $params[] = $dateTo;
}

$whereClause = implode(' AND ', $where);

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

// Get total
$stmt = $db->prepare("SELECT COUNT(*) FROM market_prices mp JOIN crops c ON mp.crop_id = c.id WHERE {$whereClause}");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();

// Get prices
$paramsPaged = [...$params, $perPage, $offset];
$stmt = $db->prepare("
    SELECT mp.*, c.name as crop_name, c.category
    FROM market_prices mp
    JOIN crops c ON mp.crop_id = c.id
    WHERE {$whereClause}
    ORDER BY mp.recorded_at DESC, mp.market_name
    LIMIT ? OFFSET ?
");
$stmt->execute($paramsPaged);
$prices = $stmt->fetchAll();

// Get filter options
$stmt = $db->query('SELECT id, name FROM crops WHERE is_active = TRUE ORDER BY name');
$crops = $stmt->fetchAll();

$stmt = $db->query('SELECT DISTINCT market_name FROM market_prices ORDER BY market_name');
$markets = $stmt->fetchAll();

$stmt = $db->query('SELECT DISTINCT province FROM market_prices ORDER BY province');
$provinces = $stmt->fetchAll();

$pagination = paginate($total, $perPage, $page);

// Build page URL with filters
function buildMarketUrl(int $page, array $currentParams): string {
    $params = array_merge($currentParams, ['page' => $page]);
    return '?' . http_build_query($params);
}

$currentParams = array_filter([
    'crop' => $cropFilter,
    'market' => $marketFilter,
    'province' => $provinceFilter,
    'date_from' => $dateFrom,
    'date_to' => $dateTo,
]);

$pageTitle = 'Market Prices - Agriculture - Rwanda Connect';
$pageDescription = 'View current market prices for agricultural products across Rwanda.';

require_once __DIR__ . '/../../../includes/header.php';
?>

<main class="module-page agriculture-page market-prices-page">
    <div class="module-container">
        <header class="module-header">
            <div class="module-brand">
                <a href="/rwanda_connect/modules/agriculture/" class="back-link">← Agriculture</a>
                <div>
                    <h1>Market Prices</h1>
                    <p>Real-time agricultural market prices across Rwanda</p>
                </div>
            </div>
        </header>
        
        <!-- Filters -->
        <section class="module-section filters-section">
            <form method="GET" action="" class="filters-form">
                <div class="filter-row">
                    <div class="form-group">
                        <label for="crop">Crop</label>
                        <select id="crop" name="crop">
                            <option value="">All Crops</option>
                            <?php foreach ($crops as $crop): ?>
                                <option value="<?= $crop['id'] ?>" <?= $cropFilter == $crop['id'] ? 'selected' : '' ?>><?= e($crop['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="market">Market</label>
                        <input type="text" id="market" name="market" value="<?= e($marketFilter) ?>" placeholder="Market name" list="markets-list">
                        <datalist id="markets-list">
                            <?php foreach ($markets as $market): ?>
                                <option value="<?= e($market['market_name']) ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    
                    <div class="form-group">
                        <label for="province">Province</label>
                        <select id="province" name="province">
                            <option value="">All Provinces</option>
                            <?php foreach ($provinces as $prov): ?>
                                <option value="<?= e($prov['province']) ?>" <?= $provinceFilter === $prov['province'] ? 'selected' : '' ?>><?= e($prov['province']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="date_from">From Date</label>
                        <input type="date" id="date_from" name="date_from" value="<?= e($dateFrom) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="date_to">To Date</label>
                        <input type="date" id="date_to" name="date_to" value="<?= e($dateTo) ?>" max="<?= date('Y-m-d') ?>">
                    </div>
                    
                    <div class="form-group filter-actions">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="/rwanda_connect/modules/agriculture/market-prices.php" class="btn btn-secondary">Clear</a>
                    </div>
                </div>
            </form>
        </section>
        
        <!-- Price Trends Summary -->
        <?php if (empty($cropFilter) && empty($marketFilter) && empty($provinceFilter)): ?>
            <section class="price-trends">
                <h2>Price Trends (Last 30 Days)</h2>
                <div class="trends-grid">
                    <?php
                    $stmt = $db->query("
                        SELECT c.name, c.category, 
                               AVG(mp.price_per_kg) as avg_price,
                               MIN(mp.price_per_kg) as min_price,
                               MAX(mp.price_per_kg) as max_price,
                               COUNT(*) as data_points
                        FROM market_prices mp
                        JOIN crops c ON mp.crop_id = c.id
                        WHERE mp.recorded_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                        GROUP BY c.id, c.name, c.category
                        ORDER BY c.category, c.name
                    ");
                    $trends = $stmt->fetchAll();
                    
                    foreach ($trends as $trend): ?>
                        <div class="trend-card">
                            <div class="trend-header">
                                <span class="trend-category"><?= e(ucfirst($trend['category'])) ?></span>
                                <h3><?= e($trend['name']) ?></h3>
                            </div>
                            <div class="trend-prices">
                                <div class="trend-price avg">
                                    <span class="label">Avg</span>
                                    <span class="value"><?= formatCurrency($trend['avg_price']) ?></span>
                                </div>
                                <div class="trend-price range">
                                    <span class="label">Range</span>
                                    <span class="value"><?= formatCurrency($trend['min_price']) ?> - <?= formatCurrency($trend['max_price']) ?></span>
                                </div>
                                <div class="trend-meta"><?= (int)$trend['data_points'] ?> price points</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
        
        <!-- Prices Table -->
        <section class="module-section">
            <div class="section-header">
                <h2>Price Listings</h2>
                <span class="results-count"><?= $total ?> result<?= $total !== 1 ? 's' : '' ?></span>
            </div>
            
            <?php if (empty($prices)): ?>
                <div class="empty-state-card">
                    <div class="empty-icon">💰</div>
                    <h2>No price data found</h2>
                    <p>Try adjusting your filters or check back later for updates.</p>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table class="market-table">
                        <thead>
                            <tr>
                                <th>Crop</th>
                                <th>Category</th>
                                <th>Market</th>
                                <th>Location</th>
                                <th>Price/kg</th>
                                <th>Date</th>
                                <th>Source</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($prices as $price): ?>
                                <tr>
                                    <td>
                                        <strong><?= e($price['crop_name']) ?></strong>
                                    </td>
                                    <td><span class="category-badge"><?= e(ucfirst($price['category'])) ?></span></td>
                                    <td><?= e($price['market_name']) ?></td>
                                    <td><?= e($price['district'] . ', ' . $price['province']) ?></td>
                                    <td class="price-cell"><?= formatCurrency($price['price_per_kg']) ?></td>
                                    <td><?= formatDate($price['recorded_at']) ?></td>
                                    <td><?= e($price['source'] ?? 'Market Survey') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($pagination['total_pages'] > 1): ?>
                    <nav class="pagination" aria-label="Price listings pagination">
                        <?php if ($pagination['has_prev']): ?>
                            <a href="<?= buildMarketUrl($pagination['prev_page'], $currentParams) ?>" class="page-btn">← Previous</a>
                        <?php endif; ?>
                        
                        <span class="page-info">
                            Page <?= $pagination['current_page'] ?> of <?= $pagination['total_pages'] ?>
                        </span>
                        
                        <?php if ($pagination['has_next']): ?>
                            <a href="<?= buildMarketUrl($pagination['next_page'], $currentParams) ?>" class="page-btn">Next →</a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>