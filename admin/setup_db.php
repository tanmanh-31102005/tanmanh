<?php
require_once '../view/config.php';
require_once '../includes/Util.php';
require_once '../includes/Database.php';

// Require admin authentication
Util::requireAuth();
Util::requireRole('admin');

$message = '';

try {
    $db = Database::getInstance();
    
    // Begin transaction
    $db->getConnection()->beginTransaction();
    
    // Add scheduled_date to bookings with NULL allowed
    $db->query("ALTER TABLE bookings ADD COLUMN IF NOT EXISTS scheduled_date DATETIME");
    $message .= "✅ Added scheduled_date to bookings table<br>";

    // Add status column to bookings if not exists
    $db->query("ALTER TABLE bookings ADD COLUMN IF NOT EXISTS status ENUM('pending', 'confirmed', 'completed', 'cancelled') NOT NULL DEFAULT 'pending'");
    $message .= "✅ Added status to bookings table<br>";
    
    // Add price column to services if not exists
    $db->query("ALTER TABLE services ADD COLUMN IF NOT EXISTS price DECIMAL(10, 2) NOT NULL DEFAULT 0.00");
    $message .= "✅ Added price to services table<br>";
    $message .= "✅ Added price to bookings table<br>";
    
    // Create payments table
    $db->query("CREATE TABLE IF NOT EXISTS payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        booking_id INT NOT NULL,
        amount DECIMAL(10, 2) NOT NULL,
        status ENUM('pending', 'paid', 'failed') NOT NULL DEFAULT 'pending',
        payment_method ENUM('bank_transfer', 'momo', 'zalopay') NOT NULL DEFAULT 'bank_transfer',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
    )");
    $message .= "✅ Created payments table<br>";
    
    // Commit transaction
    $db->getConnection()->commit();
    
    $message .= "<br>✨ Database setup completed successfully!";
    
} catch (PDOException $e) {
    // Rollback on error
    $db->getConnection()->rollBack();
    $message = "❌ Error: " . $e->getMessage();
}

require_once '../includes/header.php';
?>
<link rel="stylesheet" href="assets/css/admin3.css">
<div class="container" style="max-width: 800px;">
    <div>
        <h1>Cài đặt cơ sở dữ liệu</h1>
        <a href="dashboard.php" class="btn">
            ← Quay lại
        </a>
    </div>

    <div>
        <h2 style="margin-bottom: 1rem;">Kết quả cài đặt</h2>
        <div class="setup-result">
            <?= $message ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>