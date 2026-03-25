<?php
require_once '../config.php';
require_once '../functions.php';
require '../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
requireAdminLogin();
$page_title = 'Add Organisation';

$success = '';
$error = '';

// Email sending function using PHPMailer
function sendOrganisationCredentials($to_email, $org_name, $password, $conn) {
    // Get email settings from database
    $email_settings_query = mysqli_query($conn, "SELECT * FROM email_settings LIMIT 1");
    
    if (mysqli_num_rows($email_settings_query) == 0) {
        return false;
    }
    
    $email_settings = mysqli_fetch_assoc($email_settings_query);
    
    $smtp_host = $email_settings['smtp_host'];
    $smtp_port = $email_settings['smtp_port'];
    $smtp_username = $email_settings['smtp_username'];
    $smtp_password = $email_settings['smtp_password'];
    $smtp_from_email = $email_settings['smtp_from_email'];
    $smtp_from_name = $email_settings['smtp_from_name'];
    
    $mail = new PHPMailer(true);
    
    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host       = $smtp_host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp_username;
        $mail->Password   = $smtp_password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $smtp_port;

        //Recipients
        $mail->setFrom($smtp_from_email, $smtp_from_name);
        $mail->addAddress($to_email, $org_name);

        // Content - Improved HTML email template
        $mail->isHTML(true);
        $mail->Subject = 'Login Credentials - ' . $org_name;
        $mail->Body    = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .credentials-box { background: white; border: 2px solid #667eea; border-radius: 10px; padding: 20px; margin: 20px 0; }
                .credentials-box h3 { color: #667eea; margin-top: 0; }
                .credential-row { margin: 10px 0; }
                .credential-label { font-weight: bold; color: #555; }
                .credential-value { color: #333; font-size: 16px; }
                .password-value { background: #667eea; color: white; padding: 5px 15px; border-radius: 5px; font-weight: bold; }
                .footer { text-align: center; margin-top: 20px; color: #888; font-size: 12px; }
                .btn { display: inline-block; background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin-top: 15px; }
            </style>
        </head>
        <body>
            <div class=\"container\">
                <div class=\"header\">
                    <h1>🎉 Welcome to Rotary Membership!</h1>
                    <p>Your organisation has been registered successfully</p>
                </div>
                <div class=\"content\">
                    <p>Dear <strong>" . $org_name . "</strong>,</p>
                    <p>Thank you for joining Rotary Membership. Your organisation has been successfully registered in our system.</p>
                    
                    <div class=\"credentials-box\">
                        <h3>🔐 Login Credentials</h3>
                        <div class=\"credential-row\">
                            <span class=\"credential-label\">Email:</span>
                            <span class=\"credential-value\">" . $to_email . "</span>
                        </div>
                        <div class=\"credential-row\">
                            <span class=\"credential-label\">Password:</span>
                            <span class=\"password-value\">" . $password . "</span>
                        </div>
                    </div>
                    
                    <p><strong>Important:</strong> Please login and change your password after first login for security purposes.</p>
                    
                    <p>Best regards,<br>
                    <strong>Admin Team</strong></p>
                </div>
                <div class=\"footer\">
                    <p>This is an automated message. Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        $mail->AltBody = "Dear " . $org_name . ",\n\n" .
            "Your organisation has been registered successfully!\n\n" .
            "Login Credentials:\n" .
            "Email: " . $to_email . "\n" .
            "Password: " . $password . "\n\n" .
            "Please login and change your password.\n\n" .
            "Best regards,\n" .
            "Admin Team";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $organisation_name = clean_input($_POST['organisation_name']);
    $contact_no = clean_input($_POST['contact_no']);
    $email = clean_input($_POST['email']);
    $whatsapp_no = clean_input($_POST['whatsapp_no']);
    $address = clean_input($_POST['address']);
    
    // Generate numeric password (6 digits)
    $password = str_pad(mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);
    
    // Check if email already exists
    $check_email = $conn->prepare("SELECT organisation_id FROM organisations WHERE email = ?");
    $check_email->bind_param("s", $email);
    $check_email->execute();
    
    if ($check_email->get_result()->num_rows > 0) {
        $error = "Email already exists!";
    } else {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert organisation with password
        $stmt = $conn->prepare("INSERT INTO organisations (organisation_name, contact_no, email, whatsapp_no, address, password) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $organisation_name, $contact_no, $email, $whatsapp_no, $address, $hashed_password);
        
        if ($stmt->execute()) {
            // Try to send email with credentials
            $email_sent = sendOrganisationCredentials($email, $organisation_name, $password, $conn);
            
            if ($email_sent) {
                $success = "Organisation added successfully! Login credentials sent to email.";
            } else {
                $success = "Organisation added successfully! (Email could not be sent - Password is: " . $password . ")";
            }
        } else {
            $error = "Failed to add organisation!";
        }
        $stmt->close();
    }
    $check_email->close();
}
?>

<?php include 'header.php'; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h1><i class="fas fa-plus-circle"></i> Add New Organisation</h1>
                    <p>Enter the organisation details below</p>
                </div>
                
                <form method="POST">
                    <div class="form-group">
                        <label><i class="fas fa-building"></i> Organisation Name *</label>
                        <input type="text" name="organisation_name" required placeholder="Enter organisation name">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email *</label>
                        <input type="email" name="email" required placeholder="Enter email address">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> Contact No *</label>
                        <input type="text" name="contact_no" required placeholder="Enter contact number">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fab fa-whatsapp"></i> WhatsApp No</label>
                        <input type="text" name="whatsapp_no" placeholder="Enter WhatsApp number">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-map-marker-alt"></i> Address</label>
                        <textarea name="address" placeholder="Enter address"></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Organisation
                        </button>
                        <a href="manage_organisations.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                </form>
            </div>

