<?php
require_once 'config.php';
require_once '../includes/Util.php';
require_once '../models/User.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Yêu cầu người dùng đã đăng nhập
if (!Util::isAuthenticated()) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để thực hiện hành động này']);
    exit;
}

// Xử lý đổi mật khẩu qua AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false];
    
    try {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            throw new Exception('Vui lòng điền đầy đủ thông tin');
        }

        if ($newPassword !== $confirmPassword) {
            throw new Exception('Mật khẩu mới và xác nhận mật khẩu không khớp');
        }
        
        $userObj = new User();
        $result = $userObj->changePassword($currentPassword, $newPassword);
        
        if ($result['success']) {
            $response['success'] = true;
            $response['message'] = $result['message'];
        } else {
            throw new Exception($result['message']);
        }
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
    
    // Trả về kết quả dưới dạng JSON
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Nếu không phải yêu cầu POST, trả về lỗi
header('HTTP/1.1 400 Bad Request');
echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ']);
exit;