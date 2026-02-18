<?php
/**
 * cashfree_start.php
 * Creates a Cashfree PG order for CATEGORY UNLOCK
 * and returns payment_session_id as JSON.
 */

session_start();
require __DIR__ . '/../app/backend/db.php');

header('Content-Type: application/json');

/* ---------- 1. Auth guard ---------- */
if (!isset($_SESSION['user']['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$user   = $_SESSION['user'];
$userId = (int) $user['user_id'];
$email  = $user['email'] ?? 'noemail@example.com';

/* ---------- 2. Read POST data ---------- */
$categoryId   = isset($_POST['category_id']) ? (int) $_POST['category_id'] : 0;
$categoryName = trim($_POST['category_name'] ?? '');
$amount       = isset($_POST['amount']) ? (float) $_POST['amount'] : 0;
$currency     = $_POST['currency'] ?? 'INR';

if ($categoryId <= 0 || $amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid category or amount']);
    exit;
}

/* ---------- 3. Cashfree SANDBOX PG credentials ---------- */
$client_id     = 'TEST106529778e1486ff476796297d0277925601';
$client_secret = 'cfsk_ma_test_aff0f6d187438092034788ccf6f7f52b_14f11db6';

$pg_base       = 'https://sandbox.cashfree.com/pg';

/* ---------- 4. Build order payload ---------- */
$orderId = 'CAT' . $categoryId . '_U' . $userId . '_' . time();

$payload = [
    'order_id'       => $orderId,
    'order_amount'   => $amount,
    'order_currency' => $currency,
    'customer_details' => [
        'customer_id'    => (string) $userId,
        'customer_email' => $email,
        'customer_phone' => '9999999999',
    ],
    'order_meta' => [
        // IMPORTANT: return to *your* local URL while testing
        // When you go live, change this to https://ai-mandi.com/...
        'return_url' => 'http://localhost/new_file/cashfree_return.php?order_id={order_id}',
    ],
];

/* ---------- 5. Call Cashfree /pg/orders ---------- */
$ch = curl_init($pg_base . '/orders');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'x-api-version: 2023-08-01',
    'x-client-id: ' . $client_id,
    'x-client-secret: ' . $client_secret,
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

$response = curl_exec($ch);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($curlErr) {
    echo json_encode([
        'success' => false,
        'message' => 'Curl error: ' . $curlErr,
    ]);
    exit;
}

$data = json_decode($response, true);

/* ---------- 6. Handle Cashfree response ---------- */
if (!empty($data['payment_session_id']) && !empty($data['order_id'])) {

    $cfOrderId = $data['order_id'];

    // store temporary info so cashfree_return.php knows what to unlock
    $_SESSION['cf_category_id']    = $categoryId;
    $_SESSION['cf_order_id']       = $cfOrderId;
    $_SESSION['cf_amount']         = $amount;
    $_SESSION['cf_currency']       = $currency;
    $_SESSION['cf_category_name']  = $categoryName;

    /* 🔹 7. Insert a Pending order row in `orders` table */
    try {
        $stmt = $pdo->prepare("
            INSERT INTO orders
                (user_id, total_price, payment_status, created_at,
                 cashfree_order_id, cf_payment_reference, cart_json,
                 order_type, category_id, category_name)
            VALUES
                (:user_id, :total_price, 'Pending', NOW(),
                 :cf_order_id, NULL, NULL,
                 'category', :category_id, :category_name)
        ");

        $stmt->execute([
            ':user_id'        => $userId,
            ':total_price'    => $amount,
            ':cf_order_id'    => $cfOrderId,
            ':category_id'    => $categoryId,
            ':category_name'  => $categoryName,
        ]);

        // (optional) if you ever want local order id:
        $_SESSION['cf_local_order_id'] = $pdo->lastInsertId();
    } catch (PDOException $e) {
        // don't break the payment flow if order insert fails
        error_log('Order insert failed: ' . $e->getMessage());
    }

    echo json_encode([
        'success'            => true,
        'payment_session_id' => $data['payment_session_id'],
        'order_id'           => $cfOrderId,
    ]);
    exit;
}

/* ---------- 7. If Cashfree returned an error ---------- */
echo json_encode([
    'success' => false,
    'message' => $data['message'] ?? 'Cashfree error',
    'details' => $data,
]);
exit;
