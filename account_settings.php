<?php
include 'auth.php';
include 'db.php';

requireLogin();
verifyCsrf();

$user_id = $_SESSION['user_id'];
$message = "";

$user_sql = "SELECT * FROM users WHERE user_id = '$user_id'";
$user_result = mysqli_query($conn, $user_sql);
$user = mysqli_fetch_assoc($user_result);

if (isset($_POST['save_changes'])) {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);

    if ($_POST['institution_select'] == "Other") {
        $institution = mysqli_real_escape_string($conn, $_POST['institution_custom']);
    } else {
        $institution = mysqli_real_escape_string($conn, $_POST['institution_select']);
    }
    $bio = mysqli_real_escape_string($conn, $_POST['bio']);

    $update_sql = "UPDATE users 
                   SET full_name = '$full_name',
                       institution = '$institution',
                       bio = '$bio'
                   WHERE user_id = '$user_id'";

    if (mysqli_query($conn, $update_sql)) {
        $message = "Profile updated successfully!";
		$_SESSION['full_name'] = $full_name;
        $user_sql = "SELECT * FROM users WHERE user_id = '$user_id'";
        $user_result = mysqli_query($conn, $user_sql);
        $user = mysqli_fetch_assoc($user_result);
    } else {
        $message = "Error updating profile.";
    }
}
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password     = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Fetch the stored hash
    $stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row    = mysqli_fetch_assoc($result);

    if (!password_verify($current_password, $row['password'])) {
        $message = "Current password is incorrect.";

    } elseif (strlen($new_password) < 8) {
        $message = "New password must be at least 8 characters.";

    } elseif ($new_password !== $confirm_password) {
        $message = "New passwords do not match.";

    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt   = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, "si", $hashed, $user_id);
        mysqli_stmt_execute($stmt);
        $message = "Password changed successfully.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CampusLink | Account Settings</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f8f1e8;
            margin: 0;
            color: #111827;
        }

        a {
            text-decoration: none;
        }

			.navbar {
		    width: 100%;
		    max-width: 1200px;
		}
			
        nav {
            background: #fffaf3;
            border-bottom: 1px solid #eadfd2;
            padding: 0 12%;
            height: 72px;
            display: flex;
            align-items: center;
            justify-content: space-between;
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

        .nav-center {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-center a {
            color: #374151;
            font-size: 15px;
            font-weight: 500;
            padding: 10px 18px;
            border-radius: 14px;
        }

        .nav-center a:hover,
        .nav-center a.active {
            background: #fff0e5;
            color: orange;
            font-weight: 700;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sell-btn {
            background: orange;
            color: white;
            padding: 10px 20px;
            border-radius: 12px;
            font-weight: 700;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        }

		.cart-link {
		    background: white;
		    color: #374151;
		    padding: 10px 20px;
		    border-radius: 12px;
		    font-weight: 700;
		    border: 1px solid #eadfd2;
		    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
		    transition: all 0.15s ease;
		}
		
		.cart-link:hover {
		    background: #fff0e5;
		    color: orange;
		}
/*  Dropdown  */
        .user-menu-wrap {
            position: relative;
        }

        .user-icon-btn {
            width: 40px;
            height: 40px;
            background: white;
            border: 1px solid #eadfd2;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
            cursor: pointer;
            color: #374151;
            transition: background 0.15s;
        }

        .user-icon-btn:hover {
            background: #fff0e5;
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            background: #fff;
            border: 1px solid #eadfd2;
            border-radius: 16px;
            box-shadow: 0 8px 28px rgba(0,0,0,0.12);
            min-width: 210px;
            overflow: hidden;
            z-index: 500;
        }

        .dropdown-menu.open {
            display: block;
        }

        .dropdown-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 13px 18px;
            font-size: 14px;
            font-weight: 500;
            color: #111827;
            transition: background 0.12s;
        }

        .dropdown-menu a:hover {
            background: #fff8f0;
        }

        .dropdown-menu a svg {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
            color: #9ca3af;
        }

        .dropdown-menu .divider {
            border: none;
            border-top: 1px solid #f3ede4;
            margin: 4px 0;
        }

        .dropdown-menu .sign-out       { color: #dc2626; }
        .dropdown-menu .sign-out svg   { color: #dc2626; }
   

        .page {
            max-width: 720px;
            margin: auto;
            padding: 45px 20px 80px;
        }

        .settings-card {
            background: white;
            border: 1px solid #eadfd2;
            border-radius: 24px;
            padding: 38px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.06);
        }

        .settings-card h1 {
            font-size: 32px;
            margin: 0;
            color: #020617;
        }

        .subtitle {
            color: #64748b;
            margin-top: 8px;
            margin-bottom: 30px;
            font-size: 16px;
        }

        .verified-badge {
            background: #d9fbf5;
            color: #0891b2;
            font-size: 13px;
            padding: 6px 10px;
            border-radius: 20px;
            margin-left: 8px;
            vertical-align: middle;
        }

        label {
            display: block;
            font-weight: 700;
            margin-bottom: 8px;
            margin-top: 18px;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 14px;
            border: 1px solid #ddd6ce;
            border-radius: 14px;
            font-size: 15px;
            outline: none;
            background: white;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: orange;
        }

        textarea {
            height: 125px;
            resize: vertical;
        }

        .save-btn {
            width: 100%;
            background: orange;
            color: white;
            border: none;
            border-radius: 14px;
            padding: 15px;
            font-size: 16px;
            font-weight: 700;
            margin-top: 22px;
            cursor: pointer;
        }

        .email-box {
            margin-top: 25px;
            background: #f3eee7;
            color: #64748b;
            padding: 16px;
            border-radius: 14px;
            font-size: 14px;
        }

        .message {
            background: #fff0e5;
            color: orange;
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: 700;
        }

        footer {
            padding: 25px 12%;
            background: #fffaf3;
            border-top: 1px solid #eadfd2;
            color: #6b7280;
            display: flex;
            justify-content: space-between;
            font-size: 14px;
        }

        .footer-brand {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .footer-logo {
            width: 30px;
            height: 30px;
            background: orange;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
	/* Mobile responsiveness */
.mobile-only {
    display: none;
}

@media (max-width: 768px) {
    nav {
        height: 64px;
        padding: 0 16px;
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
    }

    .nav-logo-icon {
        width: 36px;
        height: 36px;
    }

    .nav-logo-text strong {
        font-size: 15px;
    }

    .nav-logo-text span {
        display: none;
    }

    .nav-center {
        display: none;
    }

    .cart-link {
        display: none;
    }

    .mobile-only {
        display: flex;
    }

    .nav-right {
        gap: 8px;
    }

    .sell-btn {
        padding: 8px 12px;
        font-size: 13px;
        border-radius: 10px;
    }

    .user-icon-btn {
        width: 36px;
        height: 36px;
    }

    .page {
        padding: 25px 14px 60px;
    }

    footer {
        flex-direction: column;
        gap: 10px;
        text-align: center;
        padding: 20px;
    }
}
    </style>
</head>

<body>

<!--  Nav  -->
<nav>
    <a href="dashboard.php" class="nav-logo">
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

    <div class="nav-center">
        <a href="products.php">Browse</a>
        <a href="my_listings.php">My listings</a>
        <a href="inbox.php">Messages</a>
        <a href="wishlist.php">Wishlist</a>
    </div>

        <div class="nav-right">	
			<a href="cart.php" class="cart-link"> Cart </a>
            <a href="add_product.php" class="sell-btn">+ Sell</a>
			
			<!-- Bell icon -->
        <div class="bell-wrap" style="position:relative;display:inline-flex;align-items:center;">
            <a href="notifications.php" style="background:none;border:none;cursor:pointer;padding:6px;display:flex;align-items:center;color:#374151;border-radius:10px;text-decoration:none;" title="Notifications">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
            </a>
            <?php if ($unread_count > 0): ?>
            <span style="position:absolute;top:0;right:0;background:orange;color:white;font-size:10px;font-weight:700;border-radius:999px;min-width:17px;height:17px;display:flex;align-items:center;justify-content:center;padding:0 4px;">
                <?php echo $unread_count; ?>
            </span>
            <?php endif; ?>
        </div>

        <div class="user-menu-wrap">
    <button class="user-icon-btn" onclick="toggleDropdown()" type="button">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="1.8"
             stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
            <circle cx="12" cy="7" r="4"/>
        </svg>
    </button>

    <div class="dropdown-menu" id="dropdownMenu">
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <a href="admin_dashboard.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"/>
                    </svg>
                    Admin Dashboard
                </a>
                <div style="height:1px;background:#eadfd2;margin:6px 0;"></div>
                <?php endif; ?>

					<a href="products.php" class="mobile-only">
			    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
			         stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
			        <circle cx="11" cy="11" r="8"></circle>
			        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
			    </svg>
			    Browse 
			</a>

        <a href="profile.php">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
            </svg>
            My profile
        </a>

        <a href="account_settings.php">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="3"/>
                <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 01-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V22a2 2 0 01-4 0v-.09a1.65 1.65 0 00-1-1.51 1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.6 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.6a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9c.38.23.6.64.6 1.09V10a2 2 0 010 4h-.09a1.65 1.65 0 00-.51 1z"/>
            </svg>
            Account settings
        </a>

        <a href="my_listings.php">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/>
                <rect x="9" y="3" width="6" height="4" rx="1"/>
            </svg>
            My listings
        </a>

        <a href="inbox.php">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>
            </svg>
            Messages
        </a>

			<a href="cart.php" class="mobile-only">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="9" cy="20" r="1"></circle>
        <circle cx="18" cy="20" r="1"></circle>
        <path d="M3 4h2l2.4 10.4a1 1 0 0 0 1 .8h8.9a1 1 0 0 0 1-.8L21 7H7"></path>
    </svg>
    Cart
</a>

		<a href="order_history.php">
			    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
			         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
			        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
			        <polyline points="14 2 14 8 20 8"/>
			        <line x1="16" y1="13" x2="8" y2="13"/>
			        <line x1="16" y1="17" x2="8" y2="17"/>
			        <polyline points="10 9 9 9 8 9"/>
			    </svg>
			    Order history
			</a>
        <hr>
		
        <a href="logout.php" class="sign-out">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
            Sign out
        </a>

            </div>
        </div>
    </div>
</nav>

<div class="page">

    <div class="settings-card">

        <?php if ($message != "") { ?>
            <div class="message"><?php echo $message; ?></div>
        <?php } ?>

        <h1>
            Account settings
            <span class="verified-badge">✓ Verified</span>
        </h1>

        <p class="subtitle">Update your public profile details.</p>

        <form method="POST">
        <?php csrfInput(); ?>

            <label>Display name</label>
            <input type="text"
                   name="full_name"
                   value="<?php echo $user['full_name']; ?>"
                   required>

        <label>Institution</label>

        <select name="institution_select"
            id="institutionSelect"
            onchange="toggleInstitutionInput()">

                <option value="Eduvos" <?php if ($user['institution'] == 'Eduvos') echo 'selected'; ?>>Eduvos</option>
                <option value="University of Pretoria" <?php if ($user['institution'] == 'University of Pretoria') echo 'selected'; ?>>University of Pretoria</option>
                <option value="University of Johannesburg" <?php if ($user['institution'] == 'University of Johannesburg') echo 'selected'; ?>>University of Johannesburg</option>
                <option value="University of the Witwatersrand" <?php if ($user['institution'] == 'University of the Witwatersrand') echo 'selected'; ?>>Wits University</option>
                <option value="Tshwane University of Technology" <?php if ($user['institution'] == 'Tshwane University of Technology') echo 'selected'; ?>>Tshwane University of Technology</option>
                <option value="University of Cape Town" <?php if ($user['institution'] == 'University of Cape Town') echo 'selected'; ?>>University of Cape Town</option>
                <option value="Stellenbosch University" <?php if ($user['institution'] == 'Stellenbosch University') echo 'selected'; ?>>Stellenbosch University</option>
                <option value="University of KwaZulu-Natal" <?php if ($user['institution'] == 'University of KwaZulu-Natal') echo 'selected'; ?>>University of KwaZulu-Natal</option>
                <option value="North-West University" <?php if ($user['institution'] == 'North-West University') echo 'selected'; ?>>North-West University</option>
                <option value="University of South Africa" <?php if ($user['institution'] == 'University of South Africa') echo 'selected'; ?>>UNISA</option>
                <option value="University of the Western Cape" <?php if ($user['institution'] == 'University of the Western Cape') echo 'selected'; ?>>University of the Western Cape</option>
                <option value="Nelson Mandela University" <?php if ($user['institution'] == 'Nelson Mandela University') echo 'selected'; ?>>Nelson Mandela University</option>
                <option value="University of the Free State" <?php if ($user['institution'] == 'University of the Free State') echo 'selected'; ?>>University of the Free State</option>
                <option value="University of Limpopo" <?php if ($user['institution'] == 'University of Limpopo') echo 'selected'; ?>>University of Limpopo</option>
                <option value="University of Venda" <?php if ($user['institution'] == 'University of Venda') echo 'selected'; ?>>University of Venda</option>
                <option value="Rhodes University" <?php if ($user['institution'] == 'Rhodes University') echo 'selected'; ?>>Rhodes University</option>
                <option value="Durban University of Technology" <?php if ($user['institution'] == 'Durban University of Technology') echo 'selected'; ?>>Durban University of Technology</option>
                <option value="Cape Peninsula University of Technology" <?php if ($user['institution'] == 'Cape Peninsula University of Technology') echo 'selected'; ?>>Cape Peninsula University of Technology</option>
                <option value="Central University of Technology" <?php if ($user['institution'] == 'Central University of Technology') echo 'selected'; ?>>Central University of Technology</option>
                <option value="Vaal University of Technology" <?php if ($user['institution'] == 'Vaal University of Technology') echo 'selected'; ?>>Vaal University of Technology</option>
                <option value="Mangosuthu University of Technology" <?php if ($user['institution'] == 'Mangosuthu University of Technology') echo 'selected'; ?>>Mangosuthu University of Technology</option>
                <option value="Walter Sisulu University" <?php if ($user['institution'] == 'Walter Sisulu University') echo 'selected'; ?>>Walter Sisulu University</option>
                <option value="Sefako Makgatho Health Sciences University" <?php if ($user['institution'] == 'Sefako Makgatho Health Sciences University') echo 'selected'; ?>>Sefako Makgatho Health Sciences University</option>
                <option value="Sol Plaatje University" <?php if ($user['institution'] == 'Sol Plaatje University') echo 'selected'; ?>>Sol Plaatje University</option>
                <option value="University of Mpumalanga" <?php if ($user['institution'] == 'University of Mpumalanga') echo 'selected'; ?>>University of Mpumalanga</option>
                <option value="Rosebank College" <?php if ($user['institution'] == 'Rosebank College') echo 'selected'; ?>>Rosebank College</option>
                <option value="Varsity College" <?php if ($user['institution'] == 'Varsity College') echo 'selected'; ?>>Varsity College</option>
                <option value="Boston City Campus" <?php if ($user['institution'] == 'Boston City Campus') echo 'selected'; ?>>Boston City Campus</option>
                <option value="IIE Vega" <?php if ($user['institution'] == 'IIE Vega') echo 'selected'; ?>>IIE Vega</option>
                <option value="Other">Other</option>
        </select>

        <input type="text"
            name="institution_custom"
            id="customInstitution"
            placeholder="Enter your institution name"
            value="<?php echo $user['institution']; ?>"
            style="display:none; margin-top:12px;">

            
            <label>Bio</label>
            <textarea name="bio" placeholder="Tell other students a bit about yourself."><?php echo isset($user['bio']) ? $user['bio'] : ''; ?></textarea>

            <button class="save-btn" type="submit" name="save_changes">
                Save changes
            </button>
		</form>
                <form method="POST">
                    <?php csrfInput(); ?>
                    <h3>Change Password</h3>

                    <input type="password" name="current_password" placeholder="Current password" required>
                    <input type="password" name="new_password" placeholder="New password" required>
                    <input type="password" name="confirm_password" placeholder="Confirm new password" required>

		            <button type="submit" name="change_password" style="background: orange; color: white; padding: 14px 24px; border-radius: 14px; border: none; font-weight: 700;
						cursor: pointer; font-size: 15px;">
		   					 Update Password
					</button>
                </form>

        <div class="email-box">
            Email: <?php echo $user['student_email']; ?>
        </div>

    </div>

</div>

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
function toggleDropdown() {
    document.getElementById("dropdownMenu").classList.toggle("open");
}

window.onclick = function(event) {
    if (!event.target.closest(".user-menu-wrap")) {
        const menu = document.getElementById("dropdownMenu");
        if (menu) {
            menu.classList.remove("open");
        }
    }
}
</script>
    
<script>    
        
function toggleProfileMenu() {
    document.getElementById("profileMenu").classList.toggle("show");
}

function toggleInstitutionInput() {
    const select = document.getElementById("institutionSelect");
    const customInput = document.getElementById("customInstitution");

    if (select.value === "Other") {
        customInput.style.display = "block";
        customInput.required = true;
    } else {
        customInput.style.display = "none";
        customInput.required = false;
        customInput.value = "";
    }
}

window.onclick = function(event) {
    if (!event.target.closest(".profile-dropdown")) {
        var menu = document.getElementById("profileMenu");
        if (menu) {
            menu.classList.remove("show");
        }
    }
}

window.onload = function() {
    const savedInstitution = "<?php echo $user['institution']; ?>";
    const select = document.getElementById("institutionSelect");
    const customInput = document.getElementById("customInstitution");

    let found = false;

    for (let i = 0; i < select.options.length; i++) {
        if (select.options[i].value === savedInstitution) {
            found = true;
            break;
        }
    }

    if (!found && savedInstitution !== "") {
        select.value = "Other";
        customInput.style.display = "block";
        customInput.value = savedInstitution;
        customInput.required = true;
    } else {
        toggleInstitutionInput();
    }
}
</script>

</body>
</html>