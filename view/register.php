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
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $role = Util::sanitizeInput($_POST['role'] ?? '');

    if (empty($email) || empty($password) || empty($confirmPassword) || empty($role)) {
        $error = 'Vui lòng điền đầy đủ thông tin';
    } elseif (!Util::validateEmail($email)) {
        $error = 'Email không hợp lệ';
    } elseif (!Util::validatePassword($password)) {
        $error = 'Mật khẩu phải có ít nhất 8 ký tự, bao gồm chữ hoa, chữ thường và số';
    } elseif ($password !== $confirmPassword) {
        $error = 'Mật khẩu xác nhận không khớp';
    } elseif (!in_array($role, ['brand', 'kol_koc'])) {
        $error = 'Vai trò không hợp lệ';
    } else {
        $user = new User();
        $result = $user->register($email, $password, $role);

        if ($result['success']) {
            Util::flashMessage('success', 'Đăng ký thành công! Vui lòng chờ phê duyệt từ admin.');
            Util::redirect('../view/login.php');
        } else {
            $error = $result['message'];
        }
    }
}

require_once '../includes/header.php';
?>
<link rel="stylesheet" href="../assets/css/lar.css">
<div class="auth-container">
    <h2 class="auth-heading">Đăng ký tài khoản</h2>
    
    <?php if ($error): ?>
        <div class="flash-message error">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" class="form-control" 
                value="<?= isset($email) ? htmlspecialchars($email) : '' ?>" required>
        </div>

        <div class="form-group">
            <label for="password">Mật khẩu</label>
            <input type="password" id="password" name="password" class="form-control" 
                required minlength="8" 
                title="Mật khẩu phải có ít nhất 8 ký tự, bao gồm chữ hoa, chữ thường và số">
        </div>

        <div class="form-group">
            <label for="confirm_password">Xác nhận mật khẩu</label>
            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
        </div>

        <div class="form-group">
            <label>Bạn là</label>
            <div class="role-container">
                <label class="role-option">
                    <input type="radio" name="role" value="brand" required
                        <?= (isset($role) && $role === 'brand') ? 'checked' : '' ?>>
                    <span class="role-text">Thương hiệu</span>
                </label>
                <label class="role-option">
                    <input type="radio" name="role" value="kol_koc" required
                        <?= (isset($role) && $role === 'kol_koc') ? 'checked' : '' ?>>
                    <span class="role-text">KOL/KOC</span>
                </label>
            </div>
        </div>

        <div class="form-submit">
            <button type="submit" class="btn btn-full">Đăng ký</button>
        </div>

        <div class="auth-links">
            <p>Đã có tài khoản? <a href="login.php" class="auth-link">Đăng nhập</a></p>
        </div>
    </form>
</div>

<script>
    // Password match validation
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');

    function validatePassword() {
        if (password.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('Mật khẩu xác nhận không khớp');
        } else {
            confirmPassword.setCustomValidity('');
        }
    }

    password.addEventListener('change', validatePassword);
    confirmPassword.addEventListener('keyup', validatePassword);
</script>

<?php require_once '../includes/footer.php'; ?>