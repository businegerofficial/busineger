<?php
session_start();
require __DIR__ . '/../app/backend/db.php');

/**
 * Cashfree return page.
 * Cashfree redirects here AFTER payment.
 */

if (!isset($_GET['order_id'])) {
    header("Location: chatbot1.php?payment=failed&reason=no_order_id");
    exit;
}

$orderId = $_GET['order_id'];

$client_id     = "TEST106529778e1486ff476796297d0277925601";
$client_secret = "cfsk_ma_test_aff0f6d187438092034788ccf6f7f52b_14f11db6";

$pg_base = "https://sandbox.cashfree.com/pg";

/* ---------- 1. Get order status from Cashfree ---------- */
$ch = curl_init($pg_base . "/orders/" . urlencode($orderId));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "x-api-version: 2023-08-01",
    "x-client-id: $client_id",
    "x-client-secret: $client_secret"
]);
$response = curl_exec($ch);
$curlErr  = curl_error($ch);
curl_close($ch);

// DEBUG (you can keep this while testing, then remove later)
file_put_contents(
    __DIR__ . "/cashfree_order_debug.log",
    date('c') . " ORDER_ID=$orderId\nCURL_ERR=$curlErr\nRESPONSE=$response\n\n",
    FILE_APPEND
);

if ($curlErr) {
    header("Location: chatbot1.php?payment=failed&reason=curl_error");
    exit;
}

$data   = json_decode($response, true);
$status = strtoupper($data['order_status'] ?? "UNKNOWN");

/* ---------- 2. If payment successful, unlock category + update orders table ---------- */
if ($status === "PAID") {

    $catId     = $_SESSION['cf_category_id']   ?? 0;
    $userId    = $_SESSION['user']['user_id'] ?? 0;
    $catName   = $_SESSION['cf_category_name'] ?? '';

    // 🔹 Get payment reference (cf_payment_id) – optional but nice to have
    $paymentRef = null;
    $ch2 = curl_init($pg_base . "/orders/" . urlencode($orderId) . "/payments");
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch2, CURLOPT_HTTPHEADER, [
        "x-api-version: 2023-08-01",
        "x-client-id: $client_id",
        "x-client-secret: $client_secret"
    ]);
    $paymentsResponse = curl_exec($ch2);
    $paymentsErr      = curl_error($ch2);
    curl_close($ch2);

    if (!$paymentsErr) {
        $payments = json_decode($paymentsResponse, true);
        if (is_array($payments) && !empty($payments[0]['cf_payment_id'])) {
            $paymentRef = $payments[0]['cf_payment_id'];
        }
    }

    // 2a. Unlock category for user (your existing logic)
    if ($catId > 0 && $userId > 0) {
        $stmt = $pdo->prepare("
            INSERT INTO user_access (user_id, category_id, has_paid_prompts, expires_at)
            VALUES (?, ?, 1, DATE_ADD(NOW(), INTERVAL 365 DAY))
            ON DUPLICATE KEY UPDATE
                has_paid_prompts = 1,
                expires_at       = DATE_ADD(NOW(), INTERVAL 365 DAY)
        ");
        $stmt->execute([$userId, $catId]);
    }

    // 2b. Mark order as PAID in orders table
    try {
        $sql = "
            UPDATE orders
            SET payment_status = 'Paid',
                cf_payment_reference = :pref
            WHERE cashfree_order_id = :cf_order_id
              AND user_id = :uid
              AND order_type = 'category'
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':pref'       => $paymentRef,
            ':cf_order_id'=> $orderId,
            ':uid'        => $userId,
        ]);
    } catch (PDOException $e) {
        error_log('Order update failed: ' . $e->getMessage());
    }

    // clear temp session
    unset($_SESSION['cf_category_id'], $_SESSION['cf_amount'], $_SESSION['cf_currency'], $_SESSION['cf_category_name']);

    header("Location: newchat.php?payment=success");
    exit;
}

/* ---------- 3. If failed / unknown ---------- */
header("Location: newchat.php?payment=failed&status=" . urlencode($status));
exit;
