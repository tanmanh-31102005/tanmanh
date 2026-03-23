<?php
require_once 'config.php';
require_once '../includes/Util.php';
require_once '../models/User.php';
require_once '../models/Settings.php';

// Get featured KOL/KOCs (pinned first, then top rated)
function getFeaturedKolKocs($limit = 6) {
    $db = Database::getInstance();
    
    // Get pinned KOLs first
    $pinnedIds = json_decode(Settings::get('pinned_kols') ?? '[]', true);
    $pinnedKols = [];
    
    if (!empty($pinnedIds)) {
        $placeholders = str_repeat('?,', count($pinnedIds) - 1) . '?';
        $sql = "SELECT u.id, u.email, p.name, p.avatar, p.bio, p.industry, p.followers,
                COUNT(DISTINCT b.id) as total_bookings,
                AVG(CASE WHEN r.rating IS NOT NULL THEN r.rating ELSE NULL END) as avg_rating,
                GROUP_CONCAT(DISTINCT c.name) as categories
                FROM users u
                JOIN profiles p ON u.id = p.user_id
                LEFT JOIN bookings b ON u.id = b.kol_koc_id
                LEFT JOIN reviews r ON u.id = r.target_id
                LEFT JOIN user_categories uc ON u.id = uc.user_id
                LEFT JOIN categories c ON uc.category_id = c.id
                WHERE u.role = 'kol_koc' AND u.status = 'active' AND u.id IN ($placeholders)
                GROUP BY u.id
                ORDER BY FIELD(u.id, $placeholders)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([...array_values($pinnedIds), ...array_values($pinnedIds)]);
        $pinnedKols = $stmt->fetchAll();
    }
    
    // Get remaining top rated KOLs
    $remainingLimit = $limit - count($pinnedKols);
    if ($remainingLimit > 0) {
        $notInClause = !empty($pinnedIds) ? "AND u.id NOT IN (" . str_repeat('?,', count($pinnedIds) - 1) . "?)" : "";
        
        $sql = "SELECT u.id, u.email, p.name, p.avatar, p.bio, p.industry, p.followers,
                COUNT(DISTINCT b.id) as total_bookings,
                AVG(CASE WHEN r.rating IS NOT NULL THEN r.rating ELSE NULL END) as avg_rating,
                GROUP_CONCAT(DISTINCT c.name) as categories
                FROM users u
                JOIN profiles p ON u.id = p.user_id
                LEFT JOIN bookings b ON u.id = b.kol_koc_id
                LEFT JOIN reviews r ON u.id = r.target_id
                LEFT JOIN user_categories uc ON u.id = uc.user_id
                LEFT JOIN categories c ON uc.category_id = c.id
                WHERE u.role = 'kol_koc' AND u.status = 'active' $notInClause
                GROUP BY u.id
                ORDER BY avg_rating DESC, p.followers DESC
                LIMIT ?";
        
        $params = empty($pinnedIds) ? [$remainingLimit] : [...array_values($pinnedIds), $remainingLimit];
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $topKols = $stmt->fetchAll();
        
        return array_merge($pinnedKols, $topKols);
    }
    
    return $pinnedKols;
}

$featuredKolKocs = getFeaturedKolKocs();
$backgroundImage = Settings::get('homepage_background');
$blogContent = Settings::get('blog_content');

require_once '../includes/header.php';
?>
<link rel="stylesheet" href="../assets/css/index.css">
<div class="hero-section" style="
    background: <?= $backgroundImage
        ? "linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('../Uploads/backgrounds/" . htmlspecialchars($backgroundImage) . "')"
        : "linear-gradient(to right, var(--bg-primary), var(--bg-secondary))" ?>;
    background-size: cover;
    background-position: center;
    color: <?= $backgroundImage ? 'white' : 'inherit' ?>;
">
    <h1>Nền tảng kết nối Thương hiệu với KOL/KOC</h1>
    <p style="color: <?= $backgroundImage ? 'rgba(255,255,255,0.9)' : 'var(--text-secondary)' ?>;">
        Tìm kiếm và hợp tác với những người có tầm ảnh hưởng phù hợp với thương hiệu của bạn
    </p>
    <?php if (!Util::isAuthenticated()): ?>
        <div class="hero-buttons">
            <a href="register.php?role=brand" class="btn">Đăng ký là Thương hiệu</a>
            <a href="register.php?role=kol_koc" class="btn btn-outline">
                Đăng ký là KOL/KOC
            </a>
        </div>
    <?php elseif ($_SESSION['user_role'] === 'brand'): ?>
        <a href="search.php" class="btn">Tìm kiếm KOL/KOC</a>
    <?php endif; ?>
