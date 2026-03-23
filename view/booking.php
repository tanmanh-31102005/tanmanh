<?php
require_once 'config.php';
require_once '../includes/Util.php';
require_once '../models/User.php';
require_once '../models/Service.php';
require_once '../models/Booking.php';

// Require brand authentication
Util::requireAuth();
Util::requireRole('brand');

$message = '';
$kolKocId = filter_var($_GET['kol_koc_id'] ?? 0, FILTER_VALIDATE_INT);

if (!$kolKocId) {
    header('Location: search.php');
    exit();
}

// Get KOL/KOC details
$kolKoc = User::getById($kolKocId);
if (!$kolKoc || $kolKoc['role'] !== 'kol_koc') {
    header('Location: search.php');
    exit();
}

// Get KOL/KOC services
$serviceObj = new Service();
$services = $serviceObj->getByUserId($kolKocId);

if (empty($services)) {
    $message = 'KOL/KOC chưa có dịch vụ nào';
}

// Handle booking submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $serviceId = filter_var($_POST['service_id'] ?? 0, FILTER_VALIDATE_INT);
    $posts = filter_var($_POST['posts'] ?? 0, FILTER_VALIDATE_INT);
    $deadline = $_POST['deadline'] ?? '';

    if (!$serviceId || !$posts || !$deadline) {
        $message = 'Vui lòng điền đầy đủ thông tin';
    } elseif ($posts < 1) {
        $message = 'Số lượng bài đăng không hợp lệ';
    } elseif (strtotime($deadline) < strtotime('tomorrow')) {
        $message = 'Deadline phải từ ngày mai trở đi';
    } else {
        $bookingObj = new Booking();
        $result = $bookingObj->create(
            $_SESSION['user_id'],
            $kolKocId,
            $serviceId,
            [
                'posts' => $posts,
                'deadline' => $deadline
            ]
        );

        if ($result['success']) {
            Util::flashMessage('success', 'Đặt dịch vụ thành công');
            Util::redirect('bookings.php');
        } else {
            $message = $result['message'];
        }
    }
}

require_once '../includes/header.php';
?>
<link rel="stylesheet" href="../assets/css/brand.css">
<div class="profile-container">
    <div class="back-button">
        <a href="javascript:history.back()" class="btn">
            ← Quay lại
        </a>
    </div>

    <div class="profile-card">
        <div class="profile-header">
            <img 
                src="<?= $kolKoc['avatar'] ? '../uploads/avatars/' . $kolKoc['avatar'] : '../assets/images/default-avatar.png' ?>"
                alt="<?= htmlspecialchars($kolKoc['name']) ?>"
                class="profile-avatar"
            >
        </div>

        <div class="profile-info">
            <h1 class="profile-name"><?= htmlspecialchars($kolKoc['name']) ?></h1>
            <p class="profile-industry">
                <?= htmlspecialchars($kolKoc['industry'] ?? '') ?>
            </p>
            <?php if ($kolKoc['followers']): ?>
                <p class="profile-followers">
                    <?= number_format($kolKoc['followers']) ?> followers
                </p>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="flash-message error message-container">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($services)): ?>
        <div class="booking-section">
            <h2 class="booking-title">Đặt dịch vụ</h2>
            
            <form method="POST" action="" id="bookingForm">
                <div class="services-grid">
                    <?php foreach ($services as $service): ?>
                        <label class="service-card">
                            <input type="radio" name="service_id" value="<?= $service['id'] ?>" 
                                required style="display: none;">
                            <h3 class="service-title">
                                <?= htmlspecialchars($service['name']) ?>
                            </h3>
                            <p class="service-price">
                                <?= number_format($service['rate']) ?> VNĐ/bài
                            </p>
                            <p class="service-description">
                                <?= nl2br(htmlspecialchars($service['description'])) ?>
                            </p>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="booking-form-row">
                    <div class="form-group">
                        <label for="posts">Số lượng bài đăng</label>
                        <input type="number" id="posts" name="posts" class="form-control" 
                            min="1" required onchange="updateTotal()">
                    </div>

                    <div class="form-group">
                        <label for="deadline">Deadline</label>
                        <input type="date" id="deadline" name="deadline" class="form-control" 
                            min="<?= date('Y-m-d', strtotime('tomorrow')) ?>" required>
                    </div>
                </div>

                <div id="totalPrice" class="total-price">
                    Tổng tiền: <span class="total-price-value"></span>
                </div>

                <button type="submit" class="btn full-width-button">Đặt dịch vụ</button>
            </form>
        </div>
    <?php endif; ?>
</div>

<script>
function updateServiceBorder() {
    const serviceCards = document.querySelectorAll('.service-card');
    serviceCards.forEach(card => {
        const radio = card.querySelector('input[name="service_id"]');
        card.style.borderColor = radio.checked ? 'var(--accent-color)' : 'var(--border-color)';
    });
}

function updateTotal() {
    const form = document.getElementById('bookingForm');
    const selectedService = form.querySelector('input[name="service_id"]:checked');
    const posts = form.querySelector('input[name="posts"]').value;
    const totalPrice = document.getElementById('totalPrice');

    updateServiceBorder();

    if (selectedService && posts > 0) {
        const rate = parseFloat(selectedService.closest('label').querySelector('.service-price').textContent.replace(/[^\d]/g, ''));
        const total = rate * posts;
        totalPrice.style.display = 'block';
        totalPrice.querySelector('span').textContent = new Intl.NumberFormat('vi-VN').format(total) + ' VNĐ';
    } else {
        totalPrice.style.display = 'none';
    }
}

document.querySelectorAll('input[name="service_id"]').forEach(radio => {
    radio.addEventListener('change', updateTotal);
});

document.addEventListener('DOMContentLoaded', updateServiceBorder);
</script>
<?php require_once '../includes/footer.php'; ?>