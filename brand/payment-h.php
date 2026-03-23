<?php
require_once '../view/config.php';
require_once '../includes/Util.php';
require_once '../models/Payment.php';

// Require brand authentication
Util::requireAuth();
Util::requireRole('brand');

$db = Database::getInstance();
$message = '';

// Build filter conditions
$where = ["b.brand_id = ?"];
$params = [$_SESSION['user_id']];

if (!empty($_GET['status'])) {
    $status = Util::sanitizeInput($_GET['status']);
    if (in_array($status, ['pending', 'paid', 'failed'])) {
        $where[] = "p.status = ?";
        $params[] = $status;
    }
}

// Get payments with pagination
$page = max(1, filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$sql = "SELECT p.*,
        s.name as service_name,
        k.name as kol_name, k.avatar as kol_avatar
        FROM payments p
        JOIN bookings b ON p.booking_id = b.id
        JOIN services s ON b.service_id = s.id
        JOIN profiles k ON b.kol_koc_id = k.user_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?";

$countSql = "SELECT COUNT(*) as total 
             FROM payments p
             JOIN bookings b ON p.booking_id = b.id
             WHERE " . implode(' AND ', $where);

$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalPayments = $countStmt->fetch()['total'];
$totalPages = ceil($totalPayments / $perPage);

$params[] = $perPage;
$params[] = $offset;
$stmt = $db->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll();

// Get statistics
$stats = [
    'total_paid' => (function() use ($db) {
        $stmt = $db->prepare("
            SELECT COALESCE(SUM(p.amount), 0) as total
            FROM payments p
            JOIN bookings b ON p.booking_id = b.id
            WHERE b.brand_id = ? AND p.status = 'paid'
        ");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch()['total'];
    })(),
    'pending_amount' => (function() use ($db) {
        $stmt = $db->prepare("
            SELECT COALESCE(SUM(p.amount), 0) as total
            FROM payments p
            JOIN bookings b ON p.booking_id = b.id
            WHERE b.brand_id = ? AND p.status = 'pending'
        ");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch()['total'];
    })(),
    'total_bookings' => (function() use ($db) {
        $stmt = $db->prepare("
            SELECT COUNT(*) as count
            FROM bookings
            WHERE brand_id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch()['count'];
    })()
];

require_once '../includes/header.php';
?>

<!-- Link to the CSS file -->
<link rel="stylesheet" href="../assets/css/payment.css">

<div class="payment-history-container">
    <div class="page-header">
        <h1>Lịch sử thanh toán</h1>
        <a href="../view/bookings.php" class="btn back-btn">
            ← Quay lại
        </a>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <h3>Tổng đã thanh toán</h3>
            <p class="stat-value stat-value-success">
                <?= number_format($stats['total_paid']) ?> VNĐ
            </p>
        </div>

        <div class="stat-card">
            <h3>Chờ thanh toán</h3>
            <p class="stat-value stat-value-pending">
                <?= number_format($stats['pending_amount']) ?> VNĐ
            </p>
        </div>

        <div class="stat-card">
            <h3>Tổng đơn đặt</h3>
            <p class="stat-value stat-value-accent">
                <?= number_format($stats['total_bookings']) ?>
            </p>
        </div>
    </div>

    <div class="card" style="margin-bottom: 2rem;">
        <div class="card-body">
            <form method="GET" action="" class="filter-form">
                <div class="form-group">
                    <label for="status">Trạng thái</label>
                    <select id="status" name="status" class="form-control">
                        <option value="">Tất cả trạng thái</option>
                        <option value="pending" <?= (isset($_GET['status']) && $_GET['status'] === 'pending') ? 'selected' : '' ?>>
                            Chờ thanh toán
                        </option>
                        <option value="paid" <?= (isset($_GET['status']) && $_GET['status'] === 'paid') ? 'selected' : '' ?>>
                            Đã thanh toán
                        </option>
                        <option value="failed" <?= (isset($_GET['status']) && $_GET['status'] === 'failed') ? 'selected' : '' ?>>
                            Thất bại
                        </option>
                    </select>
                </div>

                <div class="filter-button-group">
                    <button type="submit" class="btn">Lọc</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <table class="payment-table">
            <thead>
                <tr>
                    <th>KOL/KOC</th>
                    <th>Dịch vụ</th>
                    <th>Số tiền</th>
                    <th>Trạng thái</th>
                    <th>Ngày tạo</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td>
                            <div class="flex flex-center gap-0-5">
                                <img src="<?= $payment['kol_avatar'] ? '../uploads/avatars/' . $payment['kol_avatar'] : '../assets/images/default-avatar.png' ?>"
                                     alt="<?= htmlspecialchars($payment['kol_name']) ?>"
                                     class="avatar-sm">
                                <span><?= htmlspecialchars($payment['kol_name']) ?></span>
                            </div>
                        </td>
                        <td class="center-cell">
                            <?= htmlspecialchars($payment['service_name']) ?>
                        </td>
                        <td class="amount-cell">
                            <?= number_format($payment['amount']) ?> VNĐ
                        </td>
                        <td class="center-cell">
                            <span class="payment-status <?php
                                switch($payment['status']) {
                                    case 'paid':
                                        echo 'status-paid';
                                        break;
                                    case 'pending':
                                        echo 'status-pending';
                                        break;
                                    case 'failed':
                                        echo 'status-failed';
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
                        <td class="center-cell">
                            <?= date('d/m/Y H:i', strtotime($payment['created_at'])) ?>
                        </td>
                        <td class="center-cell">
                            <?php if ($payment['status'] === 'pending'): ?>
                                <a href="../view/payment.php?booking_id=<?= $payment['booking_id'] ?>" 
                                   class="btn" style="background-color: var(--accent-color);">
                                    Thanh toán ngay
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($payments)): ?>
                    <tr>
                        <td colspan="6" class="empty-table-message">
                            Không có thanh toán nào
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php
                $queryParams = $_GET;
                unset($queryParams['page']);
                $queryString = http_build_query($queryParams);
                $urlPrefix = '?' . ($queryString ? $queryString . '&' : '');
                ?>
                
                <?php if ($page > 1): ?>
                    <a href="<?= $urlPrefix ?>page=<?= $page - 1 ?>" class="btn page-btn">
                        ← Trang trước
                    </a>
                <?php endif; ?>

                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                
                if ($startPage > 1) {
                    echo '<a href="' . $urlPrefix . 'page=1" class="btn page-btn">1</a>';
                    if ($startPage > 2) {
                        echo '<span class="pagination-ellipsis">...</span>';
                    }
                }

                for ($i = $startPage; $i <= $endPage; $i++) {
                    if ($i === $page) {
                        echo '<span class="btn page-btn active-page">' . $i . '</span>';
                    } else {
                        echo '<a href="' . $urlPrefix . 'page=' . $i . '" class="btn page-btn">' . $i . '</a>';
                    }
                }

                if ($endPage < $totalPages) {
                    if ($endPage < $totalPages - 1) {
                        echo '<span class="pagination-ellipsis">...</span>';
                    }
                    echo '<a href="' . $urlPrefix . 'page=' . $totalPages . '" class="btn page-btn">' . $totalPages . '</a>';
                }
                ?>

                <?php if ($page < $totalPages): ?>
                    <a href="<?= $urlPrefix ?>page=<?= $page + 1 ?>" class="btn page-btn">
                        Trang sau →
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>