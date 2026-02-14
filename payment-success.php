<?php
session_start();
require('./backend/db.php');

// ✅ Validate: order ID from Cashfree & user session
if (!isset($_GET['order_id']) || !isset($_SESSION['user']['user_id'])) {
    die("❌ Missing payment details.");
}

$cashfree_order_id = $_GET['order_id'];
$user_id = $_SESSION['user']['user_id'];

// ✅ Call Cashfree API to confirm payment
$client_id = '987641899a6006b5bd8d24a48e146789';
$client_secret = 'cfsk_ma_prod_158925b7760b944a8e841a71bce9adcf_cd3fdcc3';
$api_url = "https://api.cashfree.com/pg/orders/$cashfree_order_id";

$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "x-client-id: $client_id",
    "x-client-secret: $client_secret",
    "x-api-version: 2023-08-01",
    "Content-Type: application/json"
]);
$response = curl_exec($ch);
curl_close($ch);

if (!$response) {
    die("❌ Failed to connect to Cashfree.");
}

$data = json_decode($response, true);
$orderStatus = $data['order_status'] ?? 'UNKNOWN';
$cf_reference = $data['order_id'] ?? '';

// ✅ Proceed only if PAID
if ($orderStatus !== 'PAID') {
    die("❌ Payment not completed. Status: $orderStatus");
}

// ✅ Update existing pending orders for this user & order
try {
    $stmt = $pdo->prepare("UPDATE orders 
        SET payment_status = 'Paid', cf_payment_reference = ? 
        WHERE user_id = ? AND cashfree_order_id = ?");
    $stmt->execute([$cf_reference, $user_id, $cashfree_order_id]);

    // ✅ Cleanup
    unset($_SESSION['cart']);
    unset($_SESSION['cart_total']);

    // ✅ Redirect to dashboard
    header("Location: https://ai-mandi.com/dashboard.php");

    exit;
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage();
}
