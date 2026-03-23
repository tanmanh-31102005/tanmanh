<?php
require_once 'config.php';
require_once '../includes/Util.php';
require_once '../models/User.php';
require_once '../models/Service.php';
require_once '../models/Booking.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if viewing other's profile or own profile
$profileId = $_GET['id'] ?? null;
$isOwnProfile = !$profileId || (Util::isAuthenticated() && $profileId == $_SESSION['user_id']);

// Require authentication for own profile
if ($isOwnProfile) {
    Util::requireAuth();
    $profileId = $_SESSION['user_id'];
}

$user = User::getById($profileId);
if (!$user) {
    header('HTTP/1.0 404 Not Found');
    die('Profile not found');
}

// Kết nối cơ sở dữ liệu để lấy danh sách categories
$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Lấy danh sách tất cả categories từ bảng categories
$categoriesQuery = "SELECT id, name FROM categories";
$categoriesResult = $db->query($categoriesQuery);
$categories = [];
while ($row = $categoriesResult->fetch_assoc()) {
    $categories[] = $row;
}

// Lấy các category đã chọn của user từ bảng user_categories
$userCategoriesQuery = "SELECT category_id FROM user_categories WHERE user_id = ?";
$stmt = $db->prepare($userCategoriesQuery);
$stmt->bind_param("i", $profileId);
$stmt->execute();
$userCategoriesResult = $stmt->get_result();
$userCategoryIds = [];
while ($row = $userCategoriesResult->fetch_assoc()) {
    $userCategoryIds[] = $row['category_id'];
}

// Lấy danh sách dịch vụ của KOL/KOC
$serviceObj = new Service();
$services = $serviceObj->getByUserId($profileId);

// Lấy danh sách đánh giá của KOL/KOC
$reviews = [];
if ($user['role'] === 'kol_koc') {
    $sql = "SELECT r.*, p.name as reviewer_name, p.avatar as reviewer_avatar
            FROM reviews r
            JOIN bookings b ON r.booking_id = b.id
            JOIN profiles p ON r.reviewer_id = p.user_id
            WHERE b.kol_koc_id = ?
            ORDER BY r.created_at DESC";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $profileId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }
    $stmt->close();
}

$db->close();

require_once '../includes/header.php';

// Get avatar URL
$avatarUrl = $user['avatar'] 
    ? '../uploads/avatars/' . $user['avatar'] 
    : Util::getImagePlaceholder();
?>
<link rel="stylesheet" href="../assets/css/kol.css">

