<?php
require_once '../view/config.php';
require_once '../includes/Util.php';
require_once '../models/User.php';

// Require admin authentication
Util::requireAuth();
Util::requireRole('admin');

$db = Database::getInstance();
$message = '';

// Handle user actions (status updates and deletion)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = filter_var($_POST['user_id'] ?? 0, FILTER_VALIDATE_INT);
    $action = Util::sanitizeInput($_POST['action'] ?? '');
    
    if ($action === 'delete' && $userId) {
        $result = User::delete($userId);
        if ($result['success']) {
            Util::flashMessage('success', 'Đã xóa người dùng thành công');
            Util::redirect('../admin/users.php');
        } else {
            $message = 'Không thể xóa người dùng: ' . ($result['message'] ?? '');
        }
    } else {
        $newStatus = Util::sanitizeInput($_POST['status'] ?? '');
        if ($userId && in_array($newStatus, ['active', 'pending', 'locked'])) {
            try {
                $stmt = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
                $result = $stmt->execute([$newStatus, $userId]);
                
                if ($result) {
                    Util::flashMessage('success', 'Cập nhật trạng thái thành công');
                    Util::redirect('../admin/users.php');
                } else {
                    $message = 'Không thể cập nhật trạng thái';
                }
            } catch (PDOException $e) {
                $message = 'Lỗi cập nhật: ' . $e->getMessage();
            }
        }
    }
}

// Build filter conditions
$where = ["1=1"];
$params = [];

if (!empty($_GET['search'])) {
    $search = '%' . Util::sanitizeInput($_GET['search']) . '%';
    $where[] = "(u.email LIKE ? OR p.name LIKE ?)";
    array_push($params, $search, $search);
}

if (!empty($_GET['role'])) {
    $role = Util::sanitizeInput($_GET['role']);
    if (in_array($role, ['admin', 'brand', 'kol_koc'])) {
        $where[] = "u.role = ?";
        $params[] = $role;
    }
}

if (!empty($_GET['status'])) {
    $status = Util::sanitizeInput($_GET['status']);
    if (in_array($status, ['active', 'pending', 'locked'])) {
        $where[] = "u.status = ?";
        $params[] = $status;
    }
}

