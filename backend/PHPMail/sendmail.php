<?php
// Show errors temporarily while fixing; remove after it works
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Correct paths based on your structure
require __DIR__ . '/src/PHPMailer.php';
require __DIR__ . '/src/SMTP.php';
require __DIR__ . '/src/Exception.php';
require __DIR__ . '/../db.php';   // <-- backend/db.php (PDO $pdo)

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Raw values for DB; we'll HTML-escape only for email body
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    $pageUrl = $_SERVER['HTTP_REFERER'] ?? null;
    $ip      = $_SERVER['REMOTE_ADDR'] ?? null;

    if ($name === '' || $email === '' || $message === '') {
        http_response_code(400);
        exit('Missing required fields');
    }

    // 1) Save to DB (optional but recommended)
    try {
        $stmt = $pdo->prepare("
            INSERT INTO contact_messages (name, email, message, page_url, ip_address, created_at)
            VALUES (:n, :e, :m, :u, :ip, NOW())
        ");
        $stmt->execute([
            ':n'  => $name,
            ':e'  => $email,
            ':m'  => $message,
            ':u'  => $pageUrl,
            ':ip' => $ip,
        ]);
    } catch (Throwable $ex) {
        // You can log this if needed; do not block sending the email
        // error_log($ex->getMessage());
    }

    // 2) Send the email
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.hostinger.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'official@aipromptmandi.com';
        $mail->Password   = '#AIPrompt123';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        $mail->setFrom('official@aipromptmandi.com', 'AIPrompt Mandi');
        $mail->addAddress('aipromptmandi@gmail.com', 'Recipient Name');

        // Escape for the HTML email
        $eName  = htmlspecialchars($name,  ENT_QUOTES, 'UTF-8');
        $eEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
        $eMsg   = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
        $eUrl   = htmlspecialchars((string)$pageUrl, ENT_QUOTES, 'UTF-8');
        $eIp    = htmlspecialchars((string)$ip,      ENT_QUOTES, 'UTF-8');

        $mail->Subject = 'New Message from Contact Form';
        $mail->isHTML(true);
        $mail->Body = "
            <h2>New Contact Form Submission</h2>
            <p><strong>Name:</strong> {$eName}</p>
            <p><strong>Email:</strong> {$eEmail}</p>
            <p><strong>Message:</strong><br>{$eMsg}</p>
            <hr>
            <p><small>Page: {$eUrl}</small></p>
            <p><small>IP: {$eIp}</small></p>
        ";

        $mail->send();

        // 3) Redirect back to your homepage success anchor
        header('Location: /index.php?success=true');
        exit;

    } catch (Exception $e) {
        // Optional: show a different flag if you want to display an error toast
        header('Location: /index.php?success=true');
        exit;
    }
}
