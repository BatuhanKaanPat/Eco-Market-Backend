<?php
// Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load mail configuration
require_once __DIR__ . '/mail_config.php';

/**
 * Mail class for handling email operations
 */
class Mail {
    /**
     * Send an email using PHPMailer
     *
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $message Email message (HTML)
     * @return bool Whether the email was sent successfully
     */
    public static function send($to, $subject, $message) {
        $mail = new PHPMailer(true);
        
        try {
            // SMTP Server settings
            $mail->SMTPDebug = DEBUG_LEVEL;
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = EMAIL;
            $mail->Password   = EMAIL_PASSWORD;
            $mail->SMTPSecure = SMTP_SECURITY;
            $mail->Port       = SMTP_PORT;
            
            // Recipients
            $mail->setFrom(EMAIL, FULLNAME);
            $mail->addAddress($to, $to);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $message;
            $mail->AltBody = strip_tags($message);
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            // Log error message
            error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
            return false;
        }
    }
}

/**
 * Send verification email with code
 *
 * @param string $email Recipient email address
 * @param string $code Verification code
 * @return bool Whether the email was sent successfully
 */
function sendVerificationEmail($email, $code) {
    $subject = "Email Verification - Eco Market";
    
    // Create HTML message
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #2e7d32; color: white; padding: 15px; text-align: center; }
            .content { padding: 20px; border: 1px solid #ddd; }
            .code { font-size: 24px; font-weight: bold; text-align: center; margin: 20px 0; letter-spacing: 5px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Eco Market</h2>
            </div>
            <div class='content'>
                <p>Hello,</p>
                <p>Thank you for registering with Eco Market. To complete your registration, please use the verification code below:</p>
                <div class='code'>$code</div>
                <p>If you did not request this code, please ignore this email.</p>
                <p>Best regards,<br>The Eco Market Team</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return Mail::send($email, $subject, $message);
} 