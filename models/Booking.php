<?php
require_once __DIR__ . '/../includes/Database.php';

class Booking {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function create($brandId, $kolKocId, $serviceId, $data) {
        try {
            $sql = "INSERT INTO bookings (brand_id, kol_koc_id, service_id, posts, deadline) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $brandId,
                $kolKocId,
                $serviceId,
                $data['posts'],
                $data['deadline']
            ]);

            if ($result) {
                $bookingId = $this->db->getConnection()->lastInsertId();
                
                // Create initial payment record
                $service = $this->getServiceDetails($serviceId);
                $amount = $service['price'];
                
                $sql = "INSERT INTO payments (booking_id, amount, status, payment_method) VALUES (?, ?, ?, ?)";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$bookingId, $service['rate'], 'pending', 'bank_transfer']);

                return ['success' => true, 'booking_id' => $bookingId];
            }
            return ['success' => false, 'message' => 'Không thể tạo đơn đặt'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    public function updateStatus($bookingId, $userId, $newStatus) {
        try {
            // Verify user has permission to update this booking
            $booking = $this->getById($bookingId);
            if (!$booking || ($booking['brand_id'] !== $userId && $booking['kol_koc_id'] !== $userId)) {
                return ['success' => false, 'message' => 'Không có quyền cập nhật đơn đặt này'];
            }

            $sql = "UPDATE bookings SET status = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$newStatus, $bookingId]);

            if ($result) {
                return ['success' => true];
            }
            return ['success' => false, 'message' => 'Không thể cập nhật trạng thái'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    public function getById($bookingId) {
        $sql = "SELECT b.*, 
                s.name as service_name, s.rate as service_price,
                bp.name as brand_name, bp.avatar as brand_avatar,
                kp.name as kol_koc_name, kp.avatar as kol_koc_avatar,
                p.status as payment_status, p.amount as payment_amount
                FROM bookings b
                JOIN services s ON b.service_id = s.id
                JOIN users bu ON b.brand_id = bu.id
                JOIN profiles bp ON bu.id = bp.user_id
                JOIN users ku ON b.kol_koc_id = ku.id
                JOIN profiles kp ON ku.id = kp.user_id
                LEFT JOIN payments p ON b.id = p.booking_id
                WHERE b.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$bookingId]);
        return $stmt->fetch();
    }

    public function getByUserId($userId, $role = null) {
        $sql = "SELECT b.*, 
                s.name as service_name, s.rate as service_price,
                bp.name as brand_name, bp.avatar as brand_avatar,
                kp.name as kol_koc_name, kp.avatar as kol_koc_avatar,
                p.status as payment_status, p.amount as payment_amount
                FROM bookings b
                JOIN services s ON b.service_id = s.id
                JOIN users bu ON b.brand_id = bu.id
                JOIN profiles bp ON bu.id = bp.user_id
                JOIN users ku ON b.kol_koc_id = ku.id
                JOIN profiles kp ON ku.id = kp.user_id
                LEFT JOIN payments p ON b.id = p.booking_id
                WHERE " . ($role === 'brand' ? 'b.brand_id' : 'b.kol_koc_id') . " = ?
                ORDER BY b.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    private function getServiceDetails($serviceId) {
        $sql = "SELECT * FROM services WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$serviceId]);
        return $stmt->fetch();
    }

    public function canReview($bookingId, $userId) {
        $booking = $this->getById($bookingId);
        if (!$booking || $booking['status'] !== 'completed') {
            return false;
        }

        // Check if user is either the brand or the KOL/KOC
        if ($booking['brand_id'] !== $userId && $booking['kol_koc_id'] !== $userId) {
            return false;
        }

        // Check if user has already reviewed
        $sql = "SELECT id FROM reviews WHERE booking_id = ? AND reviewer_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$bookingId, $userId]);
        return !$stmt->fetch();
    }

    public function addReview($bookingId, $reviewerId, $rating, $comment) {
        try {
            if (!$this->canReview($bookingId, $reviewerId)) {
                return ['success' => false, 'message' => 'Không thể đánh giá đơn đặt này'];
            }

            $booking = $this->getById($bookingId);
            $targetId = $reviewerId === $booking['brand_id'] ? $booking['kol_koc_id'] : $booking['brand_id'];

            $sql = "INSERT INTO reviews (booking_id, reviewer_id, target_id, rating, comment) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $bookingId,
                $reviewerId,
                $targetId,
                $rating,
                $comment
            ]);

            return ['success' => $result];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    public function getReviews($bookingId) {
        $sql = "SELECT r.*, p.name as reviewer_name, p.avatar as reviewer_avatar
                FROM reviews r
                JOIN profiles p ON r.reviewer_id = p.user_id
                WHERE r.booking_id = ?
                ORDER BY r.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$bookingId]);
        return $stmt->fetchAll();
    }
}