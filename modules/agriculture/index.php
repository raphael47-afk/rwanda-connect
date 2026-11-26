<?php
declare(strict_types=1);

/**
 * Rwanda Connect - Agriculture Module Home
 */

require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';

requireLogin();

$userId = getCurrentUserId();
$db = getDb();

// Get user's farms summary
$stmt = $db->prepare('SELECT COUNT(*) as count, SUM(size_hectares) as total FROM user_farms WHERE user_id = ? AND is_active = TRUE');
$stmt->execute([$userId]);
$farmSummary = $stmt->fetch();

// Get active crops
$stmt = $db->prepare('
    SELECT fc.*, c.name as crop_name, c.category, f.name as farm_name 
    FROM farm_crops fc
    JOIN crops c ON fc.crop_id = c.id
    JOIN user_farms f ON fc.farm_id = f.id
    WHERE f.user_id = ? AND fc.status IN ("planted", "growing")
    ORDER BY fc.expected_harvest_date ASC
    LIMIT 5
');
$stmt->execute([$userId]);
$activeCrops = $stmt->fetchAll();

// Get latest market prices
$stmt = $db->query('
    SELECT mp.*, c.name as crop_name 
    FROM market_prices mp
    JOIN crops c ON mp.crop_id = c.id
    WHERE mp.recorded_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ORDER BY mp.recorded_at DESC
    LIMIT 10
');
$marketPrices = $stmt->fetchAll();

// Get current season
$stmt = $db->query('SELECT * FROM farming_seasons WHERE is_active = TRUE ORDER BY start_month LIMIT 1');
$currentSeason = $stmt->fetch();

// Get farming tips
$stmt = $db->query('SELECT * FROM farming_tips WHERE is_active = TRUE ORDER BY RAND() LIMIT 3');
$farmingTips = $stmt->fetchAll();

// Get advisors
$stmt = $db->query('
    SELECT aa.*, u.full_name, u.profile_image 
    FROM agri_advisors aa
    JOIN users u ON aa.user_id = u.id
    WHERE aa.is_available = TRUE AND u.status = "active"
    ORDER BY aa.rating DESC
    LIMIT 4
');
$advisors = $stmt->fetchAll();

$pageTitle = 'Agriculture - Rwanda Connect';
$pageDescription = 'Manage your farms, track crops, check market prices, and get expert farming advice.';

require_once __DIR__ . '/../../../includes/header.php';
?>

<main class="module-page agriculture-page">
    <div class="module-container">
        <!-- Module Header -->
        <header class="module-header">
            <div class="module-brand">
                <span class="module-icon">🌱</span>
                <div>
                    <h1>Agriculture</h1>
                    <p>Farm management, market intelligence, and expert guidance</p>
                </div>
            </div>
            <div class="module-actions">
                <a href="/rwanda_connect/modules/agriculture/farms.php" class="btn btn-primary">
                    <span>➕</span> Add Farm
                </a>
                <a href="/rwanda_connect/modules/agriculture/market-prices.php" class="btn btn-secondary">Market Prices</a>
            </div>
        </header>
        
        <!-- Season Banner -->
        <?php if ($currentSeason): ?>
            <div class="season-banner">
                <div class="season-info">
                    <span class="season-icon">📅</span>
                    <div>
                        <strong>Current Season: <?= e($currentSeason['name']) ?></strong>
                        <span><?= e($currentSeason['description'] ?? 'Planting and growing season') ?></span>
                    </div>
                </div>
                <a href="/rwanda_connect/modules/agriculture/calendar.php" class="btn btn-sm btn-outline">View Calendar</a>
            </div>
        <?php endif; ?>
        
        <!-- Stats Overview -->
        <section class="module-stats" aria-label="Farm Statistics">
            <div class="stat-card">
                <div class="stat-icon">🏞️</div>
                <div class="stat-content">
                    <span class="stat-value"><?= (int)($farmSummary['count'] ?? 0) ?></span>
                    <span class="stat-label">Active Farms</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🌾</div>
                <div class="stat-content">
                    <span class="stat-value"><?= $farmSummary['total'] ? number_format($farmSummary['total'], 2) : '0' ?></span>
                    <span class="stat-label">Total Hectares</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🌱</div>
                <div class="stat-content">
                    <span class="stat-value"><?= count($activeCrops) ?></span>
                    <span class="stat-label">Active Crops</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-content">
                    <span class="stat-value"><?= count($marketPrices) ?></span>
                    <span class="stat-label">Price Updates</span>
                </div>
            </div>
        </section>
        
        <!-- Main Content Grid -->
        <div class="module-grid">
            <!-- My Farms -->
            <section class="module-section farms-section">
                <div class="section-header">
                    <h2>My Farms</h2>
                    <a href="/rwanda_connect/modules/agriculture/farms.php" class="section-link">View All →</a>
                </div>
                <div class="section-content">
                    <?php if (($farmSummary['count'] ?? 0) > 0): ?>
                        <div class="farms-list">
                            <?php
                            $stmt = $db->prepare('SELECT * FROM user_farms WHERE user_id = ? AND is_active = TRUE ORDER BY created_at DESC LIMIT 3');
                            $stmt->execute([$userId]);
                            $recentFarms = $stmt->fetchAll();
                            foreach ($recentFarms as $farm): ?>
                                <article class="farm-card">
                                    <div class="farm-header">
                                        <h3><?= e($farm['name']) ?></h3>
                                        <span class="farm-size"><?= number_format($farm['size_hectares'], 2) ?> ha</span>
                                    </div>
                                    <div class="farm-location">📍 <?= e($farm['district'] . ', ' . $farm['province']) ?></div>
                                    <div class="farm-details">
                                        <span>Soil: <?= e($farm['soil_type'] ?? 'Not specified') ?></span>
                                        <span>Irrigation: <?= e(ucfirst($farm['irrigation_type'] ?? 'Rainfed')) ?></span>
                                    </div>
                                    <a href="/rwanda_connect/modules/agriculture/farm-detail.php?id=<?= $farm['id'] ?>" class="farm-link">Manage Farm →</a>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state-inline">
                            <span class="empty-icon">🏞️</span>
                            <p>No farms added yet</p>
                            <a href="/rwanda_connect/modules/agriculture/farms.php" class="btn btn-sm btn-primary">Add Your First Farm</a>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
            
            <!-- Active Crops -->
            <section class="module-section crops-section">
                <div class="section-header">
                    <h2>Active Crops</h2>
                    <a href="/rwanda_connect/modules/agriculture/crops.php" class="section-link">View All →</a>
                </div>
                <div class="section-content">
                    <?php if (!empty($activeCrops)): ?>
                        <div class="crops-list">
                            <?php foreach ($activeCrops as $crop): ?>
                                <article class="crop-card">
                                    <div class="crop-info">
                                        <h4><?= e($crop['crop_name']) ?></h4>
                                        <span class="crop-farm"><?= e($crop['farm_name']) ?></span>
                                    </div>
                                    <div class="crop-progress">
                                        <div class="progress-bar">
                                            <?php
                                            $progress = 0;
                                            if ($crop['planting_date'] && $crop['expected_harvest_date']) {
                                                $start = new DateTime($crop['planting_date']);
                                                $end = new DateTime($crop['expected_harvest_date']);
                                                $now = new DateTime();
                                                if ($now >= $start && $now <= $end) {
                                                    $total = $start->diff($end)->days;
                                                    $elapsed = $start->diff($now)->days;
                                                    $progress = min(100, max(0, round(($elapsed / $total) * 100)));
                                                } elseif ($now > $end) {
                                                    $progress = 100;
                                                }
                                            }
                                            ?>
                                            <div class="progress-fill" style="width: <?= $progress ?>%"></div>
                                        </div>
                                        <span class="progress-text"><?= $progress ?>% grown</span>
                                    </div>
                                    <div class="crop-dates">
                                        <?php if ($crop['expected_harvest_date']): ?>
                                            <span class="harvest-date">📅 Harvest: <?= formatDate($crop['expected_harvest_date']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state-inline">
                            <span class="empty-icon">🌱</span>
                            <p>No active crops</p>
                            <a href="/rwanda_connect/modules/agriculture/crops.php" class="btn btn-sm btn-primary">Plant a Crop</a>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
        
        <!-- Market Prices & Advisors -->
        <div class="module-grid">
            <!-- Market Prices -->
            <section class="module-section market-section">
                <div class="section-header">
                    <h2>Latest Market Prices</h2>
                    <a href="/rwanda_connect/modules/agriculture/market-prices.php" class="section-link">View All →</a>
                </div>
                <div class="section-content">
                    <?php if (!empty($marketPrices)): ?>
                        <div class="market-table-wrapper">
                            <table class="market-table">
                                <thead>
                                    <tr>
                                        <th>Crop</th>
                                        <th>Market</th>
                                        <th>Price/kg</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($marketPrices, 0, 5) as $price): ?>
                                        <tr>
                                            <td><?= e($price['crop_name']) ?></td>
                                            <td><?= e($price['market_name']) ?></td>
                                            <td class="price"><?= formatCurrency($price['price_per_kg']) ?></td>
                                            <td><?= formatDate($price['recorded_at']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state-inline">
                            <span class="empty-icon">💰</span>
                            <p>No recent price data</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
            
            <!-- Expert Advisors -->
            <section class="module-section advisors-section">
                <div class="section-header">
                    <h2>Expert Advisors</h2>
                    <a href="/rwanda_connect/modules/agriculture/advisors.php" class="section-link">View All →</a>
                </div>
                <div class="section-content">
                    <?php if (!empty($advisors)): ?>
                        <div class="advisors-grid">
                            <?php foreach ($advisors as $advisor): ?>
                                <article class="advisor-card">
                                    <div class="advisor-avatar">
                                        <?php if (!empty($advisor['profile_image'])): ?>
                                            <img src="<?= e($advisor['profile_image']) ?>" alt="">
                                        <?php else: ?>
                                            <span><?= e(strtoupper(substr($advisor['full_name'], 0, 1))) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="advisor-info">
                                        <h4><?= e($advisor['full_name']) ?></h4>
                                        <p class="advisor-specialty"><?= e($advisor['specialization'] ?? 'General Agriculture') ?></p>
                                        <div class="advisor-meta">
                                            <?php if ($advisor['experience_years']): ?>
                                                <span>📅 <?= $advisor['experience_years'] ?> yrs exp</span>
                                            <?php endif; ?>
                                            <?php if ($advisor['rating']): ?>
                                                <span>⭐ <?= $advisor['rating'] ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <a href="/rwanda_connect/modules/agriculture/advisor-detail.php?id=<?= $advisor['id'] ?>" class="btn btn-sm btn-outline">Consult</a>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state-inline">
                            <span class="empty-icon">👨‍🌾</span>
                            <p>No advisors available</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
        
        <!-- Farming Tips -->
        <?php if (!empty($farmingTips)): ?>
            <section class="module-section tips-section">
                <div class="section-header">
                    <h2>Farming Tips</h2>
                </div>
                <div class="section-content">
                    <div class="tips-grid">
                        <?php foreach ($farmingTips as $tip): ?>
                            <article class="tip-card">
                                <div class="tip-category"><?= e($tip['category'] ?? 'General') ?></div>
                                <h3><?= e($tip['title']) ?></h3>
                                <p><?= e($tip['content']) ?></p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>
        
        <!-- Quick Actions -->
        <section class="module-section actions-section">
            <h2>Quick Actions</h2>
            <div class="actions-grid">
                <a href="/rwanda_connect/modules/agriculture/farms.php" class="action-card">
                    <span class="action-icon">➕</span>
                    <h3>Add New Farm</h3>
                    <p>Register a new farm with location and soil details</p>
                </a>
                <a href="/rwanda_connect/modules/agriculture/crops.php" class="action-card">
                    <span class="action-icon">🌱</span>
                    <h3>Plant New Crop</h3>
                    <p>Start tracking a new crop cycle</p>
                </a>
                <a href="/rwanda_connect/modules/agriculture/market-prices.php" class="action-card">
                    <span class="action-icon">💰</span>
                    <h3>Check Prices</h3>
                    <p>View current market prices for your crops</p>
                </a>
                <a href="/rwanda_connect/modules/agriculture/calendar.php" class="action-card">
                    <span class="action-icon">📅</span>
                    <h3>Season Calendar</h3>
                    <p>View planting and harvesting schedules</p>
                </a>
                <a href="/rwanda_connect/modules/agriculture/crop-guide.php" class="action-card">
                    <span class="action-icon">📚</span>
                    <h3>Crop Guides</h3>
                    <p>Best practices for each crop type</p>
                </a>
                <a href="/rwanda_connect/modules/agriculture/advisors.php" class="action-card">
                    <span class="action-icon">👨‍🌾</span>
                    <h3>Ask an Expert</h3>
                    <p>Connect with agricultural advisors</p>
                </a>
            </div>
        </section>
    </div>
</main>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>