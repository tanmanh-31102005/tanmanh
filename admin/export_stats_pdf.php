<?php
// Bật bộ đệm đầu ra để ngăn lỗi "data has already been output"
ob_start();

require_once '../view/config.php';
require_once '../includes/Util.php';
require_once '../models/User.php';
require_once '../vendor/autoload.php'; // Tải TCPDF qua Composer

// Yêu cầu xác thực admin
Util::requireAuth();
Util::requireRole('admin');

// Khởi tạo TCPDF
$pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Thiết lập thông tin tài liệu
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('KOL/KOC Booking System');
$pdf->SetTitle('Thống kê Chi tiết Bảng điều khiển Admin');
$pdf->SetSubject('Báo cáo Thống kê Chi tiết');
$pdf->SetKeywords('thống kê, admin, báo cáo, chi tiết, PDF');

// Thiết lập font hỗ trợ tiếng Việt
$pdf->SetFont('dejavusans', '', 10);

// Thiết lập lề
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// Thiết lập tiêu đề và chân trang tự động
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// Thêm trang mới
$pdf->AddPage();

// Lấy dữ liệu thống kê
$db = Database::getInstance();

// 1. Thống kê người dùng
$stats = [
    'total_users' => $db->query("SELECT COUNT(*) as count FROM users")->fetch()['count'],
    'admin_users' => $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'")->fetch()['count'],
    'brand_users' => $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'brand'")->fetch()['count'],
    'kol_koc_users' => $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'kol_koc'")->fetch()['count'],
    'pending_users' => $db->query("SELECT COUNT(*) as count FROM users WHERE status = 'pending'")->fetch()['count'],
    'active_users' => $db->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'")->fetch()['count'],
    'locked_users' => $db->query("SELECT COUNT(*) as count FROM users WHERE status = 'locked'")->fetch()['count'],
    'total_bookings' => $db->query("SELECT COUNT(*) as count FROM bookings")->fetch()['count'],
    'total_revenue' => $db->query("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE status = 'paid'")->fetch()['total'],
    'pending_payments' => $db->query("SELECT COUNT(*) as count FROM payments WHERE status = 'pending'")->fetch()['count'],
    'paid_payments' => $db->query("SELECT COUNT(*) as count FROM payments WHERE status = 'paid'")->fetch()['count'],
    'total_categories' => $db->query("SELECT COUNT(*) as count FROM categories")->fetch()['count'],
    'total_services' => $db->query("SELECT COUNT(*) as count FROM services")->fetch()['count'],
    'total_reviews' => $db->query("SELECT COUNT(*) as count FROM reviews")->fetch()['count'],
];

