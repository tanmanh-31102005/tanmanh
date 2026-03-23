<?php
require_once 'config.php';
require_once '../includes/Util.php';

http_response_code(404);
require_once '../includes/header.php';
?>

<div style="
    min-height: 60vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 2rem;
">
    <h1 style="font-size: 4rem; margin-bottom: 1rem;">404</h1>
    <p style="font-size: 1.5rem; color: var(--text-secondary); margin-bottom: 2rem;">
        Không tìm thấy trang bạn yêu cầu
    </p>
    <a href="index.php" class="btn">Về trang chủ</a>
</div>

<?php require_once '../includes/footer.php'; ?>