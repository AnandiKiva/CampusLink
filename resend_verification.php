<?php
require '/home/campusli/domains/campuslink.co.za/config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';
include 'db.php';

$message      = "";
$message_type = ""; 

if (isset($_POST['resend'])) {
    $email = trim($_POST['student_email']);
    $stmt  = mysqli_prepare($conn, "SELECT user_id, full_name, verification_token, is_verified FROM users WHERE student_email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($user = mysqli_fetch_assoc($result)) {
        if ($user['is_verified'] == 1) {
            $message      = "This account is already verified. You can log in.";
            $message_type = "error";
        } else {
            $token = $user['verification_token'];
            if (empty($token)) {
                $token  = bin2hex(random_bytes(32));
                $update = mysqli_prepare($conn, "UPDATE users SET verification_token = ? WHERE user_id = ?");
                mysqli_stmt_bind_param($update, "si", $token, $user['user_id']);
                mysqli_stmt_execute($update);
            }
            $verify_link = "https://campuslink.co.za/verify_email.php?token=" . $token;
            try {
                $mail             = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = SMTP_USERNAME;
                $mail->Password   = SMTP_PASSWORD;
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                $mail->CharSet    = 'UTF-8';
                $mail->isHTML(true);
                $mail->setFrom(SMTP_USERNAME, 'CampusLink');
                $mail->addAddress($email, $user['full_name']);
                $mail->Subject = "CampusLink Verification Email";
                $mail->Body    = "
                <div style='font-family: Arial, sans-serif; max-width:600px;'>
                    <div style='text-align:center; margin-bottom:20px;'>
                        <img src='https://campuslink.co.za/images/logo.png' alt='CampusLink' style='height:80px;'>
                    </div>
                    <h2>Verify your CampusLink account</h2>
                    <p>Hi {$user['full_name']},</p>
                    <p>Click the button below to verify your student email address.</p>
                    <p style='text-align:center;'>
                        <a href='{$verify_link}' style='background:orange; color:white; padding:12px 24px; text-decoration:none; border-radius:8px; font-weight:bold;'>
                            Verify Email
                        </a>
                    </p>
                    <p>If you did not create this account, you can safely ignore this email.</p>
                    <hr>
                    <p>CampusLink<br>support@campuslink.co.za<br>https://campuslink.co.za</p>
                </div>";
                $mail->send();
                $message      = "Verification email sent! Check your inbox and spam folder.";
                $message_type = "success";
            } catch (Exception $e) {
                $message      = "Unable to send email. Please try again later.";
                $message_type = "error";
            }
        }
    } else {
        $message      = "No account found with that email address.";
        $message_type = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resend Verification — CampusLink</title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            background: #f8f1e8;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .card {
            background: #fffaf3;
            border: 1px solid #eadfd2;
            border-radius: 24px;
            padding: 48px 40px;
            max-width: 440px;
            width: 100%;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0,0,0,0.07);
        }

        .logo {
            width: 64px;
            height: 64px;
            background: orange;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }

        .brand-name {
            font-family: 'Sora', sans-serif;
            font-size: 20px;
            font-weight: 800;
            color: #111827;
            margin-bottom: 4px;
        }

        .brand-sub {
            font-size: 12px;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 32px;
        }

        .icon-wrap {
            width: 72px;
            height: 72px;
            background: #fff0d6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        h1 {
            font-family: 'Sora', sans-serif;
            font-size: 22px;
            font-weight: 800;
            color: #111827;
            margin-bottom: 10px;
        }

        .subtitle {
            font-size: 15px;
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 28px;
        }

        .message {
            padding: 14px 18px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 20px;
            text-align: left;
        }

        .message.success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .message.error   { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

        .form-group { margin-bottom: 16px; text-align: left; }

        label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
        }

        input[type="email"] {
            width: 100%;
            padding: 13px 16px;
            border: 1.5px solid #e5ddd4;
            border-radius: 12px;
            font-size: 15px;
            font-family: 'Inter', sans-serif;
            background: white;
            outline: none;
            transition: border-color 0.15s;
        }

        input[type="email"]:focus { border-color: orange; }

        .submit-btn {
            width: 100%;
            background: orange;
            color: white;
            border: none;
            padding: 14px 24px;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
            margin-top: 8px;
            transition: opacity 0.15s;
        }

        .submit-btn:hover { opacity: 0.9; }

        .back-link {
            display: block;
            margin-top: 20px;
            font-size: 14px;
            color: #6b7280;
        }

        .back-link a { color: orange; font-weight: 600; text-decoration: none; }
        .back-link a:hover { text-decoration: underline; }

        @media(max-width: 480px) {
            .card { padding: 36px 24px; }
        }
    </style>
</head>
<body>

<div class="card">

    <div class="logo">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="white"
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>
            <line x1="3" y1="6" x2="21" y2="6"/>
            <path d="M16 10a4 4 0 01-8 0"/>
        </svg>
    </div>
    <div class="brand-name">CampusLink</div>
    <div class="brand-sub">Built for student hustle</div>

    <div class="icon-wrap">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="orange"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
            <polyline points="22,6 12,13 2,6"/>
        </svg>
    </div>

    <h1>Resend verification</h1>
    <p class="subtitle">Enter your student email below and we'll resend your verification link.</p>

    <?php if ($message): ?>
        <div class="message <?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Student Email</label>
            <input type="email" name="student_email"
                   placeholder="you@institution.ac.za"
                   value="<?php echo isset($_POST['student_email']) ? htmlspecialchars($_POST['student_email']) : ''; ?>"
                   required>
        </div>
        <button class="submit-btn" type="submit" name="resend">Resend Verification Email</button>
    </form>

    <p class="back-link">
        Already verified? <a href="login.php">Log in</a>
        &nbsp;·&nbsp; <a href="register.php">Create account</a>
    </p>

</div>

</body>
</html>