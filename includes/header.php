<?php
require_once __DIR__ . '/../view/config.php';
require_once __DIR__ . '/Util.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    // Xác định trang hiện tại dựa trên tên file
    $currentPage = basename($_SERVER['PHP_SELF']);
    $metaTitle = 'Thuê KOL/KOC Chuyên Nghiệp - Nền Tảng KOL Booking';
    $metaDescription = 'Kết nối thương hiệu với KOL TikTok, review quán ăn, thời trang. Đặt lịch dễ dàng, giá từ 100,000 VNĐ.';

    switch ($currentPage) {
        case 'index.php':
            // Trang chủ
            $metaTitle = 'Thuê KOL/KOC Chuyên Nghiệp - Nền Tảng KOL Booking';
            $metaDescription = 'Kết nối thương hiệu với KOL TikTok, review quán ăn, thời trang. Đặt lịch dễ dàng, giá từ 100,000 VNĐ.';
            break;
        case 'profile.php':
            // Trang hồ sơ KOL
            require_once __DIR__ . '/../models/User.php';
            $profileId = $_GET['id'] ?? ($_SESSION['user_id'] ?? 0);
            $user = User::getById($profileId);
            if ($user) {
                $metaTitle = htmlspecialchars($user['name']) . ' - KOL TikTok ' . htmlspecialchars($user['industry'] ?? '');
                $metaDescription = 'Thuê ' . htmlspecialchars($user['name']) . ' cho dịch vụ KOL TikTok, review sản phẩm. Đánh giá ' . number_format($user['avg_rating'] ?? 0, 1) . '/5, giá từ 100,000 VNĐ.';
            }
            break;
        case 'services.php':
            // Trang quản lý dịch vụ (hoặc tạo public_services.php)
            $metaTitle = 'Dịch Vụ KOL/KOC - Nhảy TikTok, Review Quán Ăn';
            $metaDescription = 'Khám phá dịch vụ KOL/KOC như nhảy TikTok, review quán ăn, thời trang. Giá từ 100,000 VNĐ.';
            break;
        case 'booking.php':
            // Trang đặt dịch vụ
            require_once __DIR__ . '/../models/User.php';
            $kolKocId = filter_var($_GET['kol_koc_id'] ?? 0, FILTER_VALIDATE_INT);
            $kolKoc = User::getById($kolKocId);
            if ($kolKoc) {
                $metaTitle = 'Đặt Dịch Vụ với ' . htmlspecialchars($kolKoc['name']) . ' - KOL TikTok';
                $metaDescription = 'Thuê ' . htmlspecialchars($kolKoc['name']) . ' cho dịch vụ nhảy TikTok, review sản phẩm. Giá từ 100,000 VNĐ.';
            }
            break;
        case 'bookings.php':
            // Trang quản lý đơn đặt
            $metaTitle = 'Quản Lý Đơn Đặt KOL/KOC';
            $metaDescription = 'Theo dõi và quản lý đơn đặt dịch vụ KOL/KOC như nhảy TikTok, review quán ăn.';
            break;
        case 'search.php':
            // Trang tìm kiếm
            $metaTitle = 'Tìm KOL/KOC - Food, Fashion, Travel';
            $metaDescription = 'Tìm kiếm KOL/KOC chuyên nghiệp trong lĩnh vực Food, Fashion, Travel. Đặt dịch vụ giá từ 100,000 VNĐ.';
            if (!empty($_GET['category'])) {
                $categoryId = filter_var($_GET['category'], FILTER_VALIDATE_INT);
                if ($categoryId) {
                    $stmt = $db->prepare("SELECT name FROM categories WHERE id = ?");
                    $stmt->execute([$categoryId]);
                    $category = $stmt->fetch();
                    if ($category) {
                        $metaTitle = 'Tìm KOL ' . htmlspecialchars($category['name']) . ' - KOL Booking';
                        $metaDescription = 'Khám phá KOL chuyên về ' . htmlspecialchars($category['name']) . '. Đặt dịch vụ giá từ 100,000 VNĐ.';
                    }
                }
            }
            break;
    }
    ?>
    <title><?php echo $metaTitle; ?></title>
    <meta name="description" content="<?php echo $metaDescription; ?>">
    <!-- Thêm meta keywords (tùy chọn, ít quan trọng) -->
    <meta name="keywords" content="thuê KOL, KOL TikTok, review quán ăn, KOL Fashion, KOL Travel">
    <!-- Thêm canonical URL -->
    <link rel="canonical" href="https://yourwebsite.com<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
    <!-- Open Graph cho mạng xã hội -->
    <meta property="og:title" content="<?php echo $metaTitle; ?>">
    <meta property="og:description" content="<?php echo $metaDescription; ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://yourwebsite.com<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
    <meta property="og:image" content="https://yourwebsite.com/assets/images/og-image.jpg">
    <!-- Twitter Cards -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo $metaTitle; ?>">
    <meta name="twitter:description" content="<?php echo $metaDescription; ?>">
    <meta name="twitter:image" content="https://yourwebsite.com/assets/images/og-image.jpg">
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/images/favicon.png">
    <style>
        :root {
            --bg-primary: #1a1a1a;
            --bg-secondary: #252525;
            --text-primary: #ffffff;
            --text-secondary: #b3b3b3;
            --accent-color: #7c3aed;
            --accent-hover: #6d28d9;
            --error-color: #ef4444;
            --success-color: #10b981;
            --border-color: #404040;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        header {
            background-color: var(--bg-secondary);
            padding: 1rem 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        nav .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--accent-color);
            text-decoration: none;
        }

        nav ul {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        nav a {
            color: var(--text-primary);
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            transition: all 0.3s ease;
        }

        nav a:hover {
            background-color: var(--accent-color);
        }

        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            background-color: var(--accent-color);
            color: white;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn:hover {
            background-color: var(--accent-hover);
        }

        .flash-message {
            padding: 1rem;
            border-radius: 0.375rem;
            margin: 1rem 0;
        }

        .flash-message.success {
            background-color: var(--success-color);
            color: white;
        }

        .flash-message.error {
            background-color: var(--error-color);
            color: white;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-secondary);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 0.375rem;
            background-color: var(--bg-secondary);
            color: var(--text-primary);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent-color);
        }

        @media (max-width: 768px) {
            nav ul {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background-color: var(--bg-secondary);
                padding: 1rem;
                flex-direction: column;
                gap: 1rem;
            }

            nav ul.active {
                display: flex;
            }

            .mobile-menu-btn {
                display: block;
            }
        }
    </style>
    

