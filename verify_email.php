<?php
include 'db.php';
include 'auth.php';

$state   = 'form'; // form | success | error
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!isset($_GET['token'])) {
        $state   = 'error';
        $message = 'Invalid verification link.';
    } else {
        $state = 'form';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['token'])) {
        $state   = 'error';
        $message = 'Invalid verification request.';
    } else {
        $token = $_POST['token'];
        $stmt  = mysqli_prepare($conn, "SELECT user_id FROM users WHERE verification_token = ? AND is_verified = 0");
        mysqli_stmt_bind_param($stmt, "s", $token);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) == 0) {
            $state   = 'error';
            $message = 'This verification link is invalid, expired, or the email has already been verified.';
        } else {
            $upd = mysqli_prepare($conn, "UPDATE users SET is_verified = 1, verification_token = NULL WHERE verification_token = ?");
            mysqli_stmt_bind_param($upd, "s", $token);
            if (mysqli_stmt_execute($upd)) {
                $state   = 'success';
                $message = 'Your email has been verified. You can now log in to CampusLink.';
            } else {
                $state   = 'error';
                $message = 'Verification failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email — CampusLink</title>
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
            margin: 0 auto 20px;
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
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .icon-wrap.orange { background: #fff0d6; }
        .icon-wrap.green  { background: #d1fae5; }
        .icon-wrap.red    { background: #fee2e2; }

        h1 {
            font-family: 'Sora', sans-serif;
            font-size: 22px;
            font-weight: 800;
            color: #111827;
            margin-bottom: 10px;
        }

        p {
            font-size: 15px;
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 28px;
        }

        .verify-btn {
            display: block;
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
            transition: opacity 0.15s;
        }

        .verify-btn:hover { opacity: 0.9; }

        .login-link {
            display: inline-block;
            margin-top: 16px;
            color: orange;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
        }

        .login-link:hover { text-decoration: underline; }

        @media(max-width: 480px) {
            .card { padding: 36px 24px; }
        }
    </style>
</head>
<body>

<div class="card">

    <!-- Logo -->
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

    <?php if ($state === 'form'): ?>

        <div class="icon-wrap orange">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="orange"
                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                <polyline points="22,6 12,13 2,6"/>
            </svg>
        </div>

        <h1>Verify your email</h1>
        <p>Click the button below to confirm your student email address and activate your CampusLink account.</p>

        <form method="POST">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token']); ?>">
            <button class="verify-btn" type="submit">Verify Email</button>
        </form>

    <?php elseif ($state === 'success'): ?>

        <div class="icon-wrap green">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#059669"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
        </div>

        <h1>Email verified!</h1>
        <p><?php echo htmlspecialchars($message); ?></p>
        <a href="login.php" class="verify-btn" style="text-decoration:none; display:block;">Go to Login</a>

    <?php elseif ($state === 'error'): ?>

        <div class="icon-wrap red">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#dc2626"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
        </div>

        <h1>Verification failed</h1>
        <p><?php echo htmlspecialchars($message); ?></p>
        <a href="register.php" class="verify-btn" style="text-decoration:none; display:block;">Back to Register</a>

    <?php endif; ?>

</div>

</body>
</html>