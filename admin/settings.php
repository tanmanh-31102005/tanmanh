<?php
require_once '../view/config.php';
require_once '../includes/Util.php';
require_once '../models/Settings.php';

// Require admin authentication
Util::requireAuth();
Util::requireRole('admin');

$message = '';
$settings = Settings::getInstance();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Handle file upload for homepage background
        if (isset($_FILES['homepage_background']) && $_FILES['homepage_background']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['homepage_background'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                throw new Exception('Invalid file type. Only JPG, PNG and WebP images are allowed.');
            }
            
            $newFilename = uniqid() . '.' . $ext;
            $uploadPath = '../Uploads/backgrounds/';
            
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }
            
            if (move_uploaded_file($file['tmp_name'], $uploadPath . $newFilename)) {
                // Delete old background if exists
                $oldBackground = Settings::get('homepage_background');
                if ($oldBackground && file_exists($uploadPath . $oldBackground)) {
                    unlink($uploadPath . $oldBackground);
                }
                
                Settings::set('homepage_background', $newFilename);
            }
        }

        // Handle other settings
        $textSettings = ['contact_email', 'contact_phone'];
        foreach ($textSettings as $setting) {
            if (isset($_POST[$setting])) {
                Settings::set($setting, Util::sanitizeInput($_POST[$setting]));
            }
        }
        
        // Handle rich text content from CKEditor
        if (isset($_POST['contact_address'])) {
            // Don't sanitize heavily as it contains HTML
            Settings::set('contact_address', $_POST['contact_address']);
        }

        // Handle blog content from CKEditor
        if (isset($_POST['blog_content'])) {
            // Don't sanitize heavily as it contains HTML
            Settings::set('blog_content', $_POST['blog_content']);
        }

        // Handle pinned KOLs
        if (isset($_POST['pinned_kols'])) {
            $pinnedKols = array_filter(array_map('trim', explode(',', $_POST['pinned_kols'])));
            Settings::set('pinned_kols', json_encode($pinnedKols));
        }

        Util::flashMessage('success', 'Cập nhật cài đặt thành công');
        Util::redirect('../admin/settings.php');
    } catch (Exception $e) {
        $message = 'Lỗi: ' . $e->getMessage();
    }
}

$currentSettings = Settings::getAll();
$settingsMap = [];
foreach ($currentSettings as $setting) {
    $settingsMap[$setting['id']] = $setting['value'];
}

require_once '../includes/header.php';
?>

<link rel="stylesheet" href="../assets/css/admin2.css">
<!-- Include CKEditor from CDN -->
<script src="https://cdn.ckeditor.com/4.16.2/standard/ckeditor.js"></script>

<div class="container narrow">
    <div class="header-section">
        <h1>Cài đặt hệ thống</h1>
        <a href="dashboard.php" class="btn back-btn">
            ← Quay lại
        </a>
    </div>

    <?php if ($message): ?>
        <div class="flash-message error">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <div class="settings-form">
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="homepage_background">Ảnh nền trang chủ</label>
                <?php if (!empty($settingsMap['homepage_background'])): ?>
                    <div class="current-image">
                        <img src="../Uploads/backgrounds/<?= htmlspecialchars($settingsMap['homepage_background']) ?>" 
                             alt="Current homepage background">
                    </div>
                <?php endif; ?>
                <input type="file" id="homepage_background" name="homepage_background" class="form-control" accept="image/*">
            </div>

            <div class="form-group">
                <label for="contact_email">Email liên hệ</label>
                <input type="email" id="contact_email" name="contact_email" class="form-control"
                       value="<?= htmlspecialchars($settingsMap['contact_email'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="contact_phone">Số điện thoại liên hệ</label>
                <input type="text" id="contact_phone" name="contact_phone" class="form-control"
                       value="<?= htmlspecialchars($settingsMap['contact_phone'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="contact_address">Địa chỉ liên hệ</label>
                <textarea id="contact_address" name="contact_address" class="form-control" rows="3"><?= htmlspecialchars($settingsMap['contact_address'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label for="blog_content">Nội dung Blog</label>
                <textarea id="blog_content" name="blog_content" class="form-control" rows="5"><?= htmlspecialchars($settingsMap['blog_content'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label for="pinned_kols">KOL/KOC được gim (ID, phân cách bởi dấu phẩy)</label>
                <input type="text" id="pinned_kols" name="pinned_kols" class="form-control"
                       value="<?= htmlspecialchars(implode(',', json_decode($settingsMap['pinned_kols'] ?? '[]', true))) ?>">
                <small class="hint-text">Ví dụ: 1,2,3</small>
            </div>

            <button type="submit" class="btn" style="width: 100%;">Lưu thay đổi</button>
        </form>
    </div>
</div>

<script>
    // Initialize CKEditor on the contact_address textarea
    CKEDITOR.replace('contact_address', {
        language: 'vi',
        height: 200,
        toolbar: [
            ['Bold', 'Italic', 'Underline', 'Strike'],
            ['NumberedList', 'BulletedList'],
            ['Link', 'Unlink'],
            ['Undo', 'Redo'],
            ['Source']
        ]
    });

    // Initialize CKEditor on the blog_content textarea
    CKEDITOR.replace('blog_content', {
        language: 'vi',
        height: 300,
        toolbar: [
            ['Bold', 'Italic', 'Underline', 'Strike'],
            ['NumberedList', 'BulletedList'],
            ['Link', 'Unlink'],
            ['Image', 'Table'],
            ['Undo', 'Redo'],
            ['Source']
        ]
    });
</script>

<?php require_once '../includes/footer.php'; ?>