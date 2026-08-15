<?php
// =========================================================================
// GMAIL SMTP CONFIGURATION - AUTOMATICALLY UPDATED FROM ADMIN SETTINGS
// =========================================================================

define('SMTP_ENABLED', true);
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_USER', 'mrstrome4@gmail.com');
define('SMTP_PASS', 'Zeel_patel@224');
define('SMTP_FROM_EMAIL', 'mrstrome4@gmail.com');
define('SMTP_FROM_NAME', 'Dr. Subhash Placement Cell');

/**
 * Socket-based Gmail SMTP Client Helper
 */
function sendSmtpEmail($toEmail, $subject, $bodyHtml) {
    if (!SMTP_ENABLED || empty(SMTP_PASS)) return false;
    try {
        $socket = @fsockopen(SMTP_HOST, SMTP_PORT, $errno, $errstr, 12);
        if (!$socket) return false;
        $read = function() use ($socket) { $s = ''; while ($str = fgets($socket, 515)) { $s .= $str; if (substr($str, 3, 1) == ' ') break; } return $s; };
        $write = function($cmd) use ($socket) { fputs($socket, $cmd . "\r\n"); };
        $read(); $write('EHLO ' . gethostname()); $read();
        if (SMTP_SECURE === 'tls') {
            $write('STARTTLS'); $read();
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT);
            $write('EHLO ' . gethostname()); $read();
        }
        $write('AUTH LOGIN'); $read();
        $write(base64_encode(SMTP_USER)); $read();
        $write(base64_encode(SMTP_PASS)); $authResp = $read();
        if (substr($authResp, 0, 3) != '235') { fclose($socket); return false; }
        $write('MAIL FROM: <' . SMTP_FROM_EMAIL . '>'); $read();
        $write('RCPT TO: <' . $toEmail . '>'); $read();
        $write('DATA'); $read();
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM_EMAIL . ">\r\n";
        $headers .= "To: <" . $toEmail . ">\r\n";
        $headers .= "Subject: " . $subject . "\r\n";
        $headers .= "Date: " . date('r') . "\r\n";
        $write($headers . "\r\n" . $bodyHtml . "\r\n."); $read();
        $write('QUIT'); fclose($socket); return true;
    } catch (Exception $e) { return false; }
}
