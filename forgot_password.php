<?php
require_once 'config.php';
require_once 'functions.php';

$message = '';
$message_type = '';
$debug_reset_link = '';

if (isset($_POST['email'])) {
    $email = clean_input($_POST['email']);

    $select_stmt = $conn->prepare("SELECT * FROM organisations WHERE email=?");
    $select_stmt->bind_param("s", $email);
    $select_stmt->execute();
    $result = $select_stmt->get_result();

    if ($result->num_rows > 0) {
        $organisation = $result->fetch_assoc();

        $token = bin2hex(random_bytes(50));

        $update_stmt = $conn->prepare("UPDATE organisations SET reset_token=?, token_expire=DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE email=?");
        $update_stmt->bind_param("ss", $token, $email);
        $update_stmt->execute();
        $update_stmt->close();

        $link = build_absolute_url('reset_password.php') . '?token=' . urlencode($token);
        $subject = 'Password Reset Request';
        $recipient_name = htmlspecialchars($organisation['organisation_name'] ?? $email, ENT_QUOTES, 'UTF-8');
        $escaped_link = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');

        $html_body = '
            <div style="margin:0;padding:32px 16px;background-color:#f4f6fb;font-family:Segoe UI,Tahoma,Geneva,Verdana,sans-serif;color:#333333;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:600px;margin:0 auto;background:#ffffff;border-radius:18px;overflow:hidden;box-shadow:0 16px 40px rgba(0,0,0,0.08);">
                    <tr>
                        <td style="padding:28px 32px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);text-align:center;color:#ffffff;">
                            <div style="font-size:28px;font-weight:700;letter-spacing:0.4px;">Rotary Club</div>
                            <div style="margin-top:8px;font-size:15px;opacity:0.95;">Password Reset Request</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:36px 32px 24px;">
                            <p style="margin:0 0 16px;font-size:16px;line-height:1.7;">Hello ' . $recipient_name . ',</p>
                            <p style="margin:0 0 16px;font-size:15px;line-height:1.8;color:#555555;">
                                We received a request to reset the password for your organisation account.
                            </p>
                            <p style="margin:0 0 24px;font-size:15px;line-height:1.8;color:#555555;">
                                Click the button below to create a new password. This reset link will remain active for <strong>1 hour</strong>.
                            </p>
                            <div style="text-align:center;margin:30px 0;">
                                <a href="' . $escaped_link . '" style="display:inline-block;padding:14px 28px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#ffffff;text-decoration:none;border-radius:10px;font-size:15px;font-weight:700;letter-spacing:0.5px;">
                                    Reset Password
                                </a>
                            </div>
                            <p style="margin:0;font-size:14px;line-height:1.8;color:#666666;">
                                If you did not request a password reset, you can safely ignore this email. Your current password will remain unchanged.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 32px 28px;border-top:1px solid #eeeeee;font-size:12px;line-height:1.7;color:#888888;text-align:center;">
                            This is an automated message from Rotary Club. Please do not reply directly to this email.
                        </td>
                    </tr>
                </table>
            </div>
        ';

        $alt_body = "Hello " . ($organisation['organisation_name'] ?? $email) . ",\n\n"
            . "We received a request to reset the password for your organisation account.\n"
            . "Use the link below to reset your password:\n"
            . $link . "\n\n"
            . "This link will expire in 1 hour.\n\n"
            . "If you did not request this password reset, you can safely ignore this email.";

        $mail_error = '';
        if (send_app_email($conn, $email, $organisation['organisation_name'] ?? '', $subject, $html_body, $alt_body, $mail_error)) {
            $message = "Reset link sent to your email!";
            $message_type = 'success';
        } elseif (is_local_request()) {
            log_local_email_delivery($email, $subject, $alt_body);
            $debug_reset_link = $link;
            $message = "SMTP is not configured locally, so the reset link is shown below for testing.";
            $message_type = 'success';
        } else {
            $rollback = $conn->prepare("UPDATE organisations SET reset_token=NULL, token_expire=NULL WHERE email=?");
            $rollback->bind_param("s", $email);
            $rollback->execute();
            $rollback->close();

            $message = "We could not send the reset email right now. Please try again later.";
            $message_type = 'error';
        }
    } else {
        $message = "Email not found!";
        $message_type = 'error';
    }

    $select_stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            padding: 50px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 450px;
            width: 100%;
            animation: slideUp 0.5s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .brand-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .brand-logo i {
            font-size: 32px;
            color: #667eea;
        }

        .login-icon {
            font-size: 60px;
            color: #667eea;
            margin-bottom: 20px;
        }

        h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
            text-align: center;
        }

        .subtitle {
            color: #666;
            font-size: 14px;
            text-align: center;
            line-height: 1.6;
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }

        input[type="email"] {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn-login {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .message {
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            line-height: 1.5;
        }

        .message.error {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
        }

        .message.success {
            background: linear-gradient(135deg, #43cea2 0%, #185a9d 100%);
        }

        .debug-link {
            margin-top: 15px;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.16);
            border-radius: 8px;
        }

        .debug-link a {
            color: white;
            text-decoration: none;
            font-weight: 600;
        }

        .debug-link a:hover {
            text-decoration: underline;
        }

        .admin-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }

        .admin-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .admin-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="brand-logo">
                <i class="fas fa-hand-holding-heart"></i>
            </div>
            <div class="login-icon">
                <i class="fas fa-key"></i>
            </div>
            <h1>Forgot Password</h1>
            <p class="subtitle">Enter your registered email address and we'll send you a password reset link.</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo htmlspecialchars($message_type, ENT_QUOTES, 'UTF-8'); ?>">
                <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <div>
                    <div><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php if ($debug_reset_link): ?>
                        <div class="debug-link">
                            <a href="<?php echo htmlspecialchars($debug_reset_link, ENT_QUOTES, 'UTF-8'); ?>">
                                Open password reset link
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" required autofocus>
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-paper-plane"></i> Send Reset Link
            </button>
        </form>

        <div class="admin-link">
            <a href="index.php">Back to Login</a>
        </div>
    </div>
</body>
</html>
