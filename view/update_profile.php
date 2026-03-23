<?php
require_once 'config.php';
require_once '../includes/Util.php';
require_once '../models/User.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Chỉ xử lý yêu cầu AJAX POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Phương thức không được phép']);
    exit;
}

// Yêu cầu người dùng đã đăng nhập
if (!Util::isAuthenticated()) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để thực hiện hành động này']);
    exit;
}

$profileId = $_SESSION['user_id'];
$response = ['success' => false];

try {
    $data = [];
    
    // Handle avatar upload
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = UPLOAD_PATH . '/avatars';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                throw new Exception('Không thể tạo thư mục tải lên');
            }
            chmod($uploadDir, 0777);
        }
        
        $result = Util::uploadFile($_FILES['avatar'], $uploadDir);
        if ($result['success']) {
            $data['avatar'] = $result['filename'];
            $user = User::getById($profileId);
            if (!empty($user['avatar'])) {
                $oldAvatar = $uploadDir . '/' . $user['avatar'];
                if (file_exists($oldAvatar)) {
                    unlink($oldAvatar);
                }
            }
            $response['avatarUrl'] = '../uploads/avatars/' . $result['filename'];
        } else {
            throw new Exception($result['message']);
        }
    }

    // Collect form data
    $data['name'] = trim($_POST['name'] ?? '');
    $data['bio'] = trim($_POST['bio'] ?? '');
    $data['social_links'] = trim($_POST['social_links'] ?? '');
    $data['followers'] = str_replace(',', '', trim($_POST['followers'] ?? '')); 
    $selectedCategories = $_POST['categories'] ?? [];

    // Validate required fields
    if (empty($data['name'])) {
        throw new Exception('Vui lòng nhập tên hiển thị');
    }

    // Validate followers
    if ($data['followers'] !== '' && (!is_numeric($data['followers']) || $data['followers'] < 0)) {
        throw new Exception('Số lượng người theo dõi phải là số không âm');
    }

    // Validate social links
    if ($data['social_links']) {
        $links = array_filter(array_map('trim', explode("\n", $data['social_links'])));
        foreach ($links as $link) {
            if (!filter_var($link, FILTER_VALIDATE_URL)) {
                throw new Exception("Liên kết không hợp lệ: $link");
            }
        }
        $data['social_links'] = implode("\n", $links);
    }

    // Update profile
    $userObj = new User();
    $result = $userObj->updateProfile($data);
    
    if ($result['success']) {
        $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($db->connect_error) {
            throw new Exception("Lỗi kết nối cơ sở dữ liệu: " . $db->connect_error);
        }

        // Xóa các category cũ
        $deleteQuery = "DELETE FROM user_categories WHERE user_id = ?";
        $stmt = $db->prepare($deleteQuery);
        $stmt->bind_param("i", $profileId);
        $stmt->execute();

        // Thêm các category mới
        if (!empty($selectedCategories)) {
            $insertQuery = "INSERT INTO user_categories (user_id, category_id) VALUES (?, ?)";
            $stmt = $db->prepare($insertQuery);
            foreach ($selectedCategories as $categoryId) {
                if (!is_numeric($categoryId)) {
                    throw new Exception("ID danh mục không hợp lệ: $categoryId");
                }
                $stmt->bind_param("ii", $profileId, $categoryId);
                $stmt->execute();
            }
        }
        
        $db->close();

        $response['success'] = true;
        $response['message'] = 'Hồ sơ đã được cập nhật thành công';
    } else {
        throw new Exception($result['message'] ?? 'Không thể cập nhật hồ sơ');
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
exit;