</div>

<div class="blog-section">
    <div class="container">
        <h2>Tin tức & Blog</h2>
        <div class="blog-content">
            <?php if ($blogContent): ?>
                <div class="blog-teaser">
                    <p>Khám phá những bài viết thú vị về KOL/KOC và các xu hướng mới nhất!</p>
                    <button class="btn blog-toggle-btn" onclick="toggleBlogContent()">Đọc Thêm</button>
                </div>
                <div class="blog-full-content" style="display: none;">
                    <?= $blogContent ?>
                    <button class="btn blog-toggle-btn" onclick="toggleBlogContent()">Thu Gọn</button>
                </div>
            <?php else: ?>
                <p class="empty-state">Chưa có nội dung blog. Vui lòng kiểm tra lại sau!</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="featured-section">
    <h2>KOL/KOC Nổi bật</h2>
    
    <div class="featured-grid">
        <?php foreach ($featuredKolKocs as $kol): ?>
            <div class="kol-card">
                <div class="kol-image">
                    <img src="<?= $kol['avatar'] ? '../Uploads/avatars/' . $kol['avatar'] : '../assets/images/default-avatar.png' ?>"
                         alt="<?= htmlspecialchars($kol['name'] ?? '') ?>">
                </div>
                <div class="kol-content">
                    <div class="kol-header">
                        <h3><?= htmlspecialchars($kol['name'] ?? '') ?></h3>
                        <?php if (in_array($kol['id'], json_decode(Settings::get('pinned_kols') ?? '[]', true))): ?>
                            <span class="featured-badge">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"></path>
                                </svg>
                                Đề xuất
                            </span>
                        <?php endif; ?>
                    </div>
                    <p class="kol-industry">
                        <?= htmlspecialchars($kol['industry'] ?? '') ?>
                    </p>
                    <p class="kol-bio">
                        <?= htmlspecialchars($kol['bio'] ?? '') ?>
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

                    <div class="kol-stats">
                        <div>
                            <span class="stat-value"><?= number_format((int)($kol['followers'] ?? 0)) ?></span>
                            <span class="stat-label"> followers</span>
                        </div>
                        <?php if (isset($kol['avg_rating'])): ?>
                            <div>
                                <span class="stat-value">★ <?= number_format((float)$kol['avg_rating'], 1) ?></span>
                                <span class="stat-label">/5</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (Util::isAuthenticated() && $_SESSION['user_role'] === 'brand'): ?>
                        <a href="profile.php?id=<?= $kol['id'] ?>" class="btn" style="display: block; text-align: center;">
                            Xem hồ sơ
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (count($featuredKolKocs) >= 6): ?>
        <div class="view-all">
            <a href="search.php" class="btn btn-outline">
                Xem tất cả KOL/KOC
            </a>
        </div>
    <?php endif; ?>
</div>

<div class="features-section">
    <div class="container">
        <h2>Tại sao chọn chúng tôi?</h2>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
                <h3 class="feature-title">Đa dạng KOL/KOC</h3>
                <p class="feature-description">
                    Hàng nghìn KOL/KOC từ nhiều lĩnh vực khác nhau
                </p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    </svg>
                </div>
                <h3 class="feature-title">An toàn & Bảo mật</h3>
                <p class="feature-description">
                    Mọi giao dịch đều được bảo vệ và giám sát
                </p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M8 14s1.5 2 4 2 4-2 4-2"></path>
                        <line x1="9" y1="9" x2="9.01" y2="9"></line>
                        <line x1="15" y1="9" x2="15.01" y2="9"></line>
                    </svg>
                </div>
                <h3 class="feature-title">Dễ dàng sử dụng</h3>
                <p class="feature-description">
                    Giao diện thân thiện, quy trình đơn giản
                </p>
            </div>
        </div>
    </div>
</div>

<script>
function toggleBlogContent() {
    const teaser = document.querySelector('.blog-teaser');
    const fullContent = document.querySelector('.blog-full-content');
    if (teaser.style.display === 'none') {
        teaser.style.display = 'block';
        fullContent.style.display =	'none';
    } else {
        teaser.style.display = 'none';
        fullContent.style.display = 'block';
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>