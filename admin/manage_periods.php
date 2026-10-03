<?php
/**
 * Admin API / Action: Multi-Period Management (Create, Edit, Delete, Set Active)
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Sesi tidak valid / belum login.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metode permintaan tidak valid.']);
    exit;
}

$action = isset($_POST['action']) ? $_POST['action'] : '';
$db = getDB();

// 1. Create New Period ("Mulai Periode Baru")
if ($action === 'create_period') {
    $nama_periode = isset($_POST['nama_periode']) ? sanitizeInput($_POST['nama_periode']) : '';
    $tanggal_undian = isset($_POST['tanggal_undian']) ? trim($_POST['tanggal_undian']) : '';
    $announcement_note = isset($_POST['announcement_note']) ? sanitizeInput($_POST['announcement_note']) : '';
    $set_active = isset($_POST['set_active']) && $_POST['set_active'] == '1';

    if (empty($nama_periode) || empty($tanggal_undian)) {
        echo json_encode(['success' => false, 'message' => 'Nama Periode dan Tanggal Undian wajib diisi!']);
        exit;
    }

    $formattedDate = date('Y-m-d H:i:s', strtotime($tanggal_undian));

    if ($set_active) {
        $db->exec("UPDATE periods SET is_active = 0");
    }

    $stmt = $db->prepare("INSERT INTO periods (nama_periode, tanggal_undian, announcement_note, is_active, status) VALUES (?, ?, ?, ?, 'berlangsung')");
    $stmt->execute([$nama_periode, $formattedDate, $announcement_note, $set_active ? 1 : 0]);

    echo json_encode([
        'success' => true,
        'message' => 'Periode baru "' . $nama_periode . '" berhasil dibuat!' . ($set_active ? ' Dan di-set sebagai Periode Aktif.' : '')
    ]);
    exit;
}

// 2. Set Active Period
if ($action === 'set_active') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID Periode tidak valid.']);
        exit;
    }

    $db->exec("UPDATE periods SET is_active = 0");
    $stmt = $db->prepare("UPDATE periods SET is_active = 1 WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode([
        'success' => true,
        'message' => 'Periode aktif berhasil diperbarui!'
    ]);
    exit;
}

// 3. Edit Period
if ($action === 'edit_period') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $nama_periode = isset($_POST['nama_periode']) ? sanitizeInput($_POST['nama_periode']) : '';
    $tanggal_undian = isset($_POST['tanggal_undian']) ? trim($_POST['tanggal_undian']) : '';
    $announcement_note = isset($_POST['announcement_note']) ? sanitizeInput($_POST['announcement_note']) : '';
    $status = isset($_POST['status']) ? trim($_POST['status']) : 'berlangsung';

    if ($id <= 0 || empty($nama_periode) || empty($tanggal_undian)) {
        echo json_encode(['success' => false, 'message' => 'Data tidak lengkap.']);
        exit;
    }

    $formattedDate = date('Y-m-d H:i:s', strtotime($tanggal_undian));

    $stmt = $db->prepare("UPDATE periods SET nama_periode = ?, tanggal_undian = ?, announcement_note = ?, status = ? WHERE id = ?");
    $stmt->execute([$nama_periode, $formattedDate, $announcement_note, $status, $id]);

    echo json_encode([
        'success' => true,
        'message' => 'Data Periode berhasil diperbarui!'
    ]);
    exit;
}

// 4. Delete Period (Memastikan periode berlalu/aktif dapat dihapus & riwayat pemenang ikut terhapus)
if ($action === 'delete_period') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID Periode tidak valid.']);
        exit;
    }

    // Check if target period exists and if it is active
    $stmt = $db->prepare("SELECT is_active, nama_periode FROM periods WHERE id = ?");
    $stmt->execute([$id]);
    $period = $stmt->fetch();

    if (!$period) {
        echo json_encode(['success' => false, 'message' => 'Periode tidak ditemukan.']);
        exit;
    }

    $isActive = $period['is_active'] == 1;

    // A. Clean up physical screenshot files for all submissions in this period
    $subStmt = $db->prepare("SELECT screenshot FROM submissions WHERE period_id = ?");
    $subStmt->execute([$id]);
    $screenshots = $subStmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($screenshots as $filename) {
        if (!empty($filename)) {
            $filePath = UPLOAD_DIR . $filename;
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }
    }

    // B. Delete all submissions (including winners) linked to this period
    $delSubStmt = $db->prepare("DELETE FROM submissions WHERE period_id = ?");
    $delSubStmt->execute([$id]);

    // C. Delete period row
    $delPeriodStmt = $db->prepare("DELETE FROM periods WHERE id = ?");
    $delPeriodStmt->execute([$id]);

    // D. If deleted period was active, automatically activate another period or create new default period
    if ($isActive) {
        $remainingId = $db->query("SELECT id FROM periods ORDER BY id DESC LIMIT 1")->fetchColumn();
        if ($remainingId) {
            $db->prepare("UPDATE periods SET is_active = 1 WHERE id = ?")->execute([$remainingId]);
        } else {
            // If no periods left, create a fresh default active period
            $defaultDate = date('Y-m-d H:i:s', strtotime('+7 days'));
            $db->prepare("INSERT INTO periods (nama_periode, tanggal_undian, announcement_note, is_active, status) VALUES (?, ?, ?, 1, 'berlangsung')")
               ->execute([
                   'Periode 1', 
                   $defaultDate, 
                   'Pemenang akan dihubungi melalui WhatsApp resmi. Pastikan nomor anda aktif!'
               ]);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Periode "' . $period['nama_periode'] . '" beserta seluruh data peserta & riwayat pemenangnya telah berhasil dihapus!'
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Aksi tidak valid.']);
