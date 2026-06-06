<?php
session_start();
include 'db.php';
include 'auth.php';

verifyCsrf();

$message = "";

if (isset($_POST['login'])) {

    $student_email = mysqli_real_escape_string($conn, $_POST['student_email']);
    $password = $_POST['password'];

    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE student_email = ?");
    mysqli_stmt_bind_param($stmt, "s", $student_email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {

        $user = mysqli_fetch_assoc($result);

        if (password_verify($password, $user['password'])) {

            session_regenerate_id(true);  

            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['is_verified'] = $user['is_verified'];

            header("Location: dashboard.php");
            exit();

        } else {
            $message = "Incorrect password.";
        }

    } else {
        $message = "No account found with that email.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CampusLink | Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        * { box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: #f8f1e8;
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        a { text-decoration: none; }

        /*  Top bar  */
        .topbar {
            padding: 20px 12%;
            background: white;
            border-bottom: 1px solid #eadfd2;
        }

        .nav-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #111827;
        }

        .nav-logo-icon {
            width: 42px;
            height: 42px;
            background: orange;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .nav-logo-text strong {
            display: block;
            font-size: 17px;
            font-weight: 800;
        }

        .nav-logo-text span {
            display: block;
            font-size: 11px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        /*  Page center  */
        .page {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 50px 20px;
        }

        /*  Card  */
        .card {
            background: white;
            border: 1px solid #eadfd2;
            border-radius: 24px;
            padding: 44px 48px;
            width: 100%;
            max-width: 460px;
            box-shadow: 0 6px 24px rgba(0,0,0,0.07);
        }

        /*  Logo inside card  */
        .card-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 32px;
        }

        .card-logo-icon {
            width: 48px;
            height: 48px;
            background: orange;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .card-logo-text strong {
            display: block;
            font-size: 19px;
            font-weight: 800;
            color: #111827;
        }

        .card-logo-text span {
            display: block;
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .card h1 {
            font-size: 26px;
            font-weight: 800;
            color: #020617;
            margin: 0 0 6px;
        }

        .card .subtitle {
            font-size: 15px;
            color: #64748b;
            margin: 0 0 28px;
        }

        /*  Flash message  */
        .flash {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
            border-left: 4px solid #ef4444;
            border-radius: 12px;
            padding: 13px 16px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .flash svg {
            flex-shrink: 0;
            color: #ef4444;
        }

        /* Form  */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .form-group input {
            width: 100%;
            padding: 13px 16px;
            border: 1.5px solid #e5ddd4;
            border-radius: 12px;
            font-size: 15px;
            color: #111827;
            background: white;
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
            font-family: Arial, sans-serif;
        }

        .form-group input: focus {
            border-color: orange;
            box-shadow: 0 0 0 3px rgba(255,165,0,0.12);
        }

        /*  Submit  */
        .submit-btn {
            width: 100%;
            padding: 15px;
            background: orange;
            color: white;
            border: none;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 800;
            cursor: pointer;
            margin-top: 6px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.13);
            transition: opacity 0.15s;
            font-family: Arial, sans-serif;
        }

        .submit-btn:hover { opacity: 0.9; }

        /*  Divider  */
        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 24px 0;
            color: #9ca3af;
            font-size: 13px;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #eadfd2;
        }

        /*  Register link  */
        .register-row {
            text-align: center;
            font-size: 14px;
            color: #64748b;
        }

        .register-row a {
            color: orange;
            font-weight: 700;
        }

        .register-row a:hover { opacity: 0.8; }

        /*  Trust badges  */
        .trust-row {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 28px;
            padding-top: 22px;
            border-top: 1px solid #eadfd2;
        }

        .trust-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: #9ca3af;
        }

        .trust-item svg { color: #9ca3af; }

        /*  Footer  */
        footer {
            padding: 20px 12%;
            background: #fffaf3;
            border-top: 1px solid #eadfd2;
            color: #6b7280;
            display: flex;
            justify-content: space-between;
            font-size: 13px;
        }

        .footer-brand {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .footer-logo {
            width: 26px;
            height: 26px;
            background: orange;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 10px;
        }

        @@media (max-width: 600px) {
		    .topbar {
		        padding: 14px 16px;
		    }
		
		    .nav-logo-text span {
		        display: none;
		    }
		
		    .page {
		        padding: 30px 16px;
		        align-items: flex-start;
		    }
		
		    .card {
		        padding: 28px 20px;
		        border-radius: 18px;
		    }
		
		    .card h1 {
		        font-size: 22px;
		    }
		
		    .card .subtitle {
		        font-size: 14px;
		    }
		
		    .card-logo-icon {
		        width: 40px;
		        height: 40px;
		    }
		
		    .card-logo-text strong {
		        font-size: 16px;
		    }
		
		    .trust-row {
		        flex-direction: column;
		        align-items: center;
		        gap: 12px;
		    }
		
		     footer { flex-direction: column; gap: 10px; text-align: center; align-items: center; }
            .trust-row { flex-direction: column; align-items: center; gap: 12px; }
		}
    </style>
</head>
<body>

<!--  Top bar  -->
<div class="topbar">
    <a href="index.php" class="nav-logo">
        <div class="nav-logo-icon">
            <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="white"
                 stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                <line x1="3" y1="6" x2="21" y2="6"/>
                <path d="M16 10a4 4 0 0 1-8 0"/>
            </svg>
        </div>
        <div class="nav-logo-text">
            <strong>CampusLink</strong>
            <span>Built for student hustle</span>
        </div>
    </a>
</div>

<!--  Page  -->
<div class="page">
    <div class="card">

        <!-- Logo inside card -->
        <div class="card-logo">
            <div class="card-logo-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white"
                     stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                    <line x1="3" y1="6" x2="21" y2="6"/>
                    <path d="M16 10a4 4 0 0 1-8 0"/>
                </svg>
            </div>
            <div class="card-logo-text">
                <strong>CampusLink</strong>
                <span>Built for student hustle</span>
            </div>
        </div>

        <h1>Welcome back</h1>
        <p class="subtitle">Sign in to your student account to continue.</p>

        <!-- Error message -->
        <?php if ($message): ?>
            <div class="flash">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <form method="POST">
            <?php csrfInput(); ?>

            <div class="form-group">
                <label>Student email</label>
                <input type="email" name="student_email"
                       placeholder="yourname@institution.ac.za" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password"
                       placeholder="Enter your password" required>
            </div>

            <button type="submit" name="login" class="submit-btn">Sign in</button>

        </form>

        <div class="divider">or</div>

        <div class="register-row">
            Don't have an account? <a href="register.php">Create one free</a>
        </div>
		
		<div class="register-row" style="margin-top:16px;">
		    Didn't receive an email?
		    <a href="resend_verification.php">Resend</a>
		</div>

        <!-- Trust badges -->
        <div class="trust-row">
            <div class="trust-item">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
                Verified students only
            </div>
            <div class="trust-item">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2"/>
                    <path d="M7 11V7a5 5 0 0110 0v4"/>
                </svg>
                Secure login
            </div>
            <div class="trust-item">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 00-3-3.87"/>
                    <path d="M16 3.13a4 4 0 010 7.75"/>
                </svg>
                SA student community
            </div>
        </div>

    </div>
</div>

<!--  Footer  -->
<footer>
    <div class="footer-brand">
        <div class="footer-logo">CL</div>
        <strong>CampusLink</strong>
        <span>Built for student hustle</span>
    </div>
    <div>
        © 2026 CampusLink. A safer marketplace for South African students.
        &nbsp;·&nbsp; <a href="about.php" style="color:#6b7280;">About</a>
        &nbsp;·&nbsp; <a href="contact.php" style="color:#6b7280;">Contact</a>
        &nbsp;·&nbsp; <a href="terms.php" style="color:#6b7280;">Terms</a>
    </div>
</footer>

</body>
</html>