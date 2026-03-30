<?php
require_once 'config.php';

$message = '';
$message_type = 'error';

if (!isset($_POST['token'], $_POST['password'])) {
    $message = "Invalid request!";
} else {
    $token = trim($_POST['token']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("SELECT organisation_id FROM organisations WHERE reset_token=? AND token_expire > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $stmt->close();

        $stmt = $conn->prepare("UPDATE organisations
            SET password=?, reset_token=NULL, token_expire=NULL
            WHERE reset_token=? AND token_expire > NOW()");

        $stmt->bind_param("ss", $password, $token);
        $stmt->execute();
        $stmt->close();
        echo "<script>alert('Password reset successfully!'); window.location.href='index.php';</script>";
        exit();
    } else {
        $stmt->close();
        $message = "Invalid or expired token!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Update</title>
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

        .message {
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            line-height: 1.5;
        }

        .message.error {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
        }

        .message.success {
            background: linear-gradient(135deg, #43cea2 0%, #185a9d 100%);
        }

        .action-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            text-decoration: none;
            font-size: 16px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
        }

        .action-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
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
                <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
            </div>
            <h1>Password Update</h1>
            <p class="subtitle">Your password reset request has been processed.</p>
        </div>

        <div class="message <?php echo htmlspecialchars($message_type, ENT_QUOTES, 'UTF-8'); ?>">
            <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
            <span><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></span>
        </div>

        <a class="action-link" href="<?php echo $message_type === 'success' ? 'index.php' : 'forgot_password.php'; ?>">
            <i class="fas <?php echo $message_type === 'success' ? 'fa-sign-in-alt' : 'fa-paper-plane'; ?>"></i>&nbsp;
            <?php echo $message_type === 'success' ? 'Go to Login' : 'Request New Link'; ?>
        </a>

        <div class="admin-link">
            <a href="index.php">Back to Login</a>
        </div>
    </div>
</body>
</html>
