<?php
include 'auth.php';
include 'db.php';

requireLogin();
requireVerified();

$user_id = $_SESSION['user_id'];

// Fetch cart items
$sql = "SELECT products.*, cart.cart_id, cart.quantity, products.delivery_options
        FROM cart
        JOIN products ON cart.product_id = products.product_id
        WHERE cart.user_id = '$user_id'";

$result = mysqli_query($conn, $sql);

$rows  = [];
$total = 0;

$all_delivery = [];
while ($row = mysqli_fetch_assoc($result)) {
    $row['subtotal'] = $row['price'] * $row['quantity'];
    $total += $row['subtotal'];
    $rows[] = $row;

    // Collect delivery options
    if (!empty($row['delivery_options'])) {
        foreach (explode(',', $row['delivery_options']) as $opt) {
            $all_delivery[$opt] = true;
        }
    }
}

// Redirect back if cart is empty
if (empty($rows)) {
    header("Location: cart.php");
    exit();
}

// Pre-fill user details
$user_result = mysqli_query($conn, "SELECT * FROM users WHERE user_id = '$user_id'");
$user        = mysqli_fetch_assoc($user_result);
?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CampusLink | Checkout</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * { box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: #f8f1e8;
            margin: 0;
            color: #111827;
        }

        a { text-decoration: none; }

        /*  Nav  */
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

        .nav-center a:hover {
            background: #fff0e5;
            color: orange;
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
            box-shadow: 0 2px 6px rgba(0,0,0,0.12);
        }

        /*  Dropdown  */
        .user-menu-wrap { position: relative; }

        .user-icon-btn {
            width: 42px;
            height: 42px;
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

        .user-icon-btn:hover { background: #fff0e5; }

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

        .dropdown-menu.open { display: block; }

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

        .dropdown-menu a:hover { background: #fff8f0; }

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

        .dropdown-menu .sign-out     { color: #dc2626; }
        .dropdown-menu .sign-out svg { color: #dc2626; }

        /*  Page  */
        .page {
            max-width: 1100px;
            margin: auto;
            padding: 45px 20px 80px;
        }

        .back-link {
            color: orange;
            font-weight: 700;
            margin-bottom: 22px;
            display: inline-block;
            font-size: 15px;
        }

        .back-link:hover { opacity: 0.8; }

        .page-header {
            margin-bottom: 32px;
        }

        .page-header h1 {
            font-size: 38px;
            margin: 0 0 6px;
        }

        .page-header p {
            color: #64748b;
            font-size: 16px;
            margin: 0;
        }

        /*  Progress steps  */
        .progress {
            display: flex;
            align-items: center;
            gap: 0;
            margin-bottom: 36px;
        }

        .step {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 600;
            color: #9ca3af;
        }

        .step.active { color: #111827; }
        .step.done   { color: orange; }

        .step-circle {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #eadfd2;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 800;
            color: #9ca3af;
            flex-shrink: 0;
        }

        .step.active .step-circle {
            background: orange;
            color: white;
        }

        .step.done .step-circle {
            background: #fff0e5;
            color: orange;
        }

        .step-line {
            flex: 1;
            height: 2px;
            background: #eadfd2;
            margin: 0 14px;
        }

        /*  Layout  */
        .checkout-layout {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 28px;
            align-items: start;
        }

        /*  Cards  */
        .card {
            background: white;
            border: 1px solid #eadfd2;
            border-radius: 22px;
            padding: 28px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.05);
            margin-bottom: 22px;
        }

        .card h2 {
            font-size: 20px;
            margin: 0 0 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card h2 svg {
            color: orange;
        }

        /*  Form  */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: #374151;
            margin-bottom: 7px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 13px 16px;
            border: 1px solid #ddd6ce;
            border-radius: 12px;
            font-size: 15px;
            color: #111827;
            background: white;
            outline: none;
            transition: border-color 0.15s;
            font-family: Arial, sans-serif;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: orange;
            box-shadow: 0 0 0 3px rgba(255,165,0,0.12);
        }

        .form-group textarea {
            height: 90px;
            resize: vertical;
        }

        /*  Delivery options  */
        .delivery-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-bottom: 20px;
        }

        .delivery-option {
            border: 2px solid #eadfd2;
            border-radius: 16px;
            padding: 18px;
            cursor: pointer;
            transition: border-color 0.15s, background 0.15s;
            position: relative;
        }

        .delivery-option:hover {
            border-color: orange;
            background: #fffaf3;
        }

        .delivery-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }

        .delivery-option.selected {
            border-color: orange;
            background: #fff8f0;
        }

        .delivery-option-icon {
            width: 40px;
            height: 40px;
            background: #fff0e5;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
            color: orange;
        }

        .delivery-option strong {
            display: block;
            font-size: 15px;
            margin-bottom: 4px;
        }

        .delivery-option span {
            font-size: 13px;
            color: #64748b;
        }

        /*  Payment options  */
        .payment-options {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 8px;
        }

        .payment-option {
            border: 2px solid #eadfd2;
            border-radius: 16px;
            padding: 16px 18px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 14px;
            transition: border-color 0.15s, background 0.15s;
            position: relative;
        }

        .payment-option:hover {
            border-color: orange;
            background: #fffaf3;
        }

        .payment-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }

        .payment-option.selected {
            border-color: orange;
            background: #fff8f0;
        }

        .payment-icon {
            width: 40px;
            height: 40px;
            background: #fff0e5;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: orange;
            flex-shrink: 0;
        }

        .payment-option strong {
            display: block;
            font-size: 15px;
            margin-bottom: 2px;
        }

        .payment-option span {
            font-size: 13px;
            color: #64748b;
        }

        .payment-radio {
            margin-left: auto;
            width: 20px;
            height: 20px;
            border: 2px solid #eadfd2;
            border-radius: 50%;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: border-color 0.15s;
        }

        .payment-option.selected .payment-radio {
            border-color: orange;
            background: orange;
        }

        .payment-option.selected .payment-radio::after {
            content: '';
            width: 8px;
            height: 8px;
            background: white;
            border-radius: 50%;
            display: block;
        }

        /*  Summary panel  */
        .summary-card {
            background: white;
            border: 1px solid #eadfd2;
            border-radius: 22px;
            padding: 28px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.06);
            position: sticky;
            top: 24px;
        }

        .summary-card h2 {
            font-size: 20px;
            margin: 0 0 22px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 14px;
            font-size: 14px;
        }

        .summary-item-name {
            color: #374151;
            flex: 1;
            line-height: 1.4;
        }

        .summary-item-qty {
            color: #9ca3af;
            font-size: 12px;
            margin-top: 2px;
        }

        .summary-item-price {
            font-weight: 700;
            color: #111827;
            white-space: nowrap;
        }

        .summary-divider {
            border: none;
            border-top: 1px solid #eadfd2;
            margin: 16px 0;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            color: #64748b;
            margin-bottom: 10px;
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            font-size: 20px;
            font-weight: 800;
            color: #111827;
            margin-top: 14px;
        }

        .summary-total span:last-child {
            color: orange;
        }

        .place-order-btn {
            display: block;
            width: 100%;
            background: orange;
            color: white;
            padding: 15px;
            border-radius: 14px;
            font-weight: 800;
            font-size: 16px;
            text-align: center;
            margin-top: 22px;
            border: none;
            cursor: pointer;
            box-shadow: 0 3px 8px rgba(0,0,0,0.14);
            transition: opacity 0.15s;
            font-family: Arial, sans-serif;
        }

        .place-order-btn:hover { opacity: 0.9; }

        .secure-note {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            margin-top: 14px;
            font-size: 13px;
            color: #9ca3af;
        }

        .secure-note svg {
            color: #9ca3af;
        }

        /* Footer  */
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
            font-size: 11px;
        }

       @media (max-width: 900px) {
		    nav {
		        height: 64px;
		        padding: 0 16px;
		        flex-direction: row;
		    }
		
		    .nav-center {
		        display: none;
		    }
		
		    .nav-logo-text span {
		        display: none;
		    }
		
		    .sell-btn {
		        padding: 8px 12px;
		        font-size: 13px;
		    }
		
		    .page {
		        padding: 25px 14px 60px;
		    }
		
		    .page-header h1 {
		        font-size: 28px;
		    }
		
		    .progress {
		        overflow-x: auto;
		        gap: 4px;
		    }
		
		    .step {
		        font-size: 12px;
		        gap: 6px;
		    }
		
		    .step-line {
		        min-width: 20px;
		        margin: 0 6px;
		    }
		
		    .checkout-layout {
		        grid-template-columns: 1fr;
		    }
		
		    .form-row {
		        grid-template-columns: 1fr;
		    }
		
		    .delivery-options {
		        grid-template-columns: 1fr;
		    }
		
		    .summary-card {
		        position: static;
		    }
		
		    .card {
		        padding: 20px 16px;
		    }
		
		    footer {
		        flex-direction: column;
		        gap: 10px;
		        text-align: center;
		        align-items: center;
		        padding: 20px;
		    }
		}
    </style>
