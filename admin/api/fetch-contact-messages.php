<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
ob_start();
header('Content-Type: application/json; charset=utf-8');

$response = ['data' => []];

try {
    require_once __DIR__ . '/../../backend/db.php'; // $pdo

    $s = $_GET['start_date'] ?? null;
    $e = $_GET['end_date'] ?? null;

    if ($s && $e) {
        $sql = "SELECT id, name, email, message, page_url, ip_address, created_at
                FROM contact_messages
                WHERE DATE(created_at) BETWEEN :s AND :e
                ORDER BY created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':s' => $s, ':e' => $e]);
    } else {
        $sql = "SELECT id, name, email, message, page_url, ip_address, created_at
                FROM contact_messages
                ORDER BY created_at DESC";
        $stmt = $pdo->query($sql);
    }

    $response['data'] = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

    $debug = trim(ob_get_clean());
    if ($debug !== '') $response['error'] = $debug;

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (Throwable $ex) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['data'=>[], 'error'=>$ex->getMessage()], JSON_UNESCAPED_UNICODE);
}
