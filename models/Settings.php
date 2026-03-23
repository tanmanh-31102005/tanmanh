<?php
require_once __DIR__ . '/../includes/Database.php';

class Settings {
    private static $db;
    private static $instance = null;

    private function __construct() {
        self::$db = Database::getInstance();
        
        // Create settings table if it doesn't exist
        self::$db->query("CREATE TABLE IF NOT EXISTS settings (
            id VARCHAR(50) PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            value TEXT,
            type ENUM('text', 'image', 'json') NOT NULL DEFAULT 'text',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");

        // Insert default settings if they don't exist
        $defaultSettings = [
            'homepage_background' => ['Homepage Background Image', '', 'image'],
            'contact_email' => ['Contact Email', '', 'text'],
            'contact_phone' => ['Contact Phone', '', 'text'],
            'contact_address' => ['Contact Address', '', 'text'],
            'pinned_kols' => ['Pinned KOLs', '[]', 'json'],
            'blog_content' => ['Blog Content', '', 'text']
        ];

        foreach ($defaultSettings as $id => [$name, $value, $type]) {
            $stmt = self::$db->prepare("INSERT IGNORE INTO settings (id, name, value, type) VALUES (?, ?, ?, ?)");
            $stmt->execute([$id, $name, $value, $type]);
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Settings();
        }
        return self::$instance;
    }

    public static function get($id) {
        self::getInstance();
        $stmt = self::$db->prepare("SELECT * FROM settings WHERE id = ?");
        $stmt->execute([$id]);
        $setting = $stmt->fetch();
        return $setting ? $setting['value'] : null;
    }

    public static function set($id, $value) {
        self::getInstance();
        $stmt = self::$db->prepare("UPDATE settings SET value = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        return $stmt->execute([$value, $id]);
    }

    public static function getAll() {
        self::getInstance();
        return self::$db->query("SELECT * FROM settings ORDER BY name")->fetchAll();
    }
}
?>