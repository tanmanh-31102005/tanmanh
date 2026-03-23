<?php
require_once 'config.php';
require_once '../includes/Database.php';

try {
    $db = Database::getInstance();
    
    // Add scheduled_date to bookings
    $db->query("ALTER TABLE bookings ADD COLUMN IF NOT EXISTS scheduled_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
    echo "Added scheduled_date to bookings table\n";
    
    // Add price to bookings
    $db->query("ALTER TABLE bookings ADD COLUMN IF NOT EXISTS price DECIMAL(10, 2) NOT NULL DEFAULT 0.00");
    echo "Added price to bookings table\n";
    
    // Create payments table
    $db->query("CREATE TABLE IF NOT EXISTS payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        booking_id INT NOT NULL,
        amount DECIMAL(10, 2) NOT NULL,
        status ENUM('pending', 'paid', 'failed') NOT NULL DEFAULT 'pending',
        payment_method ENUM('bank_transfer', 'momo', 'zalopay') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
    )");
    echo "Created payments table\n";
    
    echo "Database setup completed successfully!";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}