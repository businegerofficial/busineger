<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
ob_start();
header('Content-Type: application/json; charset=utf-8');

$response = ['data' => []];

try {
    require_once __DIR__ . '/../../backend/db.php'; // provides $pdo

    $s = $_GET['start_date'] ?? null;
    $e = $_GET['end_date'] ?? null;

    // Select with user join
    $baseSql = "
      SELECT
        o.id,
        o.user_id,
        o.product_id,
        o.total_price,
        o.payment_status,
        o.created_at,
        o.cashfree_order_id,
        o.cf_payment_reference,
        o.cart_json,
        u.username,
        u.email
      FROM orders o
      LEFT JOIN users u ON u.id = o.user_id
    ";

    if ($s && $e) {
        $baseSql .= " WHERE DATE(o.created_at) BETWEEN :s AND :e ORDER BY o.created_at DESC";
        $stmt = $pdo->prepare($baseSql);
        $stmt->execute([':s' => $s, ':e' => $e]);
    } else {
        $baseSql .= " ORDER BY o.created_at DESC";
        $stmt = $pdo->query($baseSql);
    }

    $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

    // Compute items_count from cart_json or product_id
    foreach ($rows as &$r) {
        $itemsCount = 0;
        if (!empty($r['cart_json'])) {
            $decoded = json_decode($r['cart_json'], true);
            if (is_array($decoded)) {
                // cart_json could be an array of items or {cart: [...]} – be defensive
                if (isset($decoded['cart']) && is_array($decoded['cart'])) {
                    $itemsCount = count($decoded['cart']);
                } else {
                    $itemsCount = count($decoded);
                }
            }
        } else {
            // legacy single product order
            $itemsCount = (!empty($r['product_id'])) ? 1 : 0;
        }
        $r['items_count'] = $itemsCount;
    }
    unset($r);

    $response['data'] = $rows;

    $debug = trim(ob_get_clean());
    if ($debug !== '') $response['error'] = $debug;

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (Throwable $ex) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['data'=>[], 'error'=>$ex->getMessage()], JSON_UNESCAPED_UNICODE);
}