</head>
<body>
<header>
    <nav class="container">
        <a href="../view" class="logo">KOL/KOC Booking</a>
        <ul>
            <?php if (Util::isAuthenticated()): ?>
                <?php if ($_SESSION['user_role'] === 'brand'): ?>
                    <li><a href="../view/search.php">Tìm KOL/KOC</a></li>
                    <li><a href="../view/bookings.php">Đơn đặt</a></li>
                    <li><a href="../view/profile.php">Hồ sơ</a></li>
                <?php elseif ($_SESSION['user_role'] === 'kol_koc'): ?>
                    <li><a href="../view/services.php">Dịch vụ</a></li>
                    <li><a href="../view/bookings.php">Đơn đặt</a></li>
                    <li><a href="../view/profile.php">Hồ sơ</a></li>
                <?php elseif ($_SESSION['user_role'] === 'admin'): ?>
                    <li><a href="../admin/dashboard.php">Quản trị</a></li>
                <?php endif; ?>
                <li><a href="../view/logout.php">Đăng xuất</a></li>
            <?php else: ?>
                <li><a href="../view/login.php">Đăng nhập</a></li>
                <li><a href="../view/register.php">Đăng ký</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>
    <main class="container">
        <?php
        $flash = Util::getFlashMessage();
        if ($flash): ?>
            <div class="flash-message <?= $flash['type'] ?>">
                <?= $flash['message'] ?>
            </div>
        <?php endif; ?>