// Lấy người dùng với phân trang
$page = max(1, filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$sql = "SELECT u.*, p.name, p.avatar,
        COUNT(DISTINCT b.id) as total_bookings,
        COUNT(DISTINCT s.id) as total_services
        FROM users u
        LEFT JOIN profiles p ON u.id = p.user_id
        LEFT JOIN bookings b ON (u.role = 'brand' AND u.id = b.brand_id) 
            OR (u.role = 'kol_koc' AND u.id = b.kol_koc_id)
        LEFT JOIN services s ON u.id = s.user_id
        WHERE " . implode(' AND ', $where) . "
        GROUP BY u.id
        ORDER BY u.created_at DESC
        LIMIT ? OFFSET ?";

$countSql = "SELECT COUNT(*) as total 
             FROM users u 
             LEFT JOIN profiles p ON u.id = p.user_id 
             WHERE " . implode(' AND ', $where);
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalUsers = $countStmt->fetch()['total'];
$totalPages = ceil($totalUsers / $perPage);

$params[] = $perPage;
$params[] = $offset;
$stmt = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<link rel="stylesheet" href="../assets/css/admin4.css">

<div class="container">
    <div class="header-container">
        <h1>Quản lý người dùng</h1>
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
                    placeholder="Email hoặc tên...">
            </div>

            <div class="form-group">
                <label for="role">Vai trò</label>
                <select id="role" name="role" class="form-control">
                    <option value="">Tất cả vai trò</option>
                    <option value="admin" <?= (isset($_GET['role']) && $_GET['role'] === 'admin') ? 'selected' : '' ?>>
                        Admin
                    </option>
                    <option value="brand" <?= (isset($_GET['role']) && $_GET['role'] === 'brand') ? 'selected' : '' ?>>
                        Thương hiệu
                    </option>
                    <option value="kol_koc" <?= (isset($_GET['role']) && $_GET['role'] === 'kol_koc') ? 'selected' : '' ?>>
                        KOL/KOC
                    </option>
                </select>
            </div>

            <div class="form-group">
                <label for="status">Trạng thái</label>
                <select id="status" name="status" class="form-control">
                    <option value="">Tất cả trạng thái</option>
                    <option value="active" <?= (isset($_GET['status']) && $_GET['status'] === 'active') ? 'selected' : '' ?>>
                        Hoạt động
                    </option>
                    <option value="pending" <?= (isset($_GET['status']) && $_GET['status'] === 'pending') ? 'selected' : '' ?>>
                        Chờ duyệt
                    </option>
                    <option value="locked" <?= (isset($_GET['status']) && $_GET['status'] === 'locked') ? 'selected' : '' ?>>
                        Đã khóa
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
                    <th>Người dùng</th>
                    <th>Vai trò</th>
                    <th>Trạng thái</th>
                    <th>Thống kê</th>
                    <th>Ngày đăng ký</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td>
                            <div class="user-info">
                                <img src="<?= $user['avatar'] ? '../uploads/avatars/' . $user['avatar'] : '../assets/images/default-avatar.png' ?>"
                                     alt="<?= htmlspecialchars($user['name'] ?: $user['email']) ?>"
                                     class="user-avatar">
                                <div>
                                    <div>
                                        <strong><?= htmlspecialchars($user['name'] ?: $user['email']) ?></strong>
                                    </div>
                                    <div class="user-email">
                                        <?= htmlspecialchars($user['email']) ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="role-badge">
                                <?= $user['role'] === 'kol_koc' ? 'KOL/KOC' : ucfirst($user['role']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-badge status-badge-<?= $user['status'] ?>">
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
                        <td>
                            <?php if ($user['role'] === 'brand'): ?>
                                <?= $user['total_bookings'] ?> đơn đặt
                            <?php elseif ($user['role'] === 'kol_koc'): ?>
                                <?= $user['total_services'] ?> dịch vụ<br>
                                <?= $user['total_bookings'] ?> đơn nhận
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= date('d/m/Y', strtotime($user['created_at'])) ?>
                        </td>
                        <td>
                            <div class="dropdown">
                                <button onclick="toggleDropdown(<?= $user['id'] ?>)" class="btn dropdown-btn">
                                    Thao tác ▼
                                </button>
                                <div id="dropdown-<?= $user['id'] ?>" class="dropdown-menu">
                                    <?php if ($user['status'] === 'pending'): ?>
                                        <form method="POST" action="" style="margin-bottom: 0.5rem;">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <input type="hidden" name="status" value="active">
                                            <button type="submit" class="dropdown-item" 
                                                onmouseover="this.style.backgroundColor='var(--accent-color)'"
                                                onmouseout="this.style.backgroundColor='transparent'">
                                                Phê duyệt
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <?php if ($user['status'] !== 'locked'): ?>
                                        <form method="POST" action="" style="margin-bottom: 0.5rem;">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <input type="hidden" name="status" value="locked">
                                            <button type="submit" class="dropdown-item dropdown-item-danger" 
                                                onmouseover="this.style.backgroundColor='var(--error-color)';this.style.color='white'"
                                                onmouseout="this.style.backgroundColor='transparent';this.style.color='var(--error-color)'">
                                                Khóa tài khoản
                                            </button>
                                        </form>
                                        <form method="POST" action="" onsubmit="return confirm('Bạn có chắc chắn muốn xóa người dùng này? Hành động này không thể hoàn tác.');" style="margin-bottom: 0.5rem;">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="dropdown-item dropdown-item-danger" 
                                                onmouseover="this.style.backgroundColor='var(--error-color)';this.style.color='white'"
                                                onmouseout="this.style.backgroundColor='transparent';this.style.color='var(--error-color)'">
                                                Xóa tài khoản
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" action="">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <input type="hidden" name="status" value="active">
                                            <button type="submit" class="dropdown-item dropdown-item-success" 
                                                onmouseover="this.style.backgroundColor='var(--success-color)';this.style.color='white'"
                                                onmouseout="this.style.backgroundColor='transparent';this.style.color='var(--success-color)'">
                                                Mở khóa
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
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

<script>
function toggleDropdown(userId) {
    const dropdown = document.getElementById(`dropdown-${userId}`);
    const allDropdowns = document.querySelectorAll('.dropdown-menu');
    const dropdownBtn = document.querySelector(`#dropdown-${userId}`).parentElement.querySelector('.dropdown-btn');
    const rect = dropdownBtn.getBoundingClientRect();
    const windowHeight = window.innerHeight;

    // Close all other dropdowns
    allDropdowns.forEach(d => {
        if (d !== dropdown && d.style.display === 'block') {
            d.style.display = 'none';
        }
    });

    // Toggle current dropdown
    if (dropdown.style.display === 'block') {
        dropdown.style.display = 'none';
    } else {
        dropdown.style.display = 'block';
        const dropdownRect = dropdown.getBoundingClientRect();
        if (rect.bottom + dropdownRect.height > windowHeight) {
            dropdown.style.bottom = `${rect.height}px`;
            dropdown.style.top = 'auto';
        } else {
            dropdown.style.top = `${rect.height}px`;
            dropdown.style.bottom = 'auto';
        }
        dropdown.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

// Close dropdowns when clicking outside
document.addEventListener('click', (e) => {
    if (!e.target.closest('.dropdown')) {
        document.querySelectorAll('.dropdown-menu').forEach(d => {
            d.style.display = 'none';
        });
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>