<?php
require_once 'config.php';
require_once '../includes/Util.php';
require_once '../models/Payment.php';
require_once '../models/Booking.php';

// Require authentication
Util::requireAuth();

$db = Database::getInstance();
$message = '';
$payment = null;
$booking = null;

// Get booking ID from URL
$bookingId = filter_var($_GET['booking_id'] ?? 0, FILTER_VALIDATE_INT);

if (!$bookingId) {
    Util::redirect('../view/bookings.php');
}

// Get booking and validate ownership
$bookingModel = new Booking();
$booking = $bookingModel->getById($bookingId);
if (!$booking || $booking['brand_id'] != $_SESSION['user_id']) {
    Util::redirect('../view/bookings.php');
}

// Lấy thông tin KOL trực tiếp từ profiles
$sql = "SELECT p.avatar, p.name 
        FROM profiles p 
        WHERE p.user_id = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$booking['kol_koc_id']]);
$kolUser = $stmt->fetch();

// Tính toán URL ảnh đại diện của KOL/KOC
$avatarUrl = $kolUser['avatar'] 
    ? '../Uploads/avatars/' . $kolUser['avatar'] 
    : '../assets/images/default-avatar.png';

// Get or create payment
$paymentModel = new Payment();
$payment = $paymentModel->getByBookingId($bookingId);

// If no payment exists, store service price for display
if (!$payment && !isset($_POST['payment_method'])) {
    $payment = [
        'amount' => $booking['service_price'],
        'status' => 'pending'
    ];
}

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'confirm_payment') {
        // Xác nhận thanh toán
        $result = $paymentModel->updateStatus($payment['id'], 'paid');
        if ($result['success']) {
            Util::flashMessage('success', 'Xác nhận thanh toán thành công');
            Util::redirect("../view/payment.php?booking_id=$bookingId");
        } else {
            $message = 'Không thể xác nhận thanh toán: ' . ($result['message'] ?? '');
        }
    } else {
        // Tạo thanh toán
        $paymentMethod = Util::sanitizeInput($_POST['payment_method'] ?? '');
        if (!in_array($paymentMethod, ['bank_transfer', 'momo', 'zalopay'])) {
            $message = 'Phương thức thanh toán không hợp lệ';
        } else {
            $result = $paymentModel->create([
                'booking_id' => $bookingId,
                'amount' => $booking['service_price'],
                'payment_method' => $paymentMethod,
                'status' => 'pending'
            ]);
            
            if ($result['success']) {
                Util::flashMessage('success', 'Đã tạo thanh toán thành công');
                Util::redirect("../view/payment.php?booking_id=$bookingId");
            } else {
                $message = 'Không thể tạo thanh toán: ' . ($result['message'] ?? '');
            }
        }
    }
}

require_once '../includes/header.php';
?>

<!-- Link to the CSS file -->
<link rel="stylesheet" href="../assets/css/payment.css">

<div class="payment-container">
    <div class="page-header">
        <h1>Thanh toán dịch vụ</h1>
        <a href="bookings.php" class="btn back-btn">
            ← Quay lại
        </a>
    </div>

    <?php if ($message): ?>
        <div class="flash-message error">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <h2 style="margin-bottom: 1.5rem;">Chi tiết đơn đặt</h2>
            
            <div class="grid">
                <div class="profile">
                    <img src="<?= htmlspecialchars($avatarUrl) ?>"
                         alt="<?= htmlspecialchars($kolUser['name'] ?? 'KOL') ?>"
                         class="avatar">
                    <div class="profile-info">
                        <div><?= htmlspecialchars($kolUser['name'] ?? 'KOL') ?></div>
                        <div>KOL/KOC</div>
                    </div>
                </div>
                
                <div>
                    <div class="service-title">Dịch vụ</div>
                    <div class="service-name"><?= htmlspecialchars($booking['service_name']) ?></div>
                    <div class="service-price">
                        <?= number_format($booking['service_price']) ?> VNĐ
                    </div>
                </div>
                
                <div>
                    <div class="deadline-title">Thời hạn</div>
                    <div class="deadline-date"><?= date_format(date_create($booking['deadline']), 'd/m/Y') ?></div>
                </div>
            </div>

            <?php if ($payment): ?>
                <div class="payment-info">
                    <h3 style="margin-bottom: 1rem;">Thông tin thanh toán</h3>
                    
                    <div class="grid">
                        <div>
                            <div class="service-title">Trạng thái</div>
                            <div class="payment-status <?php
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
                            </div>
                        </div>
                        
                        <div>
                            <div class="service-title">Phương thức</div>
                            <div class="payment-method">
                                <?php
                                switch($payment['payment_method']) {
                                    case 'bank_transfer':
                                        echo 'Chuyển khoản ngân hàng';
                                        break;
                                    case 'momo':
                                        echo 'Ví MoMo';
                                        break;
                                    case 'zalopay':
                                        echo 'ZaloPay';
                                        break;
                                    default:
                                        echo 'Chưa chọn';
                                }
                                ?>
                            </div>
                        </div>

                        <?php if ($payment['status'] === 'pending'): ?>
                            <?php if ($payment['payment_method'] === 'bank_transfer'): ?>
                                <div style="margin-top: 1rem;">
                                    <div class="bank-info">
                                        Thông tin chuyển khoản:
                                    </div>
                                    <div class="bank-details">
                                        <p>Ngân hàng: <strong>VietcomBank</strong></p>
                                        <p>Số tài khoản: <strong>1234567890</strong></p>
                                        <p>Chủ tài khoản: <strong>NGUYEN VAN A</strong></p>
                                        <p>Nội dung: <strong>KOL<?= $bookingId ?></strong></p>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div style="margin-top: 1rem;">
                                    <div class="bank-info">
                                        Quét mã để thanh toán:
                                    </div>
                                    <img src="assets/images/qr-code.png" alt="QR Code" class="qr-image">
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <?php if ($booking['status'] === 'completed' && $payment['status'] === 'pending'): ?>
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="confirm_payment">
                            <button type="submit" class="btn full-width-btn" style="background-color: var(--success-color);">
                                Xác nhận đã thanh toán
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <form method="POST" action="">
                    <div style="margin-bottom: 1.5rem;">
                        <h3 style="margin-bottom: 1rem;">Chọn phương thức thanh toán</h3>
                        
                        <div class="grid">
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="bank_transfer" checked>
                                <span>Chuyển khoản ngân hàng</span>
                            </label>
                            
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="momo">
                                <span>Ví MoMo</span>
                            </label>
                            
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="zalopay">
                                <span>ZaloPay</span>
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn full-width-btn">
                        Tiếp tục thanh toán
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>