</head>

<body>

<!-- Nav  -->
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
            <button class="user-icon-btn" onclick="toggleDropdown()">
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



				<a href="products.php">
				    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
				         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
				        <circle cx="11" cy="11" r="8"/>
				        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
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
                        <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>
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


				<a href="wishlist.php">
				    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
				         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
				        <path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/>
				    </svg>
				    Wishlist
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
                <hr class="divider">

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

<!--  Page  -->
<div class="page">

    <a href="cart.php" class="back-link">← Back to cart</a>

    <div class="page-header">
        <h1>Checkout</h1>
        <p>Fill in your details to complete your order.</p>
    </div>

    <!-- Progress -->
    <div class="progress">
        <div class="step done">
            <div class="step-circle">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
            </div>
            Cart
        </div>
        <div class="step-line"></div>
        <div class="step active">
            <div class="step-circle">2</div>
            Checkout
        </div>
        <div class="step-line"></div>
        <div class="step">
            <div class="step-circle">3</div>
            Confirmation
        </div>
    </div>

    <form method="POST" action="place_order.php">
    <?php csrfInput(); ?>

        <div class="checkout-layout">

            <!-- Left column -->
            <div>

                <!-- Contact details -->
                <div class="card">
                    <h2>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        Contact details
                    </h2>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Full name</label>
                            <input type="text" name="full_name"
                                   value="<?php echo $user['full_name']; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email address</label>
                            <input type="email" name="email"
                                   value="<?php echo $user['student_email']; ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Phone number</label>
                        <input type="tel" name="phone"
                               placeholder="e.g. 071 234 5678" required>
                    </div>
                </div>

                <!-- Delivery method -->
                <div class="card">
                    <h2>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="1" y="3" width="15" height="13" rx="1"/>
                            <path d="M16 8h4l3 3v5h-7V8z"/>
                            <circle cx="5.5" cy="18.5" r="2.5"/>
                            <circle cx="18.5" cy="18.5" r="2.5"/>
                        </svg>
                        Delivery method
                    </h2>

                    <div class="delivery-options">
                        <?php
                        $delivery_labels = [
                            'paxi'     => ['label' => 'PAXI collection point',  'desc' => 'Drop & collect at your nearest PAXI point'],
                            'courier'  => ['label' => 'Door-to-door courier',   'desc' => 'Arranged with the seller'],
                            'campus'   => ['label' => 'On-campus pickup',       'desc' => 'Meet the seller on campus — free'],
                            'inperson' => ['label' => 'In-person',              'desc' => 'Meet the seller directly'],
                            'online'   => ['label' => 'Online / remote',        'desc' => 'Delivered digitally'],
                        ];

                        $first = true;
                        foreach ($all_delivery as $opt => $v):
                            if (!isset($delivery_labels[$opt])) continue;
                            $info = $delivery_labels[$opt];
                        ?>
                            <label class="delivery-option <?php echo $first ? 'selected' : ''; ?>"
                                id="opt-<?php echo $opt; ?>">
                                <input type="radio" name="delivery_method"
                                    value="<?php echo $opt; ?>"
                                    <?php echo $first ? 'checked' : ''; ?>
                                    onchange="selectDelivery('<?php echo $opt; ?>')">
                                <div class="delivery-option-icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="1" y="3" width="15" height="13" rx="1"/>
                                        <path d="M16 8h4l3 3v5h-7V8z"/>
                                        <circle cx="5.5" cy="18.5" r="2.5"/>
                                        <circle cx="18.5" cy="18.5" r="2.5"/>
                                    </svg>
                                </div>
                                <strong><?php echo $info['label']; ?></strong>
                                <span><?php echo $info['desc']; ?></span>
                            </label>
                        <?php
                            $first = false;
                        endforeach;
                        ?>
                    </div>

                    <!-- Address shown only when delivery is selected -->
                    <div id="address-block" style="display:none;">
                        <div class="form-group">
                            <label>Delivery address</label>
                            <input type="text" name="address"
                                   placeholder="Street address, suburb, city">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>City</label>
                                <input type="text" name="city" placeholder="e.g. Pretoria">
                            </div>
                            <div class="form-group">
                                <label>Postal code</label>
                                <input type="text" name="postal_code" placeholder="e.g. 0001">
                            </div>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom:0;">
                        <label>Collection / delivery note (optional)</label>
                        <textarea name="delivery_note"
                                  placeholder="e.g. Available after 14:00, Gate 2 entrance..."></textarea>
                    </div>
                </div>

                <!-- Payment method -->
                <div class="card">
                    <h2>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="1" y="4" width="22" height="16" rx="2"/>
                            <line x1="1" y1="10" x2="23" y2="10"/>
                        </svg>
                        Payment method
                    </h2>

                    <div class="payment-options">

                        <label class="payment-option selected" id="pay-cash"
                               onclick="selectPayment('cash')">
                            <input type="radio" name="payment_method" value="cash" checked>
                            <div class="payment-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="2" y="6" width="20" height="12" rx="2"/>
                                    <circle cx="12" cy="12" r="2"/>
                                    <path d="M6 12h.01M18 12h.01"/>
                                </svg>
                            </div>
                            <div>
                                <strong>Cash on collection</strong>
                                <span>Pay the seller in person when you collect</span>
                            </div>
                            <div class="payment-radio"></div>
                        </label>

                        <label class="payment-option" id="pay-eft"
                               onclick="selectPayment('eft')">
                            <input type="radio" name="payment_method" value="eft">
                            <div class="payment-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                                    <rect x="9" y="12" width="6" height="9"/>
                                </svg>
                            </div>
                            <div>
                                <strong>EFT / Bank transfer</strong>
                                <span>Transfer directly to the seller's account</span>
                            </div>
                            <div class="payment-radio"></div>
                        </label>

                        <label class="payment-option" id="pay-snapscan"
                               onclick="selectPayment('snapscan')">
                            <input type="radio" name="payment_method" value="snapscan">
                            <div class="payment-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="3" width="7" height="7" rx="1"/>
                                    <rect x="14" y="3" width="7" height="7" rx="1"/>
                                    <rect x="3" y="14" width="7" height="7" rx="1"/>
                                    <path d="M14 14h3v3M17 17h3v3M14 20h3"/>
                                </svg>
                            </div>
                            <div>
                                <strong>SnapScan / QR pay</strong>
                                <span>Scan the seller's QR code to pay instantly</span>
                            </div>
                            <div class="payment-radio"></div>
                        </label>

                    </div>
                </div>

            </div>

            <!-- Right column — summary -->
            <div class="summary-card">
                <h2>Order summary</h2>

                <?php foreach ($rows as $row) { ?>
                    <div class="summary-item">
                        <div>
                            <div class="summary-item-name"><?php echo $row['title']; ?></div>
                            <div class="summary-item-qty">Qty: <?php echo $row['quantity']; ?></div>
                        </div>
                        <div class="summary-item-price">
                            R<?php echo number_format($row['subtotal'], 2); ?>
                        </div>
                    </div>
                <?php } ?>

                <hr class="summary-divider">

                <div class="summary-row">
                    <span>Subtotal</span>
                    <span>R<?php echo number_format($total, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Delivery</span>
                    <span>Arranged with seller</span>
                </div>

                <div class="summary-total">
                    <span>Total</span>
                    <span>R<?php echo number_format($total, 2); ?></span>
                </div>

                <button type="submit" class="place-order-btn">Place order</button>

                <div class="secure-note">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2"/>
                        <path d="M7 11V7a5 5 0 0110 0v4"/>
                    </svg>
                    Student-verified marketplace
                </div>
            </div>

        </div>

    </form>

</div>

<!-- ── Footer ── -->
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
    // Dropdown
    function toggleDropdown() {
        document.getElementById('dropdownMenu').classList.toggle('open');
    }

    document.addEventListener('click', function(e) {
        var wrap = document.querySelector('.user-menu-wrap');
        if (wrap && !wrap.contains(e.target)) {
            document.getElementById('dropdownMenu').classList.remove('open');
        }
    });

   // Delivery method toggle
	function selectDelivery(type) {
	    document.querySelectorAll('.delivery-option').forEach(function(el) {
	        el.classList.remove('selected');
	    });
	    document.getElementById('opt-' + type).classList.add('selected');
	
	    // Show address block only for options that need an address
	    var addressTypes = ['courier', 'paxi'];
	    var needsAddress = addressTypes.includes(type);
	    document.getElementById('address-block').style.display = needsAddress ? 'block' : 'none';
	
	    // Make address fields required when visible, optional otherwise
	    var addressFields = document.querySelectorAll('#address-block input');
	    addressFields.forEach(function(field) {
	        if (needsAddress) {
	            field.setAttribute('required', 'required');
	        } else {
	            field.removeAttribute('required');
	        }
	    });
	}

// Run on page load so the first selected option sets the required correctly
	window.addEventListener('DOMContentLoaded', function() {
	    var checked = document.querySelector('input[name="delivery_method"]:checked');
	    if (checked) selectDelivery(checked.value);
	});
	
	    // Payment method toggle
	    function selectPayment(type) {
	        ['cash', 'eft', 'snapscan'].forEach(function(t) {
	        document.getElementById('pay-' + t).classList.remove('selected');
	        });
	        document.getElementById('pay-' + type).classList.add('selected');
	    }
</script>

</body>
</html>