// 2. Chi tiết người dùng
$users = $db->query("
    SELECT u.*, p.name, p.followers 
    FROM users u 
    LEFT JOIN profiles p ON u.id = p.user_id 
    ORDER BY u.created_at DESC
")->fetchAll();

// 3. Chi tiết đơn đặt
$bookings = $db->query("
    SELECT b.*, pb.name as brand_name, pk.name as kol_name, s.name as service_name 
    FROM bookings b 
    JOIN profiles pb ON b.brand_id = pb.user_id 
    JOIN profiles pk ON b.kol_koc_id = pk.user_id 
    JOIN services s ON b.service_id = s.id 
    ORDER BY b.created_at DESC
")->fetchAll();

// 4. Chi tiết thanh toán
$payments = $db->query("
    SELECT p.*, pb.name as brand_name, pk.name as kol_name 
    FROM payments p 
    JOIN bookings b ON p.booking_id = b.id 
    JOIN profiles pb ON b.brand_id = pb.user_id 
    JOIN profiles pk ON b.kol_koc_id = pk.user_id 
    ORDER BY p.created_at DESC
")->fetchAll();

// 5. Chi tiết danh mục
$categories = $db->query("
    SELECT c.name, 
           COUNT(DISTINCT uc.user_id) as total_users,
           COUNT(DISTINCT b.id) as total_bookings
    FROM categories c 
    LEFT JOIN user_categories uc ON c.id = uc.category_id 
    LEFT JOIN users u ON uc.user_id = u.id 
    LEFT JOIN bookings b ON u.id = b.kol_koc_id OR u.id = b.brand_id
    GROUP BY c.id 
    ORDER BY total_users DESC
")->fetchAll();

// 6. Chi tiết dịch vụ
$services = $db->query("
    SELECT s.*, p.name as kol_name 
    FROM services s 
    JOIN profiles p ON s.user_id = p.user_id 
    ORDER BY s.rate DESC
")->fetchAll();

// 7. Chi tiết đánh giá
$reviews = $db->query("
    SELECT r.*, pb.name as brand_name, pk.name as kol_name 
    FROM reviews r 
    JOIN bookings b ON r.booking_id = b.id 
    JOIN profiles pb ON b.brand_id = pb.user_id 
    JOIN profiles pk ON b.kol_koc_id = pk.user_id 
    ORDER BY r.created_at DESC
")->fetchAll();

// Tạo nội dung HTML cho PDF
$html = '
<h1 style="text-align: center; color: #2563eb;">Báo cáo Thống kê Chi tiết - Bảng điều khiển Admin</h1>
<div style="margin-bottom: 20px;"></div>

<h2 style="color: #1e40af;">1. Tổng quan</h2>
<table border="1" cellpadding="8" cellspacing="0" style="width: 100%; border-collapse: collapse;">
    <tr style="background-color: #f3f4f6;">
        <th style="text-align: left;">Chỉ số</th>
        <th style="text-align: right;">Giá trị</th>
    </tr>
    <tr>
        <td>Tổng người dùng</td>
        <td style="text-align: right; color: #2563eb;">' . number_format($stats['total_users']) . '</td>
    </tr>
    <tr>
        <td>Người dùng Admin</td>
        <td style="text-align: right; color: #2563eb;">' . number_format($stats['admin_users']) . '</td>
    </tr>
    <tr>
        <td>Người dùng Brand</td>
        <td style="text-align: right; color: #2563eb;">' . number_format($stats['brand_users']) . '</td>
    </tr>
    <tr>
        <td>Người dùng KOL/KOC</td>
        <td style="text-align: right; color: #2563eb;">' . number_format($stats['kol_koc_users']) . '</td>
    </tr>
    <tr>
        <td>Người dùng chờ duyệt</td>
        <td style="text-align: right; color: #f59e0b;">' . number_format($stats['pending_users']) . '</td>
    </tr>
    <tr>
        <td>Người dùng hoạt động</td>
        <td style="text-align: right; color: #10b981;">' . number_format($stats['active_users']) . '</td>
    </tr>
    <tr>
        <td>Người dùng bị khóa</td>
        <td style="text-align: right; color: #ef4444;">' . number_format($stats['locked_users']) . '</td>
    </tr>
    <tr>
        <td>Tổng đơn đặt</td>
        <td style="text-align: right; color: #2563eb;">' . number_format($stats['total_bookings']) . '</td>
    </tr>
    <tr>
        <td>Doanh thu (Đã thanh toán)</td>
        <td style="text-align: right; color: #10b981;">' . number_format($stats['total_revenue']) . ' VNĐ</td>
    </tr>
    <tr>
        <td>Thanh toán chờ xử lý</td>
        <td style="text-align: right; color: #f59e0b;">' . number_format($stats['pending_payments']) . '</td>
    </tr>
    <tr>
        <td>Thanh toán đã hoàn tất</td>
        <td style="text-align: right; color: #10b981;">' . number_format($stats['paid_payments']) . '</td>
    </tr>
    <tr>
        <td>Tổng danh mục</td>
        <td style="text-align: right; color: #2563eb;">' . number_format($stats['total_categories']) . '</td>
    </tr>
    <tr>
        <td>Tổng dịch vụ</td>
        <td style="text-align: right; color: #2563eb;">' . number_format($stats['total_services']) . '</td>
    </tr>
    <tr>
        <td>Tổng đánh giá</td>
        <td style="text-align: right; color: #2563eb;">' . number_format($stats['total_reviews']) . '</td>
    </tr>
</table>

<div style="page-break-before: always;"></div>
<h2 style="color: #1e40af;">2. Chi tiết người dùng</h2>
<table border="1" cellpadding="8" cellspacing="0" style="width: 100%; border-collapse: collapse;">
    <tr style="background-color: #f3f4f6;">
        <th>Email</th>
        <th>Tên</th>
        <th>Vai trò</th>
        <th>Trạng thái</th>
        <th>Followers</th>
        <th>Ngày đăng ký</th>
    </tr>';
foreach ($users as $user) {
    $html .= '
    <tr>
        <td>' . htmlspecialchars($user['email']) . '</td>
        <td>' . htmlspecialchars($user['name'] ?: $user['email']) . '</td>
        <td>' . ($user['role'] === 'kol_koc' ? 'KOL/KOC' : ucfirst($user['role'])) . '</td>
        <td>' . ($user['status'] === 'active' ? 'Hoạt động' : ($user['status'] === 'pending' ? 'Chờ duyệt' : 'Đã khóa')) . '</td>
        <td style="text-align: right;">' . number_format($user['followers'] ?? 0) . '</td>
        <td>' . date('d/m/Y', strtotime($user['created_at'])) . '</td>
    </tr>';
}
$html .= '
</table>

<div style="page-break-before: always;"></div>
<h2 style="color: #1e40af;">3. Chi tiết đơn đặt</h2>
<table border="1" cellpadding="8" cellspacing="0" style="width: 100%; border-collapse: collapse;">
    <tr style="background-color: #f3f4f6;">
        <th>ID</th>
        <th>Brand</th>
        <th>KOL/KOC</th>
        <th>Dịch vụ</th>
        <th>Trạng thái</th>
        <th>Số bài đăng</th>
        <th>Deadline</th>
        <th>Ngày tạo</th>
    </tr>';
foreach ($bookings as $booking) {
    $html .= '
    <tr>
        <td>' . $booking['id'] . '</td>
        <td>' . htmlspecialchars($booking['brand_name']) . '</td>
        <td>' . htmlspecialchars($booking['kol_name']) . '</td>
        <td>' . htmlspecialchars($booking['service_name']) . '</td>
        <td>' . ($booking['status'] === 'completed' ? 'Hoàn thành' : ucfirst($booking['status'])) . '</td>
        <td style="text-align: right;">' . $booking['posts'] . '</td>
        <td>' . date('d/m/Y', strtotime($booking['deadline'])) . '</td>
        <td>' . date('d/m/Y H:i', strtotime($booking['created_at'])) . '</td>
    </tr>';
}
$html .= '
</table>

<div style="page-break-before: always;"></div>
<h2 style="color: #1e40af;">4. Chi tiết thanh toán</h2>
<table border="1" cellpadding="8" cellspacing="0" style="width: 100%; border-collapse: collapse;">
    <tr style="background-color: #f3f4f6;">
        <th>ID</th>
        <th>Booking ID</th>
        <th>Brand</th>
        <th>KOL/KOC</th>
        <th>Số tiền</th>
        <th>Trạng thái</th>
        <th>Phương thức</th>
        <th>Ngày tạo</th>
    </tr>';
foreach ($payments as $payment) {
    $html .= '
    <tr>
        <td>' . $payment['id'] . '</td>
        <td>' . $payment['booking_id'] . '</td>
        <td>' . htmlspecialchars($payment['brand_name']) . '</td>
        <td>' . htmlspecialchars($payment['kol_name']) . '</td>
        <td style="text-align: right; color: #10b981;">' . number_format($payment['amount']) . ' VNĐ</td>
        <td>' . ($payment['status'] === 'paid' ? 'Đã thanh toán' : ($payment['status'] === 'pending' ? 'Chờ xử lý' : 'Thất bại')) . '</td>
        <td>' . ($payment['payment_method'] === 'bank_transfer' ? 'Chuyển khoản' : $payment['payment_method']) . '</td>
        <td>' . date('d/m/Y H:i', strtotime($payment['created_at'])) . '</td>
    </tr>';
}
$html .= '
</table>

<div style="page-break-before: always;"></div>
<h2 style="color: #1e40af;">5. Chi tiết danh mục</h2>
<table border="1" cellpadding="8" cellspacing="0" style="width: 100%; border-collapse: collapse;">
    <tr style="background-color: #f3f4f6;">
        <th>Tên danh mục</th>
        <th>Số người dùng</th>
        <th>Số đơn đặt</th>
    </tr>';
foreach ($categories as $category) {
    $html .= '
    <tr>
        <td>' . htmlspecialchars($category['name']) . '</td>
        <td style="text-align: right;">' . number_format($category['total_users']) . '</td>
        <td style="text-align: right;">' . number_format($category['total_bookings']) . '</td>
    </tr>';
}
$html .= '
</table>

<div style="page-break-before: always;"></div>
<h2 style="color: #1e40af;">6. Chi tiết dịch vụ</h2>
<table border="1" cellpadding="8" cellspacing="0" style="width: 100%; border-collapse: collapse;">
    <tr style="background-color: #f3f4f6;">
        <th>Tên dịch vụ</th>
        <th>KOL/KOC</th>
        <th>Giá</th>
        <th>Mô tả</th>
    </tr>';
foreach ($services as $service) {
    $html .= '
    <tr>
        <td>' . htmlspecialchars($service['name']) . '</td>
        <td>' . htmlspecialchars($service['kol_name']) . '</td>
        <td style="text-align: right; color: #10b981;">' . number_format($service['rate']) . ' VNĐ</td>
        <td>' . htmlspecialchars($service['description']) . '</td>
    </tr>';
}
$html .= '
</table>

<div style="page-break-before: always;"></div>
<h2 style="color: #1e40af;">7. Chi tiết đánh giá</h2>
<table border="1" cellpadding="8" cellspacing="0" style="width: 100%; border-collapse: collapse;">
    <tr style="background-color: #f3f4f6;">
        <th>Booking ID</th>
        <th>Brand</th>
        <th>KOL/KOC</th>
        <th>Rating</th>
        <th>Bình luận</th>
        <th>Ngày tạo</th>
    </tr>';
foreach ($reviews as $review) {
    $html .= '
    <tr>
        <td>' . $review['booking_id'] . '</td>
        <td>' . htmlspecialchars($review['brand_name']) . '</td>
        <td>' . htmlspecialchars($review['kol_name']) . '</td>
        <td style="text-align: right;">' . $review['rating'] . ' sao</td>
        <td>' . htmlspecialchars($review['comment']) . '</td>
        <td>' . date('d/m/Y H:i', strtotime($review['created_at'])) . '</td>
    </tr>';
}
$html .= '
</table>';

// Ghi nội dung HTML vào PDF
$pdf->writeHTML($html, true, false, true, false, '');

// Xóa bộ đệm đầu ra trước khi gửi PDF
ob_end_clean();

// Xuất PDF
$pdf->Output('thong-ke-chi-tiet-admin-' . date('YmdHis') . '.pdf', 'D');
?>