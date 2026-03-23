<?php
require_once 'config.php';
require_once '../includes/Util.php';
require_once '../models/Booking.php';

Util::requireAuth();

$bookingObj = new Booking();
$message = '';

// Handle booking status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $bookingId = filter_var($_POST['booking_id'] ?? 0, FILTER_VALIDATE_INT);
    
    if ($action === 'update_status' && $bookingId) {
        $newStatus = Util::sanitizeInput($_POST['status'] ?? '');
        $result = $bookingObj->updateStatus($bookingId, $_SESSION['user_id'], $newStatus);
        
        if ($result['success']) {
            Util::flashMessage('success', 'Cập nhật trạng thái thành công');
            Util::redirect('../view/bookings.php');
        } else {
            $message = $result['message'];
        }
    } elseif ($action === 'add_review' && $bookingId) {
        $rating = filter_var($_POST['rating'] ?? 0, FILTER_VALIDATE_INT);
        $comment = Util::sanitizeInput($_POST['comment'] ?? '');
        
        if ($rating < 1 || $rating > 5) {
            $message = 'Đánh giá không hợp lệ';
        } elseif (empty($comment)) {
            $message = 'Vui lòng nhập nhận xét';
        } else {
            $result = $bookingObj->addReview($bookingId, $_SESSION['user_id'], $rating, $comment);
            
            if ($result['success']) {
                Util::flashMessage('success', 'Đánh giá thành công');
                Util::redirect('../view/bookings.php');
            } else {
                $message = $result['message'];
            }
        }
    }
}

// Get bookings for current user
$bookings = $bookingObj->getByUserId($_SESSION['user_id'], $_SESSION['user_role']);

require_once '../includes/header.php';
?>
<link rel="stylesheet" href="../assets/css/kol.css">
<div class="container">
    <div class="bookings-header">
        <h1>Quản lý đơn đặt</h1>
        <?php if ($_SESSION['user_role'] === 'brand'): ?>
            <a href="../brand/payment-h.php" class="btn payment-history-btn">
                Lịch sử thanh toán
            </a>
        <?php endif; ?>
    </div>

    <?php if ($message): ?>
        <div class="flash-message error">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <?php if (empty($bookings)): ?>
        <p class="empty-bookings">
            Chưa có đơn đặt nào
        </p>
    <?php else: ?>
        <div class="bookings-grid">
            <?php foreach ($bookings as $booking): ?>
                <div class="booking-card">
                    <div class="booking-content">
                        <div class="booking-info">
                            <div class="booking-user">
                                <?php if ($_SESSION['user_role'] === 'brand'): ?>
                                    <img src="<?= $booking['kol_koc_avatar'] ? '../uploads/avatars/' . $booking['kol_koc_avatar'] : '../assets/images/default-avatar.png' ?>"
                                         alt="<?= htmlspecialchars($booking['kol_koc_name']) ?>"
                                         class="booking-user-avatar">
                                    <div>
                                        <h3 class="booking-user-name"><?= htmlspecialchars($booking['kol_koc_name']) ?></h3>
                                        <p class="booking-user-role">KOL/KOC</p>
                                    </div>
                                <?php else: ?>
                                    <img src="<?= $booking['brand_avatar'] ? '../uploads/avatars/' . $booking['brand_avatar'] : '../assets/images/default-avatar.png' ?>"
                                         alt="<?= htmlspecialchars($booking['brand_name']) ?>"
                                         class="booking-user-avatar">
                                    <div>
                                        <h3 class="booking-user-name"><?= htmlspecialchars($booking['brand_name']) ?></h3>
                                        <p class="booking-user-role">Thương hiệu</p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="booking-service">
                                <h4 class="booking-service-title">Dịch vụ</h4>
                                <p><?= htmlspecialchars($booking['service_name']) ?></p>
                                <p class="booking-price">
                                    <?= number_format($booking['payment_amount']) ?> VNĐ
                                </p>
                            </div>

                            <div class="booking-details">
                                <div>
                                    <strong>Số lượng:</strong> <?= $booking['posts'] ?> bài đăng
                                </div>
                                <div>
                                    <strong>Deadline:</strong> <?= date('d/m/Y', strtotime($booking['deadline'])) ?>
                                </div>
                            </div>
                        </div>

                        <div class="booking-actions">
                            <div style="margin-bottom: 1rem;">
                                <span class="status-badge <?= 'status-badge-' . $booking['status'] ?>">
                                    <?php
                                    switch($booking['status']) {
                                        case 'pending':
                                            echo 'Chờ xác nhận';
                                            break;
                                        case 'accepted':
                                            echo 'Đã nhận';
                                            break;
                                        case 'completed':
                                            echo 'Hoàn thành';
                                            break;
                                        case 'cancelled':
                                            echo 'Đã hủy';
                                            break;
                                        case 'declined':
                                            echo 'Từ chối';
                                            break;
                                    }
                                    ?>
                                </span>
                                <?php if ($_SESSION['user_role'] === 'brand'): ?>
                                    <div style="margin-top: 0.5rem;">
                                        <span class="status-badge <?= 'status-badge-' . $booking['payment_status'] ?>">
                                            <?php
                                            switch($booking['payment_status']) {
                                                case 'paid':
                                                    echo 'Đã thanh toán';
                                                    break;
                                                case 'pending':
                                                    echo 'Chờ thanh toán';
                                                    break;
                                                case 'failed':
                                                    echo 'Thanh toán thất bại';
                                                    break;
                                            }
                                            ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if ($_SESSION['user_role'] === 'brand' && $booking['payment_status'] === 'pending' && $booking['status'] === 'completed'): ?>
                                <div style="display: flex; justify-content: flex-end; margin-bottom: 1rem;">
                                    <a href="payment.php?booking_id=<?= $booking['id'] ?>" class="btn">
                                        Thanh toán ngay
                                    </a>
                                </div>
                            <?php endif; ?>

                            <?php if ($_SESSION['user_role'] === 'kol_koc' && $booking['status'] === 'pending'): ?>
                                <div class="action-buttons">
                                    <form method="POST" action="">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                        <input type="hidden" name="status" value="accepted">
                                        <button type="submit" class="btn">Nhận đơn</button>
                                    </form>
                                    <form method="POST" action="">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                        <input type="hidden" name="status" value="declined">
                                        <button type="submit" class="btn" style="background-color: var(--error-color);">
                                            Từ chối
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>

                            <?php if ($booking['status'] === 'accepted'): ?>
                                <form method="POST" action="" style="margin-bottom: 1rem;">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                    <input type="hidden" name="status" value="completed">
                                    <button type="submit" class="btn" style="background-color: var(--success-color);">
                                        Đánh dấu hoàn thành
                                    </button>
                                </form>
                            <?php endif; ?>

                            <?php if ($_SESSION['user_role'] === 'brand' && $booking['status'] === 'completed' && $bookingObj->canReview($booking['id'], $_SESSION['user_id'])): ?>
                                <button onclick="showReviewForm(<?= $booking['id'] ?>)" class="btn">
                                    Đánh giá
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php
                    $reviews = $bookingObj->getReviews($booking['id']);
                    if (!empty($reviews)):
                    ?>
                        <div class="reviews-section">
                            <h4 style="margin-bottom: 1rem;">Đánh giá</h4>
                            <?php foreach ($reviews as $review): ?>
                                <div class="review-item">
                                    <img src="<?= $review['reviewer_avatar'] ? '../uploads/avatars/' . $review['reviewer_avatar'] : '../assets/images/default-avatar.png' ?>"
                                         alt="<?= htmlspecialchars($review['reviewer_name']) ?>"
                                         class="review-avatar">
                                    <div class="review-content">
                                        <div class="review-header">
                                            <strong><?= htmlspecialchars($review['reviewer_name']) ?></strong>
                                            <span class="review-stars">
                                                <?= str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']) ?>
                                            </span>
                                        </div>
                                        <p class="review-text">
                                            <?= nl2br(htmlspecialchars($review['comment'])) ?>
                                        </p>
                                        <small class="review-date">
                                            <?= date('d/m/Y H:i', strtotime($review['created_at'])) ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Review Modal -->
