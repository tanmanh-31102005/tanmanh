<?php
require_once 'config.php';
require_once '../includes/Util.php';
require_once '../models/User.php';

// Redirect if already logged in
if (Util::isAuthenticated()) {
    Util::redirect('../view/');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = Util::sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Vui lòng điền đầy đủ thông tin';
    } elseif (!Util::validateEmail($email)) {
        $error = 'Email không hợp lệ';
    } else {
        $user = new User();
        $result = $user->login($email, $password);

        if ($result['success']) {
            Util::flashMessage('success', 'Đăng nhập thành công');
            Util::redirect('../view/');
        } else {
            $error = $result['message'];
        }
    }
}

require_once '../includes/header.php';
?>
<link rel="stylesheet" href="../assets/css/lar.css">
<div class="auth-container">
    <h2 class="auth-heading">Đăng nhập</h2>
    
    <?php if ($error): ?>
        <div class="flash-message error">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" class="form-control" value="<?= isset($email) ? htmlspecialchars($email) : '' ?>" required>
        </div>

        <div class="form-group">
            <label for="password">Mật khẩu</label>
            <input type="password" id="password" name="password" class="form-control" required>
        </div>

        <div class="form-group">
            <a href="forgot_password.php" class="auth-link">Quên mật khẩu?</a>
        </div>

        <div class="form-submit">
            <button type="submit" class="btn btn-full">Đăng nhập</button>
        </div>

        <div class="auth-links">
            <p>Chưa có tài khoản? <a href="register.php" class="auth-link">Đăng ký ngay</a></p>
        </div>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>