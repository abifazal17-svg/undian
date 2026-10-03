<?php
/**
 * Configuration & Database Connection
 * Website Undian Bintang 5 ShopeeFood
 */

// Timezone setup
date_default_timezone_set('Asia/Jakarta');

// Database File Path
define('DB_FILE', __DIR__ . '/database.sqlite');
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
            $pdo = new PDO('sqlite:' . DB_FILE);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Enable WAL mode for better concurrency
            $pdo->exec('PRAGMA journal_mode = WAL;');
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
        setting_key TEXT UNIQUE PRIMARY KEY,
        setting_value TEXT
    )");

    // 2. Periods table
    $db->exec("CREATE TABLE IF NOT EXISTS periods (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nama_periode TEXT NOT NULL,
        tanggal_undian DATETIME NOT NULL,
        announcement_note TEXT,
        is_active INTEGER DEFAULT 0,
        status TEXT DEFAULT 'berlangsung',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // 3. Submissions table
    $db->exec("CREATE TABLE IF NOT EXISTS submissions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nama TEXT NOT NULL,
        no_hp TEXT NOT NULL,
        no_pesanan TEXT UNIQUE NOT NULL,
        screenshot TEXT NOT NULL,
        is_winner INTEGER DEFAULT 0,
        period_id INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (period_id) REFERENCES periods(id)
    )");

    // Ensure period_id column exists for existing database files
    try {
        $cols = $db->query("PRAGMA table_info(submissions)")->fetchAll();
        $hasPeriodCol = false;
        foreach ($cols as $col) {
            if ($col['name'] === 'period_id') {
                $hasPeriodCol = true;
                break;
            }
        }
        if (!$hasPeriodCol) {
            $db->exec("ALTER TABLE submissions ADD COLUMN period_id INTEGER DEFAULT 1");
        }
    } catch (Exception $e) {}

    // Create unique index on no_pesanan
    $db->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_no_pesanan ON submissions(no_pesanan)");

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
                          ON CONFLICT(setting_key) DO UPDATE SET setting_value = excluded.setting_value");
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
