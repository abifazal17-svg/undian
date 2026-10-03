<?php
/**
 * API Endpoint: Check Duplicate Order Number
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

$no_pesanan_raw = isset($_GET['no_pesanan']) ? trim($_GET['no_pesanan']) : '';

if (empty($no_pesanan_raw)) {
    echo json_encode(['exists' => false]);
    exit;
}

// Clean order number (strip leading '#', spaces)
$no_pesanan = ltrim($no_pesanan_raw, '# ');

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM submissions WHERE no_pesanan = ?");
    $stmt->execute([$no_pesanan]);
    $count = $stmt->fetchColumn();

    echo json_encode([
        'exists' => $count > 0,
        'no_pesanan' => '#' . htmlspecialchars($no_pesanan)
    ]);
} catch (Exception $e) {
    echo json_encode(['exists' => false, 'error' => $e->getMessage()]);
}
