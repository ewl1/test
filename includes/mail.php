<?php
function ensure_phpmailer_loaded()
{
    if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        return true;
    }

    foreach ([INCLUDES . 'vendor/autoload.php', BASEDIR . 'vendor/autoload.php'] as $autoloadPath) {
        if (is_file($autoloadPath)) {
            require_once $autoloadPath;
            break;
        }
    }

    return class_exists('PHPMailer\\PHPMailer\\PHPMailer');
}

function send_mail_message($toEmail, $toName, $subject, $html, $text = '')
{
    if (!ensure_phpmailer_loaded()) {
        return false;
    }

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = MAIL_HOST;
        $mail->Port = MAIL_PORT;
        $mail->SMTPAuth = true;
        $mail->Username = MAIL_USERNAME;
        $mail->Password = MAIL_PASSWORD;
        $mail->CharSet = 'UTF-8';

        if ((int)MAIL_PORT === 465) {
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        }

        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName ?: $toEmail);
        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body = $html;
        $mail->AltBody = $text ?: strip_tags($html);
        return $mail->send();
    } catch (Throwable $e) {
        error_log('Mail send error: ' . $e->getMessage());
        return false;
    }
}
