<?php
/**
 * Sends a system email using PHP's native mail function.
 * This avoids requiring external Composer packages (like PHPMailer) while keeping full compatibility and zero linter warnings.
 */
function sendSystemEmail($toEmail, $toName, $subject, $bodyHtml) {
    if (empty($toEmail)) {
        return false;
    }

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: InternMatch Platform <no-reply@internmatch.com>" . "\r\n";

    // Attempt sending via native mail function
    $success = @mail($toEmail, $subject, $bodyHtml, $headers);
    
    if (!$success) {
        error_log("Failed to send email to {$toEmail} with subject '{$subject}'");
    }

    return $success;
}
?>