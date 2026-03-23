<?php
require_once __DIR__ . '/../includes/Database.php';

class User {
    private $db;
    private $id;
    private $email;
    private $role;
    private $status;

    public function __construct() {
        $this->db = Database::getInstance();
        // Set current user ID from session if available
        if (isset($_SESSION['user_id'])) {
            $this->id = $_SESSION['user_id'];
        }
    }

    public function login($email, $password) {
        $stmt = $this->db->prepare("SELECT id, email, password, role, status FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] === 'locked') {
                return ['success' => false, 'message' => 'Account is locked'];
            }
            
            if ($user['status'] === 'pending') {
                return ['success' => false, 'message' => 'Account is pending approval'];
            }

            $this->id = $user['id'];
            $this->email = $user['email'];
            $this->role = $user['role'];
            $this->status = $user['status'];

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            
            return ['success' => true, 'user' => $user];
        }
        
        return ['success' => false, 'message' => 'Invalid email or password'];
    }

    public function register($email, $password, $role) {
        try {
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Email already exists'];
            }

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $this->db->getConnection()->beginTransaction();
            
            $stmt = $this->db->prepare(
                "INSERT INTO users (email, password, role, status) VALUES (?, ?, ?, 'pending')"
            );
            $stmt->execute([$email, $hashedPassword, $role]);
            $userId = $this->db->getConnection()->lastInsertId();
            
            // Create empty profile for new user
            $stmt = $this->db->prepare(
                "INSERT INTO profiles (user_id, name) VALUES (?, ?)"
            );
            $stmt->execute([$userId, $email]);
            
            $this->db->getConnection()->commit();
            return ['success' => true, 'user_id' => $userId];
            
        } catch (PDOException $e) {
            $this->db->getConnection()->rollBack();
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    public function logout() {
        session_destroy();
        $this->id = null;
        $this->email = null;
        $this->role = null;
        $this->status = null;
    }

    public function getProfile() {
        if (!$this->id) {
            return null;
        }

        $stmt = $this->db->prepare(
            "SELECT p.*, GROUP_CONCAT(c.name) as categories 
             FROM profiles p 
             LEFT JOIN user_categories uc ON p.user_id = uc.user_id 
             LEFT JOIN categories c ON uc.category_id = c.id 
             WHERE p.user_id = ? 
             GROUP BY p.user_id"
        );
        $stmt->execute([$this->id]);
        return $stmt->fetch();
    }

    public function updateProfile($data) {
        if (!$this->id) {
            return ['success' => false, 'message' => 'User ID not set'];
        }
    
        try {
            // Start transaction
            $this->db->getConnection()->beginTransaction();
    
            // Prepare update query
            $updateFields = [];
            $params = [];
    
            // Add fields to update
            if (isset($data['name'])) {
                $updateFields[] = "name = ?";
                $params[] = $data['name'];
            }
            if (isset($data['bio'])) {
                $updateFields[] = "bio = ?";
                $params[] = $data['bio'];
            }
            if (isset($data['industry'])) {
                $updateFields[] = "industry = ?";
                $params[] = $data['industry'];
            }
            if (isset($data['social_links'])) {
                $updateFields[] = "social_links = ?";
                $params[] = $data['social_links'];
            }
            if (isset($data['avatar'])) {
                $updateFields[] = "avatar = ?";
                $params[] = $data['avatar'];
            }
            if (isset($data['followers'])) {
                $updateFields[] = "followers = ?";
                $params[] = $data['followers'] === '' ? null : (int)$data['followers'];
            }
    
            if (empty($updateFields)) {
                return ['success' => false, 'message' => 'No fields to update'];
            }
    
            // Add user_id to params
            $params[] = $this->id;
    
            // Build and execute query
            $sql = "UPDATE profiles SET " . implode(", ", $updateFields) . " WHERE user_id = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($params);
    
            if (!$result) {
                throw new PDOException("Failed to update profile");
            }
    
            // Commit transaction
            $this->db->getConnection()->commit();
    
            return ['success' => true];
        } catch (PDOException $e) {
            // Rollback on error
            $this->db->getConnection()->rollBack();
            return [
                'success' => false, 
                'message' => 'Database error: ' . $e->getMessage(),
                'debug' => [
                    'sql' => $sql ?? null,
                    'params' => $params ?? null,
                    'user_id' => $this->id
                ]
            ];
        }
    }

    public static function getById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT u.*, p.name, p.avatar, p.bio, p.industry, p.social_links, p.followers 
             FROM users u 
             LEFT JOIN profiles p ON u.id = p.user_id 
             WHERE u.id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // Getters
    public function getId() { return $this->id; }
    public function getEmail() { return $this->email; }
    public function getRole() { return $this->role; }
    public function getStatus() { return $this->status; }

    public static function delete($userId) {
        try {
            $db = Database::getInstance();
            $db->getConnection()->beginTransaction();
    
            // 1. Xóa các bản ghi trong messages liên quan đến user (sender hoặc receiver)
            $stmt = $db->prepare(
                "DELETE FROM messages WHERE sender_id = ? OR receiver_id = ?"
            );
            $stmt->execute([$userId, $userId]);
    
            // 2. Xóa các bản ghi trong reviews liên quan đến bookings của user
            $stmt = $db->prepare(
                "DELETE r FROM reviews r 
                 INNER JOIN bookings b ON r.booking_id = b.id 
                 WHERE b.brand_id = ? OR b.kol_koc_id = ?"
            );
            $stmt->execute([$userId, $userId]);
    
            // 3. Xóa các bản ghi trong payments liên quan đến bookings của user
            $stmt = $db->prepare(
                "DELETE p FROM payments p 
                 INNER JOIN bookings b ON p.booking_id = b.id 
                 WHERE b.brand_id = ? OR b.kol_koc_id = ?"
            );
            $stmt->execute([$userId, $userId]);
    
            // 4. Xóa các bản ghi trong bookings liên quan đến user
            $stmt = $db->prepare(
                "DELETE FROM bookings WHERE brand_id = ? OR kol_koc_id = ?"
            );
            $stmt->execute([$userId, $userId]);
    
            // 5. Xóa các bản ghi khác liên quan đến user
            $stmt = $db->prepare("DELETE FROM services WHERE user_id = ?");
            $stmt->execute([$userId]);
    
            $stmt = $db->prepare("DELETE FROM user_categories WHERE user_id = ?");
            $stmt->execute([$userId]);
    
            // Thêm dòng này: Xóa các bản ghi từ portfolios
            $stmt = $db->prepare("DELETE FROM portfolios WHERE user_id = ?");
            $stmt->execute([$userId]);
    
            $stmt = $db->prepare("DELETE FROM profiles WHERE user_id = ?");
            $stmt->execute([$userId]);
    
            // 6. Xóa người dùng
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
    
            $db->getConnection()->commit();
            return ['success' => true];
        } catch (PDOException $e) {
            $db->getConnection()->rollBack();
            return ['success' => false, 'message' => 'Lỗi cơ sở dữ liệu: ' . $e->getMessage()];
        }
    }

    public function changePassword($currentPassword, $newPassword) {
        try {
            if (!$this->id) {
                return ['success' => false, 'message' => 'Vui lòng đăng nhập để đổi mật khẩu'];
            }

            // Lấy thông tin người dùng hiện tại
            $stmt = $this->db->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$this->id]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($currentPassword, $user['password'])) {
                return ['success' => false, 'message' => 'Mật khẩu hiện tại không đúng'];
            }

            // Kiểm tra định dạng mật khẩu mới
            if (!Util::validatePassword($newPassword)) {
                return ['success' => false, 'message' => 'Mật khẩu mới phải có ít nhất 8 ký tự, bao gồm chữ cái in hoa, chữ cái thường và số'];
            }

            // Mã hóa mật khẩu mới
            $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            // Cập nhật mật khẩu
            $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $result = $stmt->execute([$hashedNewPassword, $this->id]);

            if ($result) {
                return ['success' => true, 'message' => 'Đổi mật khẩu thành công'];
            }
            return ['success' => false, 'message' => 'Không thể đổi mật khẩu'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Lỗi cơ sở dữ liệu: ' . $e->getMessage()];
        }
    }
}
?>