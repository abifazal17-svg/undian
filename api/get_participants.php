<?php
/**
 * API: Get All Participants for Spin Wheel / Lottery Draw (Active Period)
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$activePeriod = getActivePeriod();
$periodId = $activePeriod ? $activePeriod['id'] : 1;

$db = getDB();
$stmt = $db->prepare("SELECT id, nama, no_hp, no_pesanan, is_winner, created_at FROM submissions WHERE period_id = ? ORDER BY RANDOM()");
$stmt->execute([$periodId]);
$participants = $stmt->fetchAll();

echo json_encode([
    'success' => true,
    'count' => count($participants),
    'period' => $activePeriod ? $activePeriod['nama_periode'] : 'Periode Aktif',
    'participants' => $participants
]);
