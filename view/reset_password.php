<?php
require_once 'config.php';
require_once '../includes/Util.php';
require_once '../includes/Database.php';

$message = '';
$email = Util::sanitizeInput($_GET['email'] ?? '');
$token = Util::sanitizeInput($_GET['token'] ?? '');

if (empty($email) || empty($token)) {
    $message = 'Liên kết không hợp lệ';
} else {
    $verify = Util::verifyResetToken($email, $token);
    if (!$verify['success']) {
        $message = $verify['message'];
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if ($password !== $confirm_password) {
            $message = 'Mật khẩu xác nhận không khớp';
        } elseif (!Util::validatePassword($password)) {
            $message = 'Mật khẩu phải có ít nhất 8 ký tự, bao gồm chữ hoa, chữ thường và số';
        } else {
            $db = Database::getInstance();
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE email = ?");
            $result = $stmt->execute([$hashedPassword, $email]);

            if ($result) {
                Util::clearResetToken($email);
                Util::flashMessage('success', 'Đặt lại mật khẩu thành công. Vui lòng đăng nhập.');
                Util::redirect('login.php');
            } else {
                $message = 'Không thể đặt lại mật khẩu';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt lại mật khẩu</title>
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body>
    <div class="auth-container">
        <h2 class="auth-heading">Đặt lại mật khẩu</h2>

        <?php if ($message): ?>
            <div class="flash-message error">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <?php if ($verify['success'] ?? false): ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="password">Mật khẩu mới</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Xác nhận mật khẩu</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                </div>

                <div class="form-submit">
                    <button type="submit" class="btn btn-full">Đặt lại mật khẩu</button>
                </div>
            </form>
        <?php else: ?>
            <div class="auth-links">
                <p><a href="forgot_password.php" class="auth-link">Thử lại</a></p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>