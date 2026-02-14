<?php
session_start();
require('./backend/db.php');

$data = json_decode(file_get_contents("php://input"), true);

if (!empty($data['email'])) {
    // Check if user exists or create
    $email = $data['email'];
    $name = $data['name'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // Create new user
        $stmt = $pdo->prepare("INSERT INTO users (username, email) VALUES (:username, :email)");
        $stmt->execute(['username' => $name, 'email' => $email]);
        $user_id = $pdo->lastInsertId();
    } else {
        $user_id = $user['id'];
    }

    $_SESSION['user'] = [
        'user_id' => $user_id,
        'email' => $email,
        'username' => $name
    ];

    // Check if from_cart flag is set
    $from_cart = !empty($data['from_cart']) && $data['from_cart'] == 1;

    if ($from_cart) {
        $_SESSION['from_cart'] = true;
    }

    echo json_encode(["success" => true, "from_cart" => $from_cart]);
    exit;
}

echo json_encode(["success" => false]);
