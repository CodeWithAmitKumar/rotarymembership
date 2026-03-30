<?php
// Sanitize input data
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Check if user is logged in as admin
function requireAdminLogin() {
    if (!isset($_SESSION['admin_id'])) {
        header("Location: login.php");
        exit();
    }
}

function is_local_request() {
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
    $remote_addr = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

    return in_array($host, ['localhost', '127.0.0.1', '::1'], true)
        || strpos($host, 'localhost:') === 0
        || strpos($host, '127.0.0.1:') === 0
        || $remote_addr === '127.0.0.1'
        || $remote_addr === '::1';
}

function build_absolute_url($path = '') {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';

    return $scheme . '://' . $host . app_url($path);
}

function get_email_settings(mysqli $conn) {
    $result = mysqli_query($conn, "SELECT * FROM email_settings LIMIT 1");

    if (!$result || mysqli_num_rows($result) === 0) {
        return null;
    }

    return mysqli_fetch_assoc($result);
}

function log_local_email_delivery($recipient, $subject, $body) {
    $log_directory = app_path('storage/mail_logs');

    if (!is_dir($log_directory) && !mkdir($log_directory, 0777, true) && !is_dir($log_directory)) {
        return false;
    }

    $log_file = $log_directory . DIRECTORY_SEPARATOR . 'password_reset.log';
    $entry = sprintf(
        "[%s] To: %s | Subject: %s%s%s%s",
        date('Y-m-d H:i:s'),
        $recipient,
        $subject,
        PHP_EOL,
        $body,
        PHP_EOL . str_repeat('-', 80) . PHP_EOL
    );

    return file_put_contents($log_file, $entry, FILE_APPEND) !== false ? $log_file : false;
}

function send_app_email(mysqli $conn, $to_email, $to_name, $subject, $html_body, $alt_body, &$error_message = null) {
    $settings = get_email_settings($conn);

    if (!$settings) {
        $error_message = 'Email settings are not configured.';
        return false;
    }

    $autoload_path = app_path('vendor/autoload.php');
    if (!file_exists($autoload_path)) {
        $error_message = 'Mailer dependency is missing.';
        return false;
    }

    require_once $autoload_path;

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = $settings['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $settings['smtp_username'];
        $mail->Password = $settings['smtp_password'];
        $mail->Port = (int) $settings['smtp_port'];
        $mail->CharSet = 'UTF-8';

        if ($mail->Port === 465) {
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        }

        $from_email = $settings['smtp_from_email'] ?: $settings['smtp_username'];
        $from_name = $settings['smtp_from_name'] ?: 'Rotary Club';

        $mail->setFrom($from_email, $from_name);
        $mail->addAddress($to_email, $to_name ?: $to_email);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $html_body;
        $mail->AltBody = $alt_body;
        $mail->send();

        return true;
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        $error_message = $e->getMessage();
        return false;
    }
}
