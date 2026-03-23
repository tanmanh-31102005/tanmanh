<?php
require_once '../vendor/autoload.php'; // Nếu sử dụng PHPMailer qua Composer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
class Util {
    public static function sanitizeInput($data) {
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }

    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public static function validatePassword($password) {
        // At least 8 characters, 1 uppercase, 1 lowercase, 1 number
        return strlen($password) >= 8 
            && preg_match('/[A-Z]/', $password) 
            && preg_match('/[a-z]/', $password) 
            && preg_match('/[0-9]/', $password);
    }

    public static function generateToken() {
        return bin2hex(random_bytes(32));
    }

    public static function uploadFile($file, $directory) {
        try {
            // Create directory if it doesn't exist
            if (!is_dir($directory)) {
                if (!mkdir($directory, 0777, true)) {
                    throw new Exception('Failed to create upload directory');
                }
                chmod($directory, 0777);
            }

            if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('File upload failed: ' . self::getUploadError($file['error']));
            }

            if ($file['size'] > MAX_FILE_SIZE) {
                throw new Exception('File size exceeds limit of ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB');
            }

            $fileInfo = pathinfo($file['name']);
            $extension = strtolower($fileInfo['extension']);
            
            if (!in_array($extension, ALLOWED_EXTENSIONS)) {
                throw new Exception('Invalid file type. Allowed types: ' . implode(', ', ALLOWED_EXTENSIONS));
            }

            // Generate unique filename
            $filename = uniqid() . '.' . $extension;
            $filepath = $directory . '/' . $filename;

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                throw new Exception('Failed to move uploaded file');
            }

            // Set file permissions
            chmod($filepath, 0666);

            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    private static function getUploadError($code) {
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:
                return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
            case UPLOAD_ERR_FORM_SIZE:
                return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
            case UPLOAD_ERR_PARTIAL:
                return 'The uploaded file was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing a temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'A PHP extension stopped the file upload';
            default:
                return 'Unknown upload error';
        }
    }

    public static function isAuthenticated() {
        return isset($_SESSION['user_id']);
    }

    public static function requireAuth() {
        if (!self::isAuthenticated()) {
            header('Location: ../view/login.php');
            exit();
        }
    }

    public static function requireRole($roles) {
        self::requireAuth();
        $roles = (array)$roles;
        
        if (!in_array($_SESSION['user_role'], $roles)) {
            header('HTTP/1.1 403 Forbidden');
            die('Access Denied');
        }
    }

    public static function flashMessage($type, $message) {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message
        ];
    }

    public static function getFlashMessage() {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }

    public static function redirect($url) {
        header("Location: " . $url);
        exit();
    }

    public static function getImagePlaceholder() {
        // Return a data URI for a simple default avatar
        return "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100' viewBox='0 0 100 100'%3E%3Ccircle cx='50' cy='35' r='25' fill='%23ccc'/%3E%3Cpath d='M15,85 Q50,65 85,85' fill='%23ccc'/%3E%3C/svg%3E";
    }

    public static function generateResetToken() {
        return bin2hex(random_bytes(32));
    }

    public static function sendResetEmail($email, $token) {
        $resetLink = SITE_URL . "/view/reset_password.php?email=" . urlencode($email) . "&token=" . $token;
        
        $mail = new PHPMailer(true);
        try {
            // Cấu hình SMTP
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'phantaib52@gmail.com'; // Thay bằng email của bạn
            $mail->Password = 'pwup qrxx vuzs mgkm'; // Thay bằng mật khẩu ứng dụng
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Đặt charset UTF-8
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';

            // Người gửi và người nhận
            $mail->setFrom('no-reply@yourdomain.com', 'KOL/KOC Booking');
            $mail->addAddress($email);

            // Nội dung email
            $mail->isHTML(true);
            // Mã hóa tiêu đề để hỗ trợ tiếng Việt
            $mail->Subject = '=?UTF-8?B?' . base64_encode('Khôi phục mật khẩu') . '?=';
            $mail->Body = "
                <h2>Yêu cầu khôi phục mật khẩu</h2>
                <p>Vui lòng nhấp vào liên kết dưới đây để đặt lại mật khẩu của bạn:</p>
                <p><a href='$resetLink'>$resetLink</a></p>
                <p>Liên kết này sẽ hết hạn sau 1 giờ.</p>
            ";

            $mail->send();
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Không thể gửi email: ' . $mail->ErrorInfo];
        }
    }

    public static function verifyResetToken($email, $token) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM password_resets WHERE email = ? AND token = ?");
        $stmt->execute([$email, $token]);
        $reset = $stmt->fetch();

        if ($reset) {
            // Kiểm tra thời gian hết hạn (1 giờ)
            $createdAt = strtotime($reset['created_at']);
            $now = time();
            if (($now - $createdAt) <= 3600) {
                return ['success' => true];
            }
            // Xóa token đã hết hạn
            $stmt = $db->prepare("DELETE FROM password_resets WHERE email = ?");
            $stmt->execute([$email]);
            return ['success' => false, 'message' => 'Liên kết khôi phục đã hết hạn'];
        }
        return ['success' => false, 'message' => 'Liên kết khôi phục không hợp lệ'];
    }

    public static function clearResetToken($email) {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM password_resets WHERE email = ?");
        $stmt->execute([$email]);
    }


}