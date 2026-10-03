<?php
/**
 * Submission Handler Script
 * Website Undian ShopeeFood Bintang 5
 */
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metode permintaan tidak valid.']);
    exit;
}

$nama = isset($_POST['nama']) ? sanitizeInput($_POST['nama']) : '';
$no_hp = isset($_POST['no_hp']) ? sanitizeInput($_POST['no_hp']) : '';
$no_pesanan_raw = isset($_POST['no_pesanan']) ? trim($_POST['no_pesanan']) : '';

// 1. Basic Validation
if (empty($nama) || empty($no_hp) || empty($no_pesanan_raw)) {
    echo json_encode(['success' => false, 'message' => 'Semua kolom formulir wajib diisi!']);
    exit;
}

// Clean order number (remove leading '#', spaces)
$no_pesanan = sanitizeInput(ltrim($no_pesanan_raw, '# '));

// Clean phone number format (replace spaces, dashes)
$no_hp_clean = preg_replace('/[^0-9+]/', '', $no_hp);

// Get current active period
$activePeriod = getActivePeriod();
$period_id = $activePeriod ? $activePeriod['id'] : 1;

// 2. Server-Side Protection: Check Duplicate Order Number
$db = getDB();
$checkStmt = $db->prepare("SELECT COUNT(*) FROM submissions WHERE no_pesanan = ?");
$checkStmt->execute([$no_pesanan]);
if ($checkStmt->fetchColumn() > 0) {
    echo json_encode([
        'success' => false, 
        'message' => 'DUPLIKASI DITOLAK! Nomor pesanan (#' . $no_pesanan . ') sudah pernah digunakan untuk mendaftar undian sebelumnya.'
    ]);
    exit;
}

// 3. File Upload Processing
if (!isset($_FILES['screenshot']) || $_FILES['screenshot']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Bukti screenshot bintang 5 gagal diunggah. Silakan coba lagi.']);
    exit;
}

$file = $_FILES['screenshot'];
$maxSize = 5 * 1024 * 1024; // 5MB limit
if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'message' => 'Ukuran file screenshot melebihi batas maksimal 5MB.']);
    exit;
}

// Allowed File Extensions & Mime Types
$allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
$fileMime = mime_content_type($file['tmp_name']);

if (!in_array($fileMime, $allowedMimes)) {
    echo json_encode(['success' => false, 'message' => 'Format file screenshot harus berupa gambar (JPG, PNG, atau WEBP).']);
    exit;
}

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
if (empty($ext)) {
    $ext = ($fileMime === 'image/png') ? 'png' : (($fileMime === 'image/webp') ? 'webp' : 'jpg');
}

// Generate secure & unique filename
$newFileName = 'proof_' . date('Ymd_His') . '_' . uniqid() . '.' . strtolower($ext);
$destination = UPLOAD_DIR . $newFileName;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan file screenshot ke server.']);
    exit;
}

// 4. Save to Database with period_id
try {
    $insertStmt = $db->prepare("INSERT INTO submissions (nama, no_hp, no_pesanan, screenshot, period_id) VALUES (?, ?, ?, ?, ?)");
    $insertStmt->execute([$nama, $no_hp_clean, $no_pesanan, $newFileName, $period_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Pendaftaran Undian Berhasil!',
        'details' => [
            'nama' => $nama,
            'no_hp' => $no_hp_clean,
            'no_pesanan' => '#' . $no_pesanan,
            'periode' => $activePeriod ? $activePeriod['nama_periode'] : 'Periode Aktif',
            'created_at' => date('d M Y H:i:s')
        ]
    ]);
} catch (PDOException $e) {
    // If unique constraint triggers in race condition
    if ($e->getCode() == 23000 || strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
        echo json_encode([
            'success' => false,
            'message' => 'DUPLIKASI DITOLAK! Nomor pesanan (#' . $no_pesanan . ') sudah pernah digunakan.'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Kesalahan database: ' . $e->getMessage()]);
    }
}
