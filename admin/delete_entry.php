<?php
/**
 * Action: Delete Submission Entry
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

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID data tidak valid.']);
    exit;
}

$db = getDB();

// Fetch screenshot filename first to clean up physical file
$stmt = $db->prepare("SELECT screenshot FROM submissions WHERE id = ?");
$stmt->execute([$id]);
$screenshot = $stmt->fetchColumn();

if ($screenshot) {
    $filePath = UPLOAD_DIR . $screenshot;
    if (file_exists($filePath)) {
        @unlink($filePath);
    }
}

// Delete row from DB
$deleteStmt = $db->prepare("DELETE FROM submissions WHERE id = ?");
$deleteStmt->execute([$id]);

echo json_encode([
    'success' => true,
    'message' => 'Data input peserta berhasil dihapus!'
]);
