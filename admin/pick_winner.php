<?php
/**
 * Action: Pick or Reset Winner Status
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = getDB();
$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action === 'set_winner') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID peserta tidak valid.']);
        exit;
    }

    $stmt = $db->prepare("UPDATE submissions SET is_winner = 1 WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(['success' => true, 'message' => 'Peserta berhasil ditetapkan sebagai Pemenang!']);
    exit;
}

if ($action === 'reset_winners') {
    $db->exec("UPDATE submissions SET is_winner = 0");
    echo json_encode(['success' => true, 'message' => 'Semua status pemenang telah di-reset.']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Aksi tidak valid.']);