<div class="container">
    <div id="message-container"></div>

    <div class="profile-container">
        <div class="profile-header">
            <img 
                src="<?= htmlspecialchars($avatarUrl) ?>"
                alt="<?= htmlspecialchars($user['name'] ?? '') ?>"
                class="profile-avatar"
                id="profile-avatar"
            >
        </div>

        <div class="profile-content">
            <?php if ($isOwnProfile): ?>
                <form id="profile-form" enctype="multipart/form-data">
                    <div style="text-align: center; margin-bottom: 2rem;">
                        <label class="btn avatar-upload-btn">
                            Đổi ảnh đại diện
                            <input type="file" name="avatar" id="avatar-upload" accept="image/*" style="display: none;">
                        </label>
                    </div>

                    <div class="form-group">
                        <label for="name">Tên hiển thị <span style="color: var(--error-color);">*</span></label>
                        <input type="text" id="name" name="name" class="form-control" 
                            value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="bio">Giới thiệu</label>
                        <textarea id="bio" name="bio" class="form-control" rows="4"
                            ><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Lĩnh vực (Chọn các lĩnh vực phù hợp)</label>
                        <div class="category-list">
                            <?php foreach ($categories as $category): ?>
                                <div class="category-item">
                                    <input type="checkbox" 
                                           name="categories[]" 
                                           value="<?= htmlspecialchars($category['id']) ?>"
                                           id="category-<?= htmlspecialchars($category['id']) ?>"
                                           <?= in_array($category['id'], $userCategoryIds) ? 'checked' : '' ?>>
                                    <label for="category-<?= htmlspecialchars($category['id']) ?>">
                                        <?= htmlspecialchars($category['name']) ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="followers">Số lượng người theo dõi</label>
                        <input type="text" id="followers" name="followers" class="form-control" 
                            value="<?= $user['followers'] !== null ? number_format((int)$user['followers']) : '' ?>" 
                            placeholder="Nhập số lượng người theo dõi">
                    </div>

                    <div class="form-group">
                        <label for="social_links">Liên kết mạng xã hội (mỗi liên kết một dòng)</label>
                        <textarea id="social_links" name="social_links" class="form-control" rows="3"
                            placeholder="VD: https://facebook.com/yourusername"
                            ><?= htmlspecialchars($user['social_links'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" class="btn" id="update-profile-btn">Cập nhật hồ sơ</button>
                    <span id="loading-profile" style="display: none; margin-left: 10px;">Đang cập nhật...</span>
                </form>

                <!-- Section đổi mật khẩu -->
                <div class="profile-section" style="margin-top: 2rem;">
                    <h3 class="profile-section-title">Đổi mật khẩu</h3>
                    <div id="password-message-container"></div>
                    <form id="password-form">
                        <div class="form-group">
                            <label for="current_password">Mật khẩu hiện tại <span style="color: var(--error-color);">*</span></label>
                            <input type="password" id="current_password" name="current_password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="new_password">Mật khẩu mới <span style="color: var(--error-color);">*</span></label>
                            <input type="password" id="new_password" name="new_password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Xác nhận mật khẩu mới <span style="color: var(--error-color);">*</span></label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn" id="change-password-btn">Đổi mật khẩu</button>
                        <span id="loading-password" style="display: none; margin-left: 10px;">Đang xử lý...</span>
                    </form>
                </div>
            <?php else: ?>
                <div style="text-align: center;">
                    <h1 class="profile-name"><?= htmlspecialchars($user['name'] ?? '') ?></h1>
                    <p class="profile-industry">
                        <?php
                        $userCategoryNames = [];
                        foreach ($categories as $category) {
                            if (in_array($category['id'], $userCategoryIds)) {
                                $userCategoryNames[] = htmlspecialchars($category['name']);
                            }
                        }
                        echo implode(', ', $userCategoryNames);
                        ?>
                    </p>
                    <?php if ($user['followers'] !== null): ?>
                        <p class="profile-followers">
                            <?= number_format((int)$user['followers']) ?> người theo dõi
                        </p>
                    <?php endif; ?>
                </div>

                <?php if (!empty($user['bio'])): ?>
                    <div class="profile-section">
                        <h3 class="profile-section-title">Giới thiệu</h3>
                        <p class="profile-bio">
                            <?= nl2br(htmlspecialchars($user['bio'])) ?>
                        </p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($user['social_links'])): ?>
                    <div class="profile-section">
                        <h3 class="profile-section-title">Mạng xã hội</h3>
                        <?php foreach (explode("\n", $user['social_links']) as $link): ?>
                            <?php if ($link = trim($link)): ?>
                                <a href="<?= htmlspecialchars($link) ?>" 
                                   target="_blank" 
                                   rel="noopener noreferrer"
                                   class="social-link"
                                >
                                    <?= parse_url($link, PHP_URL_HOST) ?>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($services)): ?>
                    <div class="profile-section">
                        <h3 class="profile-section-title">Dịch vụ cung cấp</h3>
                        <div class="services-grid">
                            <?php foreach ($services as $service): ?>
                                <div class="service-card">
                                    <h4 class="service-title"><?= htmlspecialchars($service['name']) ?></h4>
                                    <p class="service-price">
                                        <?= number_format($service['rate']) ?> VNĐ/bài
                                    </p>
                                    <p class="service-description">
                                        <?= nl2br(htmlspecialchars($service['description'])) ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($reviews)): ?>
                    <div class="profile-section">
                        <h3 class="profile-section-title">Đánh giá</h3>
                        <div class="reviews-section">
                            <?php foreach ($reviews as $review): ?>
                                <div class="review-item">
                                    <img src="<?= $review['reviewer_avatar'] ? '../uploads/avatars/' . $review['reviewer_avatar'] : Util::getImagePlaceholder() ?>"
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
                    </div>
                <?php endif; ?>

                <?php if (Util::isAuthenticated() && $_SESSION['user_role'] === 'brand' && $user['role'] === 'kol_koc'): ?>
                    <div class="profile-section">
                        <a href="booking.php?kol_koc_id=<?= $user['id'] ?>" class="btn">
                            Đặt dịch vụ
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Preview image before upload
    document.getElementById('avatar-upload').addEventListener('change', function(e) {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('profile-avatar').src = e.target.result;
            };
            reader.readAsDataURL(this.files[0]);
        }
    });

    // Format số người theo dõi với dấu phẩy ngăn cách
    document.getElementById('followers').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, ''); // Chỉ giữ số
        if (value) {
            let numericValue = Number(value);
            e.target.value = numericValue.toLocaleString('vi-VN');
            e.target.dataset.rawValue = numericValue;
        } else {
            e.target.value = '';
            e.target.dataset.rawValue = '';
        }
    });

    // Giới hạn số lượng category được chọn
    document.querySelectorAll('.category-item input[type="checkbox"]').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            const checkedCount = document.querySelectorAll('.category-item input[type="checkbox"]:checked').length;
            if (checkedCount > 5) {
                this.checked = false;
                showMessage('message-container', 'Bạn chỉ có thể chọn tối đa 5 lĩnh vực!', false);
            }
        });
    });

    // Hiển thị thông báo
    function showMessage(containerId, message, isSuccess = true) {
        const container = document.getElementById(containerId);
        container.innerHTML = `
            <div class="flash-message ${isSuccess ? 'success' : 'error'}">
                ${message}
            </div>
        `;
        setTimeout(() => {
            container.innerHTML = '';
        }, 5000);
    }

    // Xử lý form cập nhật hồ sơ bằng AJAX
    document.getElementById('profile-form').addEventListener('submit', function(e) {
        e.preventDefault();

        // Kiểm tra dữ liệu trước khi gửi
        const name = document.getElementById('name').value.trim();
        const followers = document.getElementById('followers').dataset.rawValue || '';
        const socialLinks = document.getElementById('social_links').value.trim();

        if (!name) {
            showMessage('message-container', 'Vui lòng nhập tên hiển thị', false);
            return;
        }

        if (followers && (isNaN(followers) || followers < 0)) {
            showMessage('message-container', 'Số lượng người theo dõi phải là số không âm', false);
            return;
        }

        // Kiểm tra định dạng liên kết mạng xã hội
        if (socialLinks) {
            const links = socialLinks.split('\n').map(link => link.trim()).filter(link => link);
            const urlPattern = /^(https?:\/\/)?([\da-z.-]+)\.([a-z.]{2,6})([\/\w .-]*)*\/?$/;
            for (const link of links) {
                if (!urlPattern.test(link)) {
                    showMessage('message-container', `Liên kết không hợp lệ: ${link}`, false);
                    return;
                }
            }
        }

        // Hiển thị trạng thái đang xử lý
        document.getElementById('update-profile-btn').disabled = true;
        document.getElementById('loading-profile').style.display = 'inline';

        // Chuẩn bị dữ liệu form
        const formData = new FormData(this);
        let followersInput = document.getElementById('followers');
        if (followersInput.dataset.rawValue !== undefined) {
            formData.set('followers', followersInput.dataset.rawValue || '');
        }

        // Gửi yêu cầu AJAX
        fetch('update_profile.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            showMessage('message-container', data.message, data.success);
            if (data.success && data.avatarUrl) {
                document.getElementById('profile-avatar').src = data.avatarUrl;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('message-container', 'Đã xảy ra lỗi khi cập nhật hồ sơ', false);
        })
        .finally(() => {
            document.getElementById('update-profile-btn').disabled = false;
            document.getElementById('loading-profile').style.display = 'none';
        });
    });

    // Xử lý form đổi mật khẩu bằng AJAX
    document.getElementById('password-form').addEventListener('submit', function(e) {
        e.preventDefault();

        const newPass = document.getElementById('new_password').value;
        const confirmPass = document.getElementById('confirm_password').value;

        if (newPass !== confirmPass) {
            showMessage('password-message-container', 'Mật khẩu mới và xác nhận mật khẩu không khớp', false);
            return;
        }

        if (newPass.length < 8 || !/[A-Z]/.test(newPass) || !/[a-z]/.test(newPass) || !/[0-9]/.test(newPass)) {
            showMessage('password-message-container', 'Mật khẩu mới phải có ít nhất 8 ký tự, bao gồm chữ cái in hoa, chữ cái thường và số', false);
            return;
        }

        document.getElementById('change-password-btn').disabled = true;
        document.getElementById('loading-password').style.display = 'inline';

        const formData = new FormData(this);

        fetch('change_password.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            showMessage('password-message-container', data.message, data.success);
            if (data.success) {
                this.reset();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('password-message-container', 'Đã xảy ra lỗi khi đổi mật khẩu', false);
        })
        .finally(() => {
            document.getElementById('change-password-btn').disabled = false;
            document.getElementById('loading-password').style.display = 'none';
        });
    });
});
</script>

<?php 
require_once '../includes/footer.php'; 
?>