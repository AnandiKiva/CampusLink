<?php
require '/home/campusli/domains/campuslink.co.za/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';

include 'auth.php';
include 'db.php';

verifyCsrf();

$message      = "";
$message_type = "success";

$institution_domains = [
    'Eduvos'                                     => ['vossie.net'],
    'University of Pretoria'                     => ['tuks.co.za', 'up.ac.za'],
    'University of Johannesburg'                 => ['student.uj.ac.za'],
    'University of the Witwatersrand'            => ['students.wits.ac.za'],
    'Tshwane University of Technology'           => ['tut.ac.za','tut4life.ac.za'],
    'University of Cape Town'                    => ['myuct.ac.za', 'uct.ac.za'],
    'Stellenbosch University'                    => ['sun.ac.za', 'stud.sun.ac.za'],
    'University of KwaZulu-Natal'                => ['stu.ukzn.ac.za'],
    'North-West University'                      => ['mynwu.ac.za'],
    'University of South Africa'                 => ['mylife.unisa.ac.za'],
    'University of the Western Cape'             => ['uwc.ac.za', 'students.uwc.ac.za'],
    'Nelson Mandela University'                  => ['mandela.ac.za', 'student.mandela.ac.za'],
    'University of the Free State'               => ['ufs.ac.za', 'student.ufs.ac.za'],
    'University of Limpopo'                      => ['ul.ac.za', 'student.ul.ac.za'],
    'University of Venda'                        => ['univen.ac.za'],
    'Rhodes University'                          => ['ru.ac.za', 'students.ru.ac.za'],
    'Durban University of Technology'            => ['dut.ac.za', 'dut4life.ac.za'],
    'Cape Peninsula University of Technology'    => ['cput.ac.za', 'student.cput.ac.za'],
    'Central University of Technology'           => ['cut.ac.za', 'student.cut.ac.za'],
    'Vaal University of Technology'              => ['vut.ac.za', 'student.vut.ac.za'],
    'Mangosuthu University of Technology'        => ['mut.ac.za', 'student.mut.ac.za'],
    'Walter Sisulu University'                   => ['wsu.ac.za', 'student.wsu.ac.za'],
    'Sefako Makgatho Health Sciences University' => ['smu.ac.za', 'student.smu.ac.za'],
    'Sol Plaatje University'                     => ['spu.ac.za', 'student.spu.ac.za'],
    'University of Mpumalanga'                   => ['ump.ac.za', 'student.ump.ac.za'],
    'Rosebank College'                           => ['rcconnect.edu.za', 'rrconnect.edu.za'],
    'Emeris'                            		 => ['myemeris.edu.za'],
    'Boston City Campus'                         => ['boston.co.za'],
    'IIE Vega'                                   => ['vegaschool.com', 'iie.ac.za'],
];

