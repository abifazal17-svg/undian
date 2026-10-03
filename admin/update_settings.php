<?php
/**
 * Update Admin Settings (Tanggal Undian, Password, Note)
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Sesi tidak valid / belum login.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metode permintaan salah.']);
    exit;
}

$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action === 'update_date') {
    $tanggal_undian = isset($_POST['tanggal_undian']) ? trim($_POST['tanggal_undian']) : '';
    $announcement_note = isset($_POST['announcement_note']) ? trim($_POST['announcement_note']) : '';

    if (empty($tanggal_undian)) {
        echo json_encode(['success' => false, 'message' => 'Tanggal undian tidak boleh kosong!']);
        exit;
    }

    // Format HTML5 datetime-local to Y-m-d H:i:s
    $formattedDate = date('Y-m-d H:i:s', strtotime($tanggal_undian));

    setSetting('tanggal_undian', $formattedDate);
    setSetting('announcement_note', sanitizeInput($announcement_note));

    echo json_encode([
        'success' => true,
        'message' => 'Jadwal dan tanggal undian berhasil diperbarui!',
        'tanggal_undian' => $formattedDate,
        'announcement_note' => $announcement_note
    ]);
    exit;
}

if ($action === 'change_password') {
    $new_pass = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';

    if (strlen($new_pass) < 4) {
        echo json_encode(['success' => false, 'message' => 'Password baru minimal 4 karakter!']);
        exit;
    }

    $hash = password_hash($new_pass, PASSWORD_BCRYPT);
    setSetting('admin_password', $hash);

    echo json_encode(['success' => true, 'message' => 'Password admin berhasil diubah.']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenali.']);
