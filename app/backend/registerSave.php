<?php
// registerSave.php
include __DIR__ . '/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $name     = $_POST['name'] ?? '';
    $email    = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // ✅ carry embed + redirect from mandiresgistration.php
    $embed = (isset($_GET['embed']) && $_GET['embed'] == '1') ? '1' : '0';

    $redirect = $_GET['redirect'] ?? 'newchat.php';
    $redirect = preg_replace('/[^a-zA-Z0-9_\-\/\.\?\=\&]/', '', $redirect);
    if ($redirect === '') $redirect = 'newchat.php';

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
        $stmt->execute([$name, $hashedPassword, $email]);

        // ✅ After register -> go to login (carry embed + redirect + registered flag)
        if ($embed === '1') {
            header("Location: ../mandilogin.php?embed=1&redirect=" . urlencode($redirect) . "&registered=1");
        } else {
            header("Location: ../mandilogin.php?redirect=" . urlencode($redirect) . "&registered=1");
        }
        exit;

    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>
