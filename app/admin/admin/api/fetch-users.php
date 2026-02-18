<?php
// admin/api/fetch-users.php  (PDO)
ini_set('display_errors', 0);
error_reporting(E_ALL);
ob_start(); // capture notices so we still return valid JSON
header('Content-Type: application/json; charset=utf-8');

$response = ['data' => []];

try {
    // use the SAME DB as registerSave.php
    require_once __DIR__ . '/../../backend/db.php'; // <- gives $pdo (PDO)

    $start = $_GET['start_date'] ?? null;
    $end   = $_GET['end_date'] ?? null;

    if ($start && $end) {
        $sql = "SELECT id, username, email, otp, otp_expiry, created_at
                FROM users
                WHERE DATE(created_at) BETWEEN :s AND :e
                ORDER BY created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':s' => $start, ':e' => $end]);
    } else {
        $sql = "SELECT id, username, email, otp, otp_expiry, created_at
                FROM users
                ORDER BY created_at DESC";
        $stmt = $pdo->query($sql);
    }

    $response['data'] = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

    $debug = trim(ob_get_clean());
    if ($debug !== '') $response['error'] = $debug;

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['data' => [], 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
