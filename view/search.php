<?php
require_once 'config.php';
require_once '../includes/Util.php';
require_once '../models/User.php';

// Require brand authentication
Util::requireAuth();
Util::requireRole('brand');

$db = Database::getInstance();

// Get all categories for filter
$categoriesQuery = $db->prepare("SELECT id, name FROM categories ORDER BY name");
$categoriesQuery->execute();
$categories = $categoriesQuery->fetchAll();

// Build search query
$where = ["u.role = 'kol_koc' AND u.status = 'active'"];
$having = [];
$params = [];

// Apply filters
if (!empty($_GET['search'])) {
    $searchTerm = '%' . Util::sanitizeInput($_GET['search']) . '%';
    $where[] = "(p.name LIKE ? OR p.bio LIKE ? OR p.industry LIKE ?)";
    array_push($params, $searchTerm, $searchTerm, $searchTerm);
}

if (!empty($_GET['category'])) {
    $categoryId = filter_var($_GET['category'], FILTER_VALIDATE_INT);
    if ($categoryId) {
        $where[] = "EXISTS (
            SELECT 1 FROM user_categories uc 
            WHERE uc.user_id = u.id AND uc.category_id = ?
        )";
        $params[] = $categoryId;
    }
}

if (!empty($_GET['min_followers'])) {
    $minFollowers = filter_var($_GET['min_followers'], FILTER_VALIDATE_INT);
    if ($minFollowers) {
        $where[] = "p.followers >= ?";
        $params[] = $minFollowers;
    }
}

if (!empty($_GET['min_rating'])) {
    $minRating = filter_var($_GET['min_rating'], FILTER_VALIDATE_FLOAT);
    if ($minRating) {
        $having[] = "COALESCE(AVG(r.rating), 0) >= ?";
        $params[] = $minRating;
    }
}

// Build and execute the final query
$sql = "SELECT u.id, u.email, p.name, p.avatar, p.bio, p.industry, p.followers,
        COUNT(DISTINCT b.id) as total_bookings,
        COALESCE(AVG(r.rating), 0) as avg_rating,
        GROUP_CONCAT(DISTINCT c.name) as categories
        FROM users u
        JOIN profiles p ON u.id = p.user_id
        LEFT JOIN bookings b ON u.id = b.kol_koc_id
        LEFT JOIN reviews r ON u.id = r.target_id
        LEFT JOIN user_categories uc ON u.id = uc.user_id
        LEFT JOIN categories c ON uc.category_id = c.id
        WHERE " . implode(' AND ', $where) . "
        GROUP BY u.id" .
        (!empty($having) ? " HAVING " . implode(' AND ', $having) : "") . "
        ORDER BY " . 
        (!empty($_GET['sort']) && $_GET['sort'] === 'followers' ? 'p.followers DESC' : 'avg_rating DESC');

$stmt = $db->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll();

require_once '../includes/header.php';
?>
<link rel="stylesheet" href="../assets/css/brand.css">
<div class="search-container">
    <h1 class="search-title">Tìm kiếm KOL/KOC</h1>

    <div class="search-form-container">
        <form method="GET" action="" class="search-form-grid">
            <div class="form-group">
                <label for="search">Tìm kiếm</label>
                <input type="text" id="search" name="search" class="form-control"
                    value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                    placeholder="Tên, ngành nghề...">
            </div>

            <div class="form-group">
                <label for="category">Lĩnh vực</label>
                <select id="category" name="category" class="form-control">
                    <option value="">Tất cả lĩnh vực</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['id'] ?>"
                            <?= (isset($_GET['category']) && $_GET['category'] == $category['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="min_followers">Số followers tối thiểu</label>
                <input type="number" id="min_followers" name="min_followers" class="form-control"
                    value="<?= htmlspecialchars($_GET['min_followers'] ?? '') ?>"
                    placeholder="VD: 10000">
            </div>

            <div class="form-group">
                <label for="min_rating">Đánh giá tối thiểu</label>
                <select id="min_rating" name="min_rating" class="form-control">
                    <option value="">Tất cả đánh giá</option>
                    <?php for ($i = 4; $i >= 1; $i--): ?>
                        <option value="<?= $i ?>"
                            <?= (isset($_GET['min_rating']) && $_GET['min_rating'] == $i) ? 'selected' : '' ?>>
                            <?= $i ?>+ sao
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="sort">Sắp xếp theo</label>
                <select id="sort" name="sort" class="form-control">
                    <option value="rating" <?= (!isset($_GET['sort']) || $_GET['sort'] === 'rating') ? 'selected' : '' ?>>
                        Đánh giá cao nhất
                    </option>
                    <option value="followers" <?= (isset($_GET['sort']) && $_GET['sort'] === 'followers') ? 'selected' : '' ?>>
                        Followers nhiều nhất
                    </option>
                </select>
            </div>

            <div class="search-button-container">
                <button type="submit" class="btn full-width-button">Tìm kiếm</button>
            </div>
        </form>
    </div>

    <?php if (empty($results)): ?>
        <p class="search-no-results">
            Không tìm thấy KOL/KOC nào phù hợp với tiêu chí tìm kiếm
        </p>
    <?php else: ?>
        <div class="search-results-grid">
            <?php foreach ($results as $kol): ?>
                <div class="kol-card"
                onmouseover="this.style.transform='translateY(-5px)'"
                onmouseout="this.style.transform='translateY(0)'">
                    <div class="kol-image-container">
                        <img src="<?= $kol['avatar'] ? '../uploads/avatars/' . $kol['avatar'] : Util::getImagePlaceholder() ?>"
                             alt="<?= htmlspecialchars($kol['name'] ?? '') ?>"
                             class="kol-image">
                    </div>
                    <div class="kol-info">
                        <h3 class="kol-name"><?= htmlspecialchars($kol['name'] ?? '') ?></h3>
                        <p class="kol-industry">
                            <?= htmlspecialchars($kol['industry'] ?? '') ?>
                        </p>
                        
                        <?php if ($kol['categories']): ?>
                            <div class="kol-categories">
                                <?php foreach (explode(',', $kol['categories']) as $category): ?>
                                    <span class="category-tag">
                                        <?= htmlspecialchars($category) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <p class="kol-bio">
                            <?= htmlspecialchars($kol['bio'] ?? '') ?>
                        </p>

                        <div class="kol-stats">
                            <div>
                                <span class="kol-followers-count"><?= number_format((int)($kol['followers'] ?? 0)) ?></span>
                                <span class="kol-followers-label"> followers</span>
                            </div>
                            <div>
                                <span class="kol-rating-value">★ <?= number_format((float)($kol['avg_rating'] ?? 0), 1) ?></span>
                                <span class="kol-rating-max">/5</span>
                            </div>
                        </div>

                        <a href="profile.php?id=<?= $kol['id'] ?>" class="btn profile-link">
                            Xem hồ sơ
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>