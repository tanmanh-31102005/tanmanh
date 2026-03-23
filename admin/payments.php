<?php
require_once '../view/config.php';
require_once '../includes/Util.php';
require_once '../includes/Database.php';
require_once '../models/Payment.php';

// Require admin authentication
Util::requireAuth();
Util::requireRole('admin');

$db = Database::getInstance();
$message = '';

// Build filter conditions
$where = ["1=1"];
$params = [];

if (!empty($_GET['search'])) {
    $search = '%' . Util::sanitizeInput($_GET['search']) . '%';
    $where[] = "(pb.name LIKE ? OR pk.name LIKE ?)";
    array_push($params, $search, $search);
}

if (!empty($_GET['status'])) {
    $status = Util::sanitizeInput($_GET['status']);
    if (in_array($status, ['pending', 'paid', 'failed'])) {
        $where[] = "p.status = ?";
        $params[] = $status;
    }
}

// Get payments with pagination
$page = max(1, filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$sql = "SELECT p.*,
        s.name as service_name,
        pb.name as brand_name, pb.avatar as brand_avatar,
        pk.name as kol_name, pk.avatar as kol_avatar
        FROM payments p
        JOIN bookings b ON p.booking_id = b.id
        JOIN services s ON b.service_id = s.id
        JOIN profiles pb ON b.brand_id = pb.user_id
        JOIN profiles pk ON b.kol_koc_id = pk.user_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?";

$countSql = "SELECT COUNT(*) as total 
             FROM payments p
             JOIN bookings b ON p.booking_id = b.id
             JOIN profiles pb ON b.brand_id = pb.user_id
             JOIN profiles pk ON b.kol_koc_id = pk.user_id
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

require_once '../includes/header.php';
?>

<link rel="stylesheet" href="../assets/css/admin2.css">

<div class="container">
    <div class="header-section">
        <h1>Quản lý thanh toán</h1>
        <a href="dashboard.php" class="btn back-btn">
            ← Quay lại
        </a>
    </div>

    <?php if ($message): ?>
        <div class="flash-message error">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <div class="filter-section">
        <form method="GET" action="" class="filter-form">
            <div class="form-group">
                <label for="search">Tìm kiếm</label>
                <input type="text" id="search" name="search" class="form-control"
                    value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                    placeholder="Tên KOL/KOC hoặc thương hiệu...">
            </div>

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

            <div class="form-group filter-form-button">
                <button type="submit" class="btn">Tìm kiếm</button>
            </div>
        </form>
    </div>

    <div class="users-table-container">
        <table class="users-table">
            <thead>
                <tr>
                    <th>Thương hiệu</th>
                    <th>KOL/KOC</th>
                    <th>Dịch vụ</th>
                    <th>Số tiền</th>
                    <th>Phương thức</th>
                    <th>Trạng thái</th>
                    <th>Ngày tạo</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td>
                            <div class="user-info">
                                <img src="<?= $payment['brand_avatar'] ? '../Uploads/avatars/' . $payment['brand_avatar'] : '../assets/images/default-avatar.png' ?>"
                                     alt="<?= htmlspecialchars($payment['brand_name']) ?>"
                                     class="user-avatar">
                                <div>
                                    <strong><?= htmlspecialchars($payment['brand_name']) ?></strong>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="user-info">
                                <img src="<?= $payment['kol_avatar'] ? '../Uploads/avatars/' . $payment['kol_avatar'] : '../assets/images/default-avatar.png' ?>"
                                     alt="<?= htmlspecialchars($payment['kol_name']) ?>"
                                     class="user-avatar">
                                <div>
                                    <strong><?= htmlspecialchars($payment['kol_name']) ?></strong>
                                </div>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($payment['service_name']) ?></td>
                        <td><?= number_format($payment['amount'] ?? 0) ?> VNĐ</td>
                        <td>
                            <?php
                            switch($payment['payment_method']) {
                                case 'bank_transfer':
                                    echo 'Chuyển khoản';
                                    break;
                                case 'momo':
                                    echo 'MoMo';
                                    break;
                                case 'zalopay':
                                    echo 'ZaloPay';
                                    break;
                            }
                            ?>
                        </td>
                        <td>
                            <span class="status-badge status-badge-<?= $payment['status'] ?>">
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
                        <td>
                            <?= date('d/m/Y H:i', strtotime($payment['created_at'])) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($payments)): ?>
                    <tr>
                        <td colspan="7" class="empty-state">
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
                    <a href="<?= $urlPrefix ?>page=<?= $page - 1 ?>" class="btn">
                        ← Trang trước
                    </a>
                <?php endif; ?>

                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                
                if ($startPage > 1) {
                    echo '<a href="' . $urlPrefix . 'page=1" class="btn">1</a>';
                    if ($startPage > 2) {
                        echo '<span class="pagination-ellipsis">...</span>';
                    }
                }

                for ($i = $startPage; $i <= $endPage; $i++) {
                    if ($i === $page) {
                        echo '<span class="btn btn-active">' . $i . '</span>';
                    } else {
                        echo '<a href="' . $urlPrefix . 'page=' . $i . '" class="btn">' . $i . '</a>';
                    }
                }

                if ($endPage < $totalPages) {
                    if ($endPage < $totalPages - 1) {
                        echo '<span class="pagination-ellipsis">...</span>';
                    }
                    echo '<a href="' . $urlPrefix . 'page=' . $totalPages . '" class="btn">' . $totalPages . '</a>';
                }
                ?>

                <?php if ($page < $totalPages): ?>
                    <a href="<?= $urlPrefix ?>page=<?= $page + 1 ?>" class="btn">
                        Trang sau →
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>