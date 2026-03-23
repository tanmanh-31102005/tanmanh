<?php
require_once 'config.php';
require_once '../includes/Util.php';
require_once '../includes/Database.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = Util::sanitizeInput($_POST['email'] ?? '');

    if (!Util::validateEmail($email)) {
        $message = 'Email không hợp lệ';
    } else {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $token = Util::generateResetToken();
            $stmt = $db->prepare("INSERT INTO password_resets (email, token) VALUES (?, ?) ON DUPLICATE KEY UPDATE token = ?, created_at = NOW()");
            $stmt->execute([$email, $token, $token]);

            $result = Util::sendResetEmail($email, $token);
            if ($result['success']) {
                $message = 'Liên kết khôi phục đã được gửi đến email của bạn';
            } else {
                $message = $result['message'];
            }
        } else {
            $message = 'Email không tồn tại';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên mật khẩu</title>
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body>
    <div class="auth-container">
        <h2 class="auth-heading">Khôi phục mật khẩu</h2>

        <?php if ($message): ?>
            <div class="flash-message <?= strpos($message, 'Liên kết khôi phục đã được gửi') !== false ? 'success' : 'error' ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>

            <div class="form-submit">
                <button type="submit" class="btn btn-full">Gửi liên kết khôi phục</button>
            </div>

            <div class="auth-links">
                <p><a href="login.php" class="auth-link">Quay lại đăng nhập</a></p>
            </div>
        </form>
    </div>
</body>
</html>