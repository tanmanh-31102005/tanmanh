<?php
require_once '../view/config.php';
require_once '../includes/Database.php';
require_once '../vendor/jpgraph/src/jpgraph.php';
require_once '../vendor/jpgraph/src/jpgraph_bar.php';

// Lấy dữ liệu thống kê
$db = Database::getInstance();
$stats = [
    'total_users' => $db->query("SELECT COUNT(*) as count FROM users")->fetch()['count'],
    'pending_users' => $db->query("SELECT COUNT(*) as count FROM users WHERE status = 'pending'")->fetch()['count'],
    'total_bookings' => $db->query("SELECT COUNT(*) as count FROM bookings")->fetch()['count'],
    'total_revenue' => $db->query("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE status = 'paid'")->fetch()['total'] / 1000000, // Triệu VNĐ
    'pending_payments' => $db->query("SELECT COUNT(*) as count FROM payments WHERE status = 'pending'")->fetch()['count']
];

// Dữ liệu và nhãn cho biểu đồ
$data = [
    $stats['total_users'],
    $stats['pending_users'],
    $stats['total_bookings'],
    $stats['total_revenue'],
    $stats['pending_payments']
];
$labels = ['Tổng người dùng', 'Chờ duyệt', 'Tổng đơn', 'Doanh thu (triệu VNĐ)', 'Thanh toán chờ'];

// Tạo biểu đồ
$graph = new Graph(600, 300, 'auto');
$graph->SetScale('textlin');
$graph->SetFrame(true, 'white', 1);
$graph->SetMargin(60, 30, 30, 60);

// Tiêu đề
$graph->title->Set('Thống kê Tổng quan');
$graph->title->SetFont(FF_DEJAVU, FS_NORMAL, 12);
$graph->title->SetColor('black');

// Nhãn trục X
$graph->xaxis->SetTickLabels($labels);
$graph->xaxis->SetFont(FF_DEJAVU, FS_NORMAL, 8);
$graph->xaxis->SetLabelAngle(45);

// Nhãn trục Y
$graph->yaxis->SetFont(FF_DEJAVU, FS_NORMAL, 8);
$graph->yaxis->title->Set('Giá trị');
$graph->yaxis->title->SetFont(FF_DEJAVU, FS_NORMAL, 10);

// Tạo biểu đồ cột
$barplot = new BarPlot($data);
$barplot->SetFillColor(['#2563eb', '#f59e0b', '#2563eb', '#10b981', '#f59e0b']);
$barplot->SetWidth(0.15);
$barplot->SetLegend('Thống kê');

// Thêm cột vào biểu đồ
$graph->Add($barplot);

// Lưu biểu đồ thành ảnh
$graph->Stroke(UPLOAD_PATH . '/chart.png');
?>