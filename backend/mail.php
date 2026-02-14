<?php
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendEmail($to, $toName, $subject, $body) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.hostinger.com';  
        $mail->SMTPAuth   = true;                 
        $mail->Username   = 'official@aipromptmandi.com';
        $mail->Password   = '#AIPrompt123';         
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; 
        $mail->Port       = 465;                

        // Recipients
        $mail->setFrom('official@aipromptmandi.com', 'AIPrompt Mandi'); 
        $mail->addAddress($to, $toName);

        // Content
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->isHTML(true); 

        // Send email
        if ($mail->send()) {
            return true;
        } else {
            return false;
        }
    } catch (Exception $e) {
        echo "Error: {$mail->ErrorInfo}";
        return false;
    }
}
?>