<div id="reviewModal" class="modal-overlay">
    <div class="modal-content">
        <h2 class="modal-title">Đánh giá</h2>
        <form method="POST" action="" id="reviewForm">
            <input type="hidden" name="action" value="add_review">
            <input type="hidden" name="booking_id" id="reviewBookingId">

            <div class="form-group">
                <label>Đánh giá của bạn</label>
                <div class="stars-container">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <label style="cursor: pointer;">
                            <input type="radio" name="rating" value="<?= $i ?>" required
                                style="display: none;"
                                onchange="updateStars(this.value)">
                            <span class="star" data-rating="<?= $i ?>">☆</span>
                        </label>
                    <?php endfor; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="comment">Nhận xét của bạn</label>
                <textarea name="comment" id="comment" rows="4" class="form-control" required></textarea>
            </div>

            <div class="modal-actions">
                <button type="button" onclick="hideReviewModal()" class="btn cancel-btn">
                    Hủy
                </button>
                <button type="submit" class="btn">Gửi đánh giá</button>
            </div>
        </form>
    </div>
</div>

<script>
function showReviewForm(bookingId) {
    document.getElementById('reviewBookingId').value = bookingId;
    document.getElementById('reviewModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function hideReviewModal() {
    document.getElementById('reviewModal').style.display = 'none';
    document.body.style.overflow = 'auto';
    document.getElementById('reviewForm').reset();
    document.querySelectorAll('.star').forEach(star => {
        star.style.color = 'var(--text-secondary)';
        star.textContent = '☆';
    });
}

function updateStars(rating) {
    document.querySelectorAll('.star').forEach(star => {
        const starRating = star.getAttribute('data-rating');
        if (starRating <= rating) {
            star.style.color = 'var(--accent-color)';
            star.textContent = '★';
        } else {
            star.style.color = 'var(--text-secondary)';
            star.textContent = '☆';
        }
    });
}

// Star rating hover effects
document.querySelectorAll('.star').forEach(star => {
    star.addEventListener('mouseover', () => {
        const rating = star.getAttribute('data-rating');
        document.querySelectorAll('.star').forEach(s => {
            const r = s.getAttribute('data-rating');
            if (r <= rating) {
                s.style.color = 'var(--accent-color)';
                s.textContent = '★';
            } else {
                s.style.color = 'var(--text-secondary)';
                s.textContent = '☆';
            }
        });
    });

    star.addEventListener('mouseout', () => {
        const selectedRating = document.querySelector('input[name="rating"]:checked')?.value;
        document.querySelectorAll('.star').forEach(s => {
            const r = s.getAttribute('data-rating');
            if (r <= selectedRating) {
                s.style.color = 'var(--accent-color)';
                s.textContent = '★';
            } else {
                s.style.color = 'var(--text-secondary)';
                s.textContent = '☆';
            }
        });
    });

    star.addEventListener('click', () => {
        const rating = star.getAttribute('data-rating');
        document.querySelector(`input[name="rating"][value="${rating}"]`).checked = true;
    });
});

// Close modal when clicking outside
document.getElementById('reviewModal').addEventListener('click', (e) => {
    if (e.target === document.getElementById('reviewModal')) {
        hideReviewModal();
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>