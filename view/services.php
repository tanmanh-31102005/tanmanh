<?php
require_once 'config.php';
require_once '../includes/Util.php';
require_once '../models/Service.php';

// Require KOL/KOC authentication
Util::requireAuth();
Util::requireRole('kol_koc');

$serviceObj = new Service();
$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Validate and sanitize input
    $serviceData = [
        'name' => Util::sanitizeInput($_POST['name'] ?? ''),
        'rate' => filter_var($_POST['rate'] ?? 0, FILTER_VALIDATE_FLOAT),
        'description' => Util::sanitizeInput($_POST['description'] ?? '')
    ];

    if ($action === 'create' || $action === 'update') {
        if (empty($serviceData['name']) || empty($serviceData['description']) || $serviceData['rate'] <= 0) {
            $message = 'Vui lòng điền đầy đủ thông tin và mức giá hợp lệ';
        }
    }

    if (!$message) {
        switch ($action) {
            case 'create':
                $result = $serviceObj->create($_SESSION['user_id'], $serviceData);
                if ($result['success']) {
                    Util::flashMessage('success', 'Thêm dịch vụ thành công');
                    Util::redirect('../view/services.php');
                } else {
                    $message = $result['message'];
                }
                break;

            case 'update':
                $serviceId = filter_var($_POST['service_id'] ?? 0, FILTER_VALIDATE_INT);
                if ($serviceId <= 0) {
                    $message = 'ID dịch vụ không hợp lệ';
                } else {
                    $result = $serviceObj->update($serviceId, $_SESSION['user_id'], $serviceData);
                    if ($result['success']) {
                        Util::flashMessage('success', 'Cập nhật dịch vụ thành công');
                        Util::redirect('../view/services.php');
                    } else {
                        $message = $result['message'];
                    }
                }
                break;

            case 'delete':
                $serviceId = filter_var($_POST['service_id'] ?? 0, FILTER_VALIDATE_INT);
                if ($serviceId <= 0) {
                    $message = 'ID dịch vụ không hợp lệ';
                } else {
                    $result = $serviceObj->delete($serviceId, $_SESSION['user_id']);
                    if ($result['success']) {
                        Util::flashMessage('success', 'Xóa dịch vụ thành công');
                        Util::redirect('../view/services.php');
                    } else {
                        $message = $result['message'] ?? 'Không thể xóa dịch vụ';
                    }
                }
                break;

            default:
                $message = 'Hành động không hợp lệ';
                break;
        }
    }
}

// Get services with booking stats
$services = $serviceObj->getWithBookingStats($_SESSION['user_id']);

require_once '../includes/header.php';
?>

<link rel="stylesheet" href="../assets/css/nologin.css">

<div class="container service-container">
    <div class="service-header">
        <h1>Quản lý dịch vụ</h1>
        <button onclick="showServiceForm()" class="btn">Thêm dịch vụ mới</button>
    </div>

    <?php if ($message): ?>
        <div class="flash-message error">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div id="serviceForm" class="service-form-container">
        <div class="content-box">
            <h2 id="formTitle">Thêm dịch vụ mới</h2>
            <form method="POST" action="" id="serviceFormElement">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="service_id" value="">

                <div class="form-group">
                    <label for="name">Tên dịch vụ</label>
                    <input type="text" id="name" name="name" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="rate">Giá dịch vụ (VNĐ)</label>
                    <input type="number" id="rate" name="rate" class="form-control" min="0" step="1000" required>
                </div>

                <div class="form-group">
                    <label for="description">Mô tả dịch vụ</label>
                    <textarea id="description" name="description" class="form-control" rows="4" required></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn">Lưu dịch vụ</button>
                    <button type="button" onclick="hideServiceForm()" class="btn back-btn">
                        Hủy
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($services)): ?>
        <p class="empty-message">
            Bạn chưa có dịch vụ nào. Hãy thêm dịch vụ đầu tiên!
        </p>
    <?php else: ?>
        <div class="service-grid">
            <?php foreach ($services as $service): ?>
                <div class="service-card">
                    <div class="service-title">
                        <div>
                            <h3><?= htmlspecialchars($service['name']) ?></h3>
                            <p class="service-price">
                                <?= number_format($service['rate']) ?> VNĐ
                            </p>
                        </div>
                        <div class="service-actions">
                            <button onclick='editService(<?= htmlspecialchars(json_encode($service)) ?>)' 
                                class="btn back-btn">
                                Sửa
                            </button>
                            <form method="POST" action="" class="inline-form" 
                                onsubmit="return confirm('Bạn có chắc muốn xóa dịch vụ này?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="service_id" value="<?= $service['id'] ?>">
                                <button type="submit" class="btn delete-btn">
                                    Xóa
                                </button>
                            </form>
                        </div>
                    </div>

                    <p class="text-secondary service-description">
                        <?= nl2br(htmlspecialchars($service['description'])) ?>
                    </p>

                    <div class="service-stats">
                        <div>
                            <strong><?= $service['total_bookings'] ?></strong> đơn đặt
                        </div>
                        <div>
                            <strong><?= $service['completed_bookings'] ?></strong> hoàn thành
                        </div>
                        <?php if ($service['avg_rating']): ?>
                            <div>
                                <strong><?= number_format($service['avg_rating'], 1) ?>/5</strong> đánh giá
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function showServiceForm() {
    document.getElementById('serviceForm').style.display = 'block';
    document.getElementById('formTitle').textContent = 'Thêm dịch vụ mới';
    document.getElementById('serviceFormElement').reset();
    document.querySelector('input[name="action"]').value = 'create';
    document.querySelector('input[name="service_id"]').value = '';
}

function hideServiceForm() {
    document.getElementById('serviceForm').style.display = 'none';
}

function editService(service) {
    document.getElementById('serviceForm').style.display = 'block';
    document.getElementById('formTitle').textContent = 'Chỉnh sửa dịch vụ';
    document.querySelector('input[name="action"]').value = 'update';
    document.querySelector('input[name="service_id"]').value = service.id;
    document.querySelector('input[name="name"]').value = service.name;
    document.querySelector('input[name="rate"]').value = service.rate;
    document.querySelector('textarea[name="description"]').value = service.description;
}
</script>

<?php require_once '../includes/footer.php'; ?>