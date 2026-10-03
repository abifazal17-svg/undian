<?php
/**
 * Configuration & Database Connection
 * Website Undian Bintang 5 ShopeeFood
 */

// Timezone setup
date_default_timezone_set('Asia/Jakarta');

// Database MySQL Credentials (Dummy Localhost)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Kosongkan jika menggunakan XAMPP standar
define('DB_NAME', 'db_undian_shopeefood');

define('UPLOAD_DIR', __DIR__ . '/uploads/screenshots/');
define('UPLOAD_URL_PATH', 'uploads/screenshots/');

// Default Admin Credentials
define('DEFAULT_ADMIN_USER', 'admin');
define('DEFAULT_ADMIN_PASS', 'admin123'); // Can be changed in admin dashboard

// Create Upload directory if not exists
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}

// Database Connection Helper
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Database Connection Error: " . $e->getMessage());
        }
    }
    return $pdo;
}

// Initialize Database Schema & Migrations
function initDB() {
    $db = getDB();
    
    // 1. Settings table
    $db->exec("CREATE TABLE IF NOT EXISTS settings (
        setting_key VARCHAR(191) PRIMARY KEY,
        setting_value TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 2. Periods table
    $db->exec("CREATE TABLE IF NOT EXISTS periods (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nama_periode VARCHAR(255) NOT NULL,
        tanggal_undian DATETIME NOT NULL,
        announcement_note TEXT,
        is_active TINYINT DEFAULT 0,
        status VARCHAR(50) DEFAULT 'berlangsung',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 3. Submissions table
    $db->exec("CREATE TABLE IF NOT EXISTS submissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nama VARCHAR(255) NOT NULL,
        no_hp VARCHAR(50) NOT NULL,
        no_pesanan VARCHAR(191) UNIQUE NOT NULL,
        screenshot VARCHAR(255) NOT NULL,
        is_winner TINYINT DEFAULT 0,
        period_id INT DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (period_id) REFERENCES periods(id) ON DELETE SET NULL ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Ensure period_id column exists for existing database tables
    try {
        $stmt = $db->query("SHOW COLUMNS FROM submissions LIKE 'period_id'");
        $hasPeriodCol = $stmt->fetch();
        if (!$hasPeriodCol) {
            $db->exec("ALTER TABLE submissions ADD COLUMN period_id INT DEFAULT 1");
        }
    } catch (Exception $e) {}

    // Initialize default settings if empty
    $stmt = $db->query("SELECT COUNT(*) FROM settings");
    if ($stmt->fetchColumn() == 0) {
        $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)")
           ->execute(['admin_password', password_hash(DEFAULT_ADMIN_PASS, PASSWORD_BCRYPT)]);
    }

    // Initialize default active period if empty
    $periodCount = $db->query("SELECT COUNT(*) FROM periods")->fetchColumn();
    if ($periodCount == 0) {
        $defaultDate = date('Y-m-d H:i:s', strtotime('+7 days'));
        $db->prepare("INSERT INTO periods (nama_periode, tanggal_undian, announcement_note, is_active, status) VALUES (?, ?, ?, 1, 'berlangsung')")
           ->execute([
               'Periode A', 
               $defaultDate, 
               'Pemenang akan dihubungi melalui WhatsApp resmi. Pastikan nomor anda aktif!'
           ]);
    }
}

// Run DB Initialization automatically
initDB();

// Helper functions
function getSetting($key, $default = '') {
    $db = getDB();
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $res = $stmt->fetchColumn();
    return $res !== false ? $res : $default;
}

function setSetting($key, $value) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                          ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    return $stmt->execute([$key, $value]);
}

// Get active period
function getActivePeriod() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM periods WHERE is_active = 1 LIMIT 1");
    $period = $stmt->fetch();
    if (!$period) {
        // Fallback to latest
        $stmt = $db->query("SELECT * FROM periods ORDER BY id DESC LIMIT 1");
        $period = $stmt->fetch();
    }
    return $period;
}

// Helper Name Masking: "Abiyyu Dhonan" -> "A***** *****N"
function maskWinnerName($name) {
    $name = trim($name);
    if (empty($name)) return '***';

    $words = preg_split('/\s+/', $name);
    $totalWords = count($words);

    if ($totalWords === 1) {
        $word = $words[0];
        $len = mb_strlen($word);
        if ($len <= 2) {
            return mb_strtoupper(mb_substr($word, 0, 1)) . '*';
        }
        return mb_strtoupper(mb_substr($word, 0, 1)) . str_repeat('*', $len - 2) . mb_strtoupper(mb_substr($word, -1));
    }

    $masked = [];
    foreach ($words as $idx => $word) {
        $len = mb_strlen($word);
        if ($idx === 0) {
            // First word: Uppercase First char + stars (e.g. Abiyyu -> A*****)
            $masked[] = mb_strtoupper(mb_substr($word, 0, 1)) . str_repeat('*', max(1, $len - 1));
        } elseif ($idx === $totalWords - 1) {
            // Last word: stars + Uppercase Last char (e.g. Dhonan -> *****N)
            $masked[] = str_repeat('*', max(1, $len - 1)) . mb_strtoupper(mb_substr($word, -1));
        } else {
            // Middle words: all stars
            $masked[] = str_repeat('*', $len);
        }
    }
    return implode(' ', $masked);
}

function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Start Session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
