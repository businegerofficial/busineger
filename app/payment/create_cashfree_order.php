<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');
require('./backend/db.php');

// ✅ 1. Ensure user is logged in
if (!isset($_SESSION['user']['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => true, "message" => "Unauthorized access"]);
    exit();
}

$user_id = $_SESSION['user']['user_id'];
$email = $_SESSION['user']['email'] ?? 'noemail@example.com';
$cart = $_SESSION['cart'] ?? [];

// ✅ 2. Validate cart
if (empty($cart)) {
    http_response_code(400);
    echo json_encode(["error" => true, "message" => "Cart is empty"]);
    exit();
}

// ✅ 3. Calculate total amount
$totalAmount = array_reduce($cart, function($sum, $item) {
    return $sum + floatval($item['price']);
}, 0);

if ($totalAmount <= 0) {
    http_response_code(400);
    echo json_encode(["error" => true, "message" => "Invalid total amount"]);
    exit();
}

// ✅ 4. Generate a unique Cashfree order ID
$cashfree_order_id = "ORDER_" . uniqid();

// ✅ 5. Prepare data payload for Cashfree API
$data = [
    "order_id" => $cashfree_order_id,
    "order_amount" => $totalAmount,
    "order_currency" => "INR",
    "customer_details" => [
        "customer_id" => (string)$user_id,
        "customer_email" => $email,
        "customer_phone" => "9999999999"
    ],
   "order_meta" => [
    "return_url" => "https://ai-mandi.com/payment-success.php?order_id={order_id}"
]
];

// ✅ 6. Cashfree sandbox credentials
$client_id = "987641899a6006b5bd8d24a48e146789";
$client_secret = "cfsk_ma_prod_158925b7760b944a8e841a71bce9adcf_cd3fdcc3";

// ✅ 7. Send request to Cashfree API
$ch = curl_init("https://api.cashfree.com/pg/orders");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "x-api-version: 2025-01-01",
    "x-client-id: $client_id",
    "x-client-secret: $client_secret"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
$response = curl_exec($ch);
curl_close($ch);

// ✅ 8. Decode Cashfree response
$response_data = json_decode($response, true);

// ✅ 9. If successful, insert each product with status = 'Pending'
if (!empty($response_data['payment_session_id']) && !empty($response_data['order_id'])) {
    $cf_order_id = $response_data['order_id'];

    foreach ($cart as $item) {
    if (!isset($item['id'])) {
        echo json_encode(["error" => true, "message" => "Cart item missing 'id'", "item" => $item]);
        exit();
    }

    $stmt = $pdo->prepare("INSERT INTO orders 
        (user_id, product_id, total_price, payment_status, cashfree_order_id)
        VALUES (?, ?, ?, 'Pending', ?)");
    $stmt->execute([
        $user_id,
        $item['id'],
        $item['price'],
        $cf_order_id
    ]);
}


    // ✅ Optional: store CF order ID in session
    $_SESSION['cashfree_order_id'] = $cf_order_id;

    echo json_encode([
        "payment_session_id" => $response_data['payment_session_id']
    ]);
    exit();
}

// ❌ 10. If error, return detailed message
echo json_encode([
    "error" => true,
    "message" => $response_data['message'] ?? 'Cashfree API call failed',
    "details" => $response_data
]);
exit();
