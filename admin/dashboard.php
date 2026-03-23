<?php
require_once '../view/config.php';
require_once '../includes/Util.php';
require_once '../models/User.php';

// Require admin authentication
Util::requireAuth();
Util::requireRole('admin');

$db = Database::getInstance();

// Get statistics
$stats = [
    'total_users' => $db->query("SELECT COUNT(*) as count FROM users")->fetch()['count'],
    'pending_users' => $db->query("SELECT COUNT(*) as count FROM users WHERE status = 'pending'")->fetch()['count'],
    'total_bookings' => $db->query("SELECT COUNT(*) as count FROM bookings")->fetch()['count'],
    'total_revenue' => $db->query("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE status = 'paid'")->fetch()['total'],
    'pending_payments' => $db->query("SELECT COUNT(*) as count FROM payments WHERE status = 'pending'")->fetch()['count']
];

require_once '../includes/header.php';
?>

<!-- Include admin CSS -->
<link rel="stylesheet" href="../assets/css/admin1.css">

<div class="dashboard-container">
    <h1 style="margin-bottom: 2rem;">Bảng điều khiển Admin</h1>

    <div class="stats-grid">
        <div class="card">
            <h3 class="card-title">Tổng người dùng</h3>
            <p class="card-value">
                <?= number_format($stats['total_users']) ?>
            </p>
        </div>

        <div class="card">
            <h3 class="card-title">Đợi duyệt</h3>
            <p class="card-value card-value-warning">
                <?= number_format($stats['pending_users']) ?>
            </p>
        </div>

        <div class="card">
            <h3 class="card-title">Tổng đơn đặt</h3>
            <p class="card-value">
                <?= number_format($stats['total_bookings']) ?>
            </p>
        </div>

        <div class="card">
            <h3 class="card-title">Doanh thu</h3>
            <p class="card-value card-value-success">
                <?= number_format($stats['total_revenue']) ?> VNĐ
            </p>
        </div>
    </div>

    <div class="content-grid">
        <div>
            <div class="btn-flex" style="margin-bottom: 2rem;">
                <a href="users.php" class="btn">Quản lý người dùng</a>
                <a href="categories.php" class="btn">Quản lý danh mục</a>
                <a href="payments.php" class="btn">Quản lý thanh toán</a>
                <a href="settings.php" class="btn">Cài đặt hệ thống</a>
                <a href="export_stats_pdf.php" class="btn btn-outline">Xuất PDF</a>
            </div>
        </div>
        <div>
            <div class="header-flex">
                <h2>Quản lý người dùng</h2>
                <a href="users.php" class="btn">Xem tất cả</a>
            </div>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Email</th>
                            <th>Vai trò</th>
                            <th>Trạng thái</th>
                            <th>Ngày đăng ký</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $users = $db->query("
                            SELECT u.*, p.name
                            FROM users u
                            LEFT JOIN profiles p ON u.id = p.user_id
                            ORDER BY u.created_at DESC
                            LIMIT 5
                        ")->fetchAll();

                        foreach ($users as $user):
                        ?>
                            <tr>
                                <td>
                                    <div>
                                        <strong><?= htmlspecialchars($user['name'] ?: $user['email']) ?></strong>
                                    </div>
                                    <div class="user-meta">
                                        <?= htmlspecialchars($user['email']) ?>
                                    </div>
                                </td>
                                <td class="center">
                                    <span class="badge badge-primary">
                                        <?= $user['role'] === 'kol_koc' ? 'KOL/KOC' : ucfirst($user['role']) ?>
                                    </span>
                                </td>
                                <td class="center">
                                    <span class="badge <?php
                                        switch($user['status']) {
                                            case 'active':
                                                echo 'badge-success';
                                                break;
                                            case 'pending':
                                                echo 'badge-warning';
                                                break;
                                            case 'locked':
                                                echo 'badge-error';
                                                break;
                                        }
                                    ?>">
                                        <?php
                                        switch($user['status']) {
                                            case 'active':
                                                echo 'Hoạt động';
                                                break;
                                            case 'pending':
                                                echo 'Chờ duyệt';
                                                break;
                                            case 'locked':
                                                echo 'Đã khóa';
                                                break;
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td class="center">
                                    <?= date('d/m/Y', strtotime($user['created_at'])) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div>
            <div class="header-flex">
                <h2>Quản lý danh mục</h2>
                <a href="categories.php" class="btn">Xem tất cả</a>
            </div>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Tên danh mục</th>
                            <th>Số KOL/KOC</th>
                            <th>Số đơn đặt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $categories = $db->query("
                            SELECT c.*, 
                                COUNT(DISTINCT uc.user_id) as total_users,
                                COUNT(DISTINCT b.id) as total_bookings
                            FROM categories c
                            LEFT JOIN user_categories uc ON c.id = uc.category_id
                            LEFT JOIN users u ON uc.user_id = u.id AND u.role = 'kol_koc'
                            LEFT JOIN bookings b ON u.id = b.kol_koc_id
                            GROUP BY c.id
                            ORDER BY total_users DESC
                            LIMIT 5
                        ")->fetchAll();

                        foreach ($categories as $category):
                        ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($category['name']) ?>
                                </td>
                                <td class="center">
                                    <?= number_format($category['total_users']) ?>
                                </td>
                                <td class="center">
                                    <?= number_format($category['total_bookings']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div>
            <div class="header-flex">
                <h2>Thanh toán gần đây</h2>
                <a href="payments.php" class="btn">Xem tất cả</a>
            </div>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Thông tin</th>
                            <th>Số tiền</th>
                            <th>Trạng thái</th>
                            <th>Thời gian</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $payments = $db->query("
                            SELECT p.*,
                                pb.name as brand_name, pb.avatar as brand_avatar,
                                pk.name as kol_name
                            FROM payments p
                            JOIN bookings b ON p.booking_id = b.id
                            JOIN profiles pb ON b.brand_id = pb.user_id
                            JOIN profiles pk ON b.kol_koc_id = pk.user_id
                            ORDER BY p.created_at DESC
                            LIMIT 5
                        ")->fetchAll();

                        foreach ($payments as $payment):
                        ?>
                            <tr>
                                <td>
                                    <div class="user-info">
                                        <img src="<?= $payment['brand_avatar'] ? '../uploads/avatars/' . $payment['brand_avatar'] : '../assets/images/default-avatar.png' ?>"
                                             alt="<?= htmlspecialchars($payment['brand_name']) ?>"
                                             class="user-avatar">
                                        <div>
                                            <div class="user-name">
                                                <?= htmlspecialchars($payment['brand_name']) ?>
                                            </div>
                                            <div class="user-meta">
                                                <?= htmlspecialchars($payment['kol_name']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="right">
                                    <?= number_format($payment['amount'] ?? 0) ?> VNĐ
                                </td>
                                <td class="center">
                                    <span class="badge <?php
                                        switch($payment['status']) {
                                            case 'paid':
                                                echo 'badge-success';
                                                break;
                                            case 'pending':
                                                echo 'badge-warning';
                                                break;
                                            case 'failed':
                                                echo 'badge-error';
                                                break;
                                        }
                                    ?>">
                                        <?php
                                        switch($payment['status']) {
                                            case 'paid':
                                                echo 'Đã thanh toán';
                                                break;
                                            case 'pending':
                                                echo 'Chờ thanh toán';
                                                break;
                                            case 'failed':
                                                echo 'Thất bại';
                                                break;
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td class="center">
                                    <?= date('d/m/Y H:i', strtotime($payment['created_at'])) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (empty($payments)): ?>
                            <tr>
                                <td colspan="4" class="table-empty">
                                    Chưa có thanh toán nào
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>