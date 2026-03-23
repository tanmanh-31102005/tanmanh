<?php
require_once __DIR__ . '/../includes/Database.php';

class Service {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function create($userId, $data) {
        try {
            $sql = "INSERT INTO services (user_id, name, rate, description) VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $userId,
                $data['name'],
                $data['rate'],
                $data['description']
            ]);
            return ['success' => true, 'id' => $this->db->getConnection()->lastInsertId()];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    public function update($serviceId, $userId, $data) {
        try {
            $sql = "UPDATE services SET name = ?, rate = ?, description = ? 
                    WHERE id = ? AND user_id = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $data['name'],
                $data['rate'],
                $data['description'],
                $serviceId,
                $userId
            ]);
            return ['success' => $result];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    public function delete($serviceId, $userId) {
        try {
            // Kiểm tra các bản ghi đặt dịch vụ đang hoạt động
            $sqlCheck = "SELECT COUNT(*) FROM bookings WHERE service_id = ? AND status IN ('pending', 'accepted')";
            $stmtCheck = $this->db->prepare($sqlCheck);
            $stmtCheck->execute([$serviceId]);
            $activeBookings = $stmtCheck->fetchColumn();
        
            if ($activeBookings > 0) {
                return ['success' => false, 'message' => 'Không thể xóa dịch vụ vì có các đơn đặt đang hoạt động'];
            }
        
            // Lấy đối tượng PDO
            $pdo = $this->db->getConnection();
        
            // Bắt đầu giao dịch
            $pdo->beginTransaction();
        
            // Xóa các bản ghi thanh toán liên quan
            $sqlPayments = "DELETE p FROM payments p INNER JOIN bookings b ON p.booking_id = b.id WHERE b.service_id = ?";
            $stmtPayments = $this->db->prepare($sqlPayments);
            $stmtPayments->execute([$serviceId]);
        
            // Xóa các bản ghi đặt dịch vụ liên quan
            $sqlBookings = "DELETE FROM bookings WHERE service_id = ?";
            $stmtBookings = $this->db->prepare($sqlBookings);
            $stmtBookings->execute([$serviceId]);
        
            // Xóa dịch vụ
            $sqlService = "DELETE FROM services WHERE id = ? AND user_id = ?";
            $stmtService = $this->db->prepare($sqlService);
            $result = $stmtService->execute([$serviceId, $userId]);
        
            // Hoàn tất giao dịch
            $pdo->commit();
        
            return ['success' => $result];
        } catch (PDOException $e) {
            // Hoàn tác giao dịch nếu có lỗi
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Lỗi cơ sở dữ liệu: ' . $e->getMessage()];
        }
    }

    public function getByUserId($userId) {
        $sql = "SELECT * FROM services WHERE user_id = ? ORDER BY id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getById($serviceId) {
        $sql = "SELECT * FROM services WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$serviceId]);
        return $stmt->fetch();
    }

    public function getWithBookingStats($userId) {
        $sql = "SELECT s.*, 
                COUNT(DISTINCT b.id) as total_bookings,
                COUNT(DISTINCT CASE WHEN b.status = 'completed' THEN b.id END) as completed_bookings,
                AVG(CASE WHEN r.rating IS NOT NULL THEN r.rating ELSE NULL END) as avg_rating
                FROM services s
                LEFT JOIN bookings b ON s.id = b.service_id
                LEFT JOIN reviews r ON b.id = r.booking_id
                WHERE s.user_id = ?
                GROUP BY s.id
                ORDER BY s.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}