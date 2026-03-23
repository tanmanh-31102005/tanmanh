<?php
require_once __DIR__ . '/../includes/Database.php';

class Payment {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function create($data) {
        try {
            $this->db->getConnection()->beginTransaction();

            $stmt = $this->db->prepare(
                "INSERT INTO payments (booking_id, amount, status, payment_method) 
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([
                $data['booking_id'],
                $data['amount'],
                $data['status'] ?? 'pending',
                $data['payment_method']
            ]);
            
            $paymentId = $this->db->getConnection()->lastInsertId();

            $this->db->getConnection()->commit();
            return ['success' => true, 'payment_id' => $paymentId];
        } catch (PDOException $e) {
            $this->db->getConnection()->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getById($id) {
        $stmt = $this->db->prepare(
            "SELECT p.*, b.brand_id, b.kol_koc_id, b.service_id, s.name as service_name,
             pb.name as brand_name, pb.avatar as brand_avatar,
             pk.name as kol_name, pk.avatar as kol_avatar
             FROM payments p
             JOIN bookings b ON p.booking_id = b.id
             JOIN services s ON b.service_id = s.id
             JOIN profiles pb ON b.brand_id = pb.user_id
             JOIN profiles pk ON b.kol_koc_id = pk.user_id
             WHERE p.id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getByBookingId($bookingId) {
        $stmt = $this->db->prepare(
            "SELECT * FROM payments WHERE booking_id = ?"
        );
        $stmt->execute([$bookingId]);
        return $stmt->fetch();
    }

    public function updateStatus($id, $status) {
        try {
            $this->db->getConnection()->beginTransaction();

            $stmt = $this->db->prepare(
                "UPDATE payments SET status = ? WHERE id = ?"
            );
            $result = $stmt->execute([$status, $id]);

            $this->db->getConnection()->commit();
            return ['success' => true];
        } catch (PDOException $e) {
            $this->db->getConnection()->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}