if (isset($_POST['register'])) {

    $full_name     = mysqli_real_escape_string($conn, $_POST['full_name']);
    $student_email = mysqli_real_escape_string($conn, $_POST['student_email']);
    $phone         = mysqli_real_escape_string($conn, $_POST['phone']);

    // Password length check
    if (strlen($_POST['password']) < 8) {
        $message      = "Password must be at least 8 characters.";
        $message_type = "error";
    } else {

        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        // Determine institution
        if ($_POST['institution_select'] == "Other") {
            $institution = mysqli_real_escape_string($conn, $_POST['institution_custom']);
            $is_other    = true;
        } else {
            $institution = mysqli_real_escape_string($conn, $_POST['institution_select']);
            $is_other    = false;
        }

        // Validate email domain
        $email_domain = strtolower(substr(strrchr($student_email, "@"), 1));
        $email_valid  = false;

        if ($is_other) {
            $generic_domains = ['.ac.za', '.edu', '.edu.za'];
            foreach ($generic_domains as $suffix) {
                if (str_ends_with($email_domain, ltrim($suffix, '.'))) {
                    $email_valid = true;
                    break;
                }
            }
            if (!$email_valid) {
                $message      = "Please use your official student email (e.g. you@institution.ac.za).";
                $message_type = "error";
            }
        } else {
            $allowed = $institution_domains[$institution] ?? [];
            foreach ($allowed as $domain) {
                if (str_ends_with($email_domain, $domain)) {
                    $email_valid = true;
                    break;
                }
            }
            if (!$email_valid) {
                $expected     = !empty($allowed) ? implode(' or ', array_map(fn($d) => "@$d", $allowed)) : 'your institution email';
                $message      = "That email doesn't match your institution. Please use $expected.";
                $message_type = "error";
            }
        }

        if ($email_valid) {
            $stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE student_email = ?");
            mysqli_stmt_bind_param($stmt, "s", $student_email);
            mysqli_stmt_execute($stmt);
            $check = mysqli_stmt_get_result($stmt);

            if (mysqli_num_rows($check) > 0) {
                $message      = "An account with that email already exists.";
                $message_type = "error";
            } else {
                $verification_token = bin2hex(random_bytes(32));
                $stmt = mysqli_prepare($conn, "INSERT INTO users (full_name, student_email, password, phone, institution, verification_token, is_verified) VALUES (?, ?, ?, ?, ?, ?, 0)");
                mysqli_stmt_bind_param($stmt, "ssssss", $full_name, $student_email, $password, $phone, $institution, $verification_token);

                if (mysqli_stmt_execute($stmt)) {

                    $verify_link = "https://campuslink.co.za/verify_email.php?token=$verification_token";

                    $mail = new PHPMailer(true);
                    try {
                    	$mail->isSMTP();
						$mail->Host       = 'smtp.gmail.com';
						$mail->SMTPAuth   = true;
						$mail->Username = $config['smtp_user'];
						$mail->Password = $config['smtp_pass'];
						$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
						$mail->Port       = 587;
                        $mail->CharSet    = 'UTF-8';
                        $mail->isHTML(true);

                        $mail->setFrom('support@campuslink.co.za', 'CampusLink');
                        $mail->addAddress($student_email, $full_name);
                        $mail->Subject = "Verify Your CampusLink Account";

                        $mail->Body = "
                        <div style='font-family: Arial, sans-serif; max-width:600px;'>
                            <div style='text-align:center; margin-bottom:20px;'>
                                <img src='https://campuslink.co.za/images/logo.png' alt='CampusLink' style='height:80px;'>
                            </div>
                            <h2>Welcome to CampusLink!</h2>
                            <p>Hi {$full_name},</p>
                            <p>Thank you for creating your account.</p>
                            <p>Please verify your student email address by clicking the button below.</p>
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
                        $message      = "Account created! Check your student email for a verification link.";
                        $message_type = "success";

                    } catch (Exception $e) {
					    $message      = "Account created but we couldn't send the verification email. Error: ". $mail->ErrorInfo;
					    $message_type = "error";
					}

                } else {
                    $message      = "Error: " . mysqli_error($conn);
                    $message_type = "error";
                }
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
    <title>CampusLink | Register</title>
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

        /* Top bar */
        .topbar {
            padding: 20px 12%;
            background: #fffaf3;
            border-bottom: 1px solid #eadfd2;
            display: flex;
            align-items: center;
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

        /* Page */
        .page {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 50px 20px;
        }

        /* Card */
        .card {
            background: white;
            border: 1px solid #eadfd2;
            border-radius: 24px;
            padding: 44px 48px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 6px 24px rgba(0,0,0,0.07);
        }

        /* Logo inside card */
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

        /* Flash messages */
        .flash {
            border-radius: 12px;
            padding: 13px 16px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-left: 4px solid;
        }

        .flash.success {
            background: #f0fdf4;
            color: #166534;
            border-color: #22c55e;
        }

        .flash.error {
            background: #fef2f2;
            color: #991b1b;
            border-color: #ef4444;
        }

        .flash svg { flex-shrink: 0; }

        /* Form */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .full-width {
            grid-column: span 2;
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

        .form-group input,
        .form-group select {
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
            appearance: none;
            -webkit-appearance: none;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: orange;
            box-shadow: 0 0 0 3px rgba(255,165,0,0.12);
        }

        /* Custom select arrow */
        .select-wrap { position: relative; }

        .select-wrap::after {
            content: '';
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            width: 0;
            height: 0;
            border-left: 5px solid transparent;
            border-right: 5px solid transparent;
            border-top: 6px solid #9ca3af;
            pointer-events: none;
        }

        /* Email hint shown below the email field */
        .field-hint {
            font-size: 12px;
            color: #9ca3af;
            margin-top: 6px;
        }

        /* Submit */
        .submit-btn {
            background: orange;
            color: white;
            padding: 14px;
            border-radius: 14px;
            border: none;
            font-weight: 700;
            width: 100%;
            margin-top: 22px;
            cursor: pointer;
            font-size: 15px;
        }

        .submit-btn:hover { opacity: 0.9; }

        /* Divider */
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

        /* Login link */
        .login-row {
            text-align: center;
            font-size: 14px;
            color: #64748b;
        }

        .login-row a {
            color: orange;
            font-weight: 700;
        }

        .login-row a:hover { opacity: 0.8; }

        /* Trust badges */
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

        /* Footer */
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

        @media(max-width: 600px) {
            .card { padding: 32px 24px; }
            .topbar { padding: 16px 20px; }
            .form-row { grid-template-columns: 1fr; }
            footer { flex-direction: column; gap: 10px; text-align: center; align-items: center; }
            .trust-row { flex-direction: column; align-items: center; gap: 12px; }
        }
    </style>
</head>
<body>

<!-- Top bar -->
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

<!-- Page -->
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

        <h1>Create your account</h1>
        <p class="subtitle">Join verified South African students on CampusLink.</p>

        <!-- Flash message -->
        <?php if ($message): ?>
            <div class="flash <?php echo $message_type; ?>">
                <?php if ($message_type === 'success'): ?>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                <?php else: ?>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                <?php endif; ?>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <form method="POST">
            <?php csrfInput(); ?>

            <!-- Name + Email -->
            <div class="form-row">
                <div class="form-group">
                    <label>Full name</label>
                    <input type="text" name="full_name"
                           placeholder="e.g. Thabo Nkosi" required>
                </div>
                <div class="form-group">
                    <label>Student email</label>
                    <input type="email" name="student_email"
                           id="studentEmail"
                           placeholder="you@institution.ac.za" required>
                    <p class="field-hint" id="emailHint">Must match your institution's student email domain.</p>
                </div>
            </div>

            <!-- Phone + Institution -->
            <div class="form-row">
                <div class="form-group">
                    <label>Phone number</label>
                    <input type="tel" name="phone"
                           placeholder="e.g. 071 234 5678">
                </div>

                <div class="form-group">
                    <label>Institution</label>

                    <div class="select-wrap">
                        <select name="institution_select"
                                id="institutionSelect"
                                onchange="toggleInstitutionInput()"
                                required>

                            <option value="">Select institution</option>
                            <option value="Eduvos">Eduvos</option>
                            <option value="University of Pretoria">University of Pretoria</option>
                            <option value="University of Johannesburg">University of Johannesburg</option>
                            <option value="University of the Witwatersrand">Wits University</option>
                            <option value="Tshwane University of Technology">Tshwane University of Technology</option>
                            <option value="University of Cape Town">University of Cape Town</option>
                            <option value="Stellenbosch University">Stellenbosch University</option>
                            <option value="University of KwaZulu-Natal">University of KwaZulu-Natal</option>
                            <option value="North-West University">North-West University</option>
                            <option value="University of South Africa">UNISA</option>
                            <option value="University of the Western Cape">University of the Western Cape</option>
                            <option value="Nelson Mandela University">Nelson Mandela University</option>
                            <option value="University of the Free State">University of the Free State</option>
                            <option value="University of Limpopo">University of Limpopo</option>
                            <option value="University of Venda">University of Venda</option>
                            <option value="Rhodes University">Rhodes University</option>
                            <option value="Durban University of Technology">Durban University of Technology</option>
                            <option value="Cape Peninsula University of Technology">Cape Peninsula University of Technology</option>
                            <option value="Central University of Technology">Central University of Technology</option>
                            <option value="Vaal University of Technology">Vaal University of Technology</option>
                            <option value="Mangosuthu University of Technology">Mangosuthu University of Technology</option>
                            <option value="Walter Sisulu University">Walter Sisulu University</option>
                            <option value="Sefako Makgatho Health Sciences University">Sefako Makgatho Health Sciences University</option>
                            <option value="Sol Plaatje University">Sol Plaatje University</option>
                            <option value="University of Mpumalanga">University of Mpumalanga</option>
                            <option value="Rosebank College">Rosebank College</option>
                            <option value="Emeris">Emeris</option>
                            <option value="Boston City Campus">Boston City Campus</option>
                            <option value="IIE Vega">IIE Vega</option>
                            <option value="Other">Other</option>

                        </select>
                    </div>

                    <input type="text"
                           name="institution_custom"
                           id="customInstitution"
                           placeholder="Enter your institution name"
                           style="display:none; margin-top:12px;">
                </div>
            </div>

            <!-- Password -->
            <div class="form-group full-width">
                <label>Password</label>
                <input type="password" name="password"
                       placeholder="Create a strong password" required>
                <p class="field-hint">Minimum 8 characters recommended.</p>
            </div>

            <button type="submit" name="register" class="submit-btn">
                Create account
            </button>

        </form>

        <div class="divider">or</div>

        <div class="login-row">
            Already have an account? <a href="login.php">Sign in</a>
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
                Passwords hashed
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

<!-- Footer -->
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

<script>
    // Known domains per institution — mirrors the PHP array for live hint updating
    const institutionDomains = {
        'Eduvos': ['vossie.net'],
        'University of Pretoria': ['tuks.co.za', 'up.ac.za'],
        'University of Johannesburg': ['student.uj.ac.za'],
        'University of the Witwatersrand': ['students.wits.ac.za'],
        'Tshwane University of Technology': ['tut.ac.za', 'tut4life.ac.za'],
        'University of Cape Town': ['myuct.ac.za', 'uct.ac.za'],
        'Stellenbosch University': ['sun.ac.za', 'stud.sun.ac.za'],
        'University of KwaZulu-Natal': ['stu.ukzn.ac.za'],
        'North-West University': ['mynwu.ac.za'],
        'University of South Africa': ['mylife.unisa.ac.za'],
        'University of the Western Cape': ['uwc.ac.za', 'students.uwc.ac.za'],
        'Nelson Mandela University': ['mandela.ac.za', 'student.mandela.ac.za'],
        'University of the Free State': ['ufs.ac.za', 'student.ufs.ac.za'],
        'University of Limpopo': ['ul.ac.za', 'student.ul.ac.za'],
        'University of Venda': ['univen.ac.za'],
        'Rhodes University': ['ru.ac.za', 'students.ru.ac.za'],
        'Durban University of Technology': ['dut.ac.za', 'dut4life.ac.za'],
        'Cape Peninsula University of Technology': ['cput.ac.za', 'student.cput.ac.za'],
        'Central University of Technology': ['cut.ac.za', 'student.cut.ac.za'],
        'Vaal University of Technology': ['vut.ac.za', 'student.vut.ac.za'],
        'Mangosuthu University of Technology': ['mut.ac.za', 'student.mut.ac.za'],
        'Walter Sisulu University': ['wsu.ac.za', 'student.wsu.ac.za'],
        'Sefako Makgatho Health Sciences University': ['smu.ac.za', 'student.smu.ac.za'],
        'Sol Plaatje University': ['spu.ac.za', 'student.spu.ac.za'],
        'University of Mpumalanga': ['ump.ac.za', 'student.ump.ac.za'],
        'Rosebank College': ['rcconnect.edu.za', 'rrconnect.edu.za'],
        'Emeris': ['myemeris.edu.za'],
        'Boston City Campus': ['boston.co.za'],
        'IIE Vega': ['vegaschool.com', 'iie.ac.za'],
    };

    function toggleInstitutionInput() {
        const select      = document.getElementById("institutionSelect");
        const customInput = document.getElementById("customInstitution");
        const hint        = document.getElementById("emailHint");

        if (select.value === "Other") {
            customInput.style.display = "block";
            customInput.required      = true;
            hint.textContent = "Use your official student email (e.g. you@institution.ac.za).";
        } else {
            customInput.style.display = "none";
            customInput.required      = false;
            customInput.value         = "";

            // Update hint to show expected domain for selected institution
            const domains = institutionDomains[select.value];
            if (domains) {
                hint.textContent = "Use your " + select.options[select.selectedIndex].text + " email: " + domains.map(d => "@" + d).join(" or ");
            } else {
                hint.textContent = "Must match your institution's student email domain.";
            }
        }
    }
</script>
</body>
</html>