<?php
include 'auth.php';
include 'db.php';

requireLogin();


$full_name = $_SESSION['full_name'];

// Unread notification count for bell badge
$bell_stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS cnt FROM notifications WHERE user_id = ? AND is_read = 0");
mysqli_stmt_bind_param($bell_stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($bell_stmt);
$bell_row = mysqli_fetch_assoc(mysqli_stmt_get_result($bell_stmt));
$unread_count = (int)($bell_row['cnt'] ?? 0);
mysqli_stmt_close($bell_stmt);
?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CampusLink | Dashboard</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * 
        {
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

		.cart-link {
		    background: white;
		    color: #374151;
		    padding: 10px 20px;
		    border-radius: 12px;
		    font-weight: 700;
		    border: 1px solid #eadfd2;
		    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
		}
		
		.cart-link:hover { 
			background: #fff0e5; 
			color: orange; }

        .user-menu-wrap {
            position: relative;
        }

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
                z-index: 999;
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

        .dropdown-menu .sign-out     { color: #dc2626; }
        .dropdown-menu .sign-out svg { color: #dc2626; }

        .hero {
            padding: 100px 12% 85px;
            background: linear-gradient(135deg, #fffaf3 0%, #fff3e8 55%, #e9fbf8 100%);
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 70px;
            align-items: center;
        }

        .badge {
            display: inline-block;
            color: orange;
            background: #fff0e5;
            border: 1px solid #ffd3b8;
            padding: 9px 16px;
            border-radius: 999px;
            font-weight: 700;
            margin-bottom: 28px;
        }

        .hero h1 {
            font-size: 70px;
            line-height: 1.08;
            margin: 0;
            color: #020617;
        }

        .hero h1 span {
            color: orange;
        }

        .hero p {
            color: #475569;
            font-size: 21px;
            line-height: 1.55;
            max-width: 650px;
            margin: 28px 0;
        }

        .hero-actions {
            display: flex;
            gap: 14px;
            margin-bottom: 25px;
        }

        .primary-btn {
            background: orange;
            color: white;
            padding: 15px 30px;
            border-radius: 14px;
            font-weight: 800;
            box-shadow: 0 3px 8px rgba(0,0,0,0.14);
        }

        .secondary-btn {
            background: white;
            color: #111827;
            padding: 15px 30px;
            border-radius: 14px;
            font-weight: 800;
            border: 1px solid #eadfd2;
        }

        .trust-row {
            display: flex;
            gap: 28px;
            color: #64748b;
            font-size: 14px;
        }

        .preview-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .preview-card {
            background: white;
            border: 1px solid #eadfd2;
            border-radius: 22px;
            padding: 26px;
            min-height: 150px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.06);
        }

        .preview-icon {
            font-size: 28px;
            margin-bottom: 18px;
        }

        .preview-icon {
            width: 60px;
            height: 60px;
            border-radius: 18px;

            background: rgba(255, 140, 66, 0.12);

            color: #ff8c42;

            display: flex;
            align-items: center;
            justify-content: center;

            margin-bottom: 18px;
        }
        .preview-card h3 {
            margin: 0 0 12px;
            font-size: 18px;
        }

        .preview-card .price {
            color: orange;
            font-size: 22px;
            font-weight: 800;
        }

        .preview-card .condition {
            display: block;
            text-align: right;
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .section {
            padding: 70px 12%;
        }

        .section-title {
            text-align: center;
            max-width: 760px;
            margin: 0 auto 45px;
        }

        .section-title h2 {
            font-size: 38px;
            margin: 0 0 12px;
        }

        .section-title p {
            color: #64748b;
            font-size: 18px;
            line-height: 1.45;
        }

        .steps {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 26px;
        }

        .step-card {
            background: white;
            border: 1px solid #eadfd2;
            border-radius: 22px;
            padding: 32px;
            min-height: 230px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.05);
        }

        .step-icon {
            background: #fff0e5;
            color: orange;
            width: 58px;
            height: 58px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            margin-bottom: 26px;
        }

        .step-card h3 {
            font-size: 22px;
            margin-bottom: 12px;
        }

        .step-card p {
            color: #64748b;
            line-height: 1.5;
        }

        .category-header {
            display: flex;
            justify-content: space-between;
            align-items: end;
            margin-bottom: 28px;
        }

        .category-header h2 {
            font-size: 32px;
            margin: 0;
        }

        .category-header p {
            color: #64748b;
            margin-top: 6px;
        }

        .category-header a {
            color: orange;
            font-weight: 800;
        }

        .categories {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 16px;
        }

        .category-card {
            background: white;
            border: 1px solid #eadfd2;
            border-radius: 18px;
            padding: 24px 12px;
            text-align: center;
            box-shadow: 0 4px 14px rgba(0,0,0,0.04);
        }

        .category-card span {
            color: #64748b;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .category-card strong {
            display: block;
            font-size: 18px;
            margin-top: 8px;
        }

        .cta {
            margin: 80px 12%;
            background: linear-gradient(135deg, #ffe5d5 0%, #e9fbf8 100%);
            border: 1px solid #ffd3b8;
            border-radius: 26px;
            text-align: center;
            padding: 70px 20px;
        }

        .cta h2 {
            font-size: 42px;
            margin: 0 0 12px;
        }

        .cta p {
            color: #64748b;
            font-size: 18px;
            margin-bottom: 30px;
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

        @media (max-width: 1000px) {
			.cart-link {
				    display: none;
				}

		    nav {
		        height: 64px;
		        padding: 0 16px;
		        flex-direction: row;
		        align-items: center;
		        justify-content: space-between;
		    }
		
		    .nav-center {
		        display: none;
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
		
		    .hero {
		        padding: 45px 18px;
		        grid-template-columns: 1fr;
		        gap: 35px;
		    }
		
		    .hero h1 {
		        font-size: 38px;
		    }
		
		    .hero p {
		        font-size: 16px;
		    }
		
		    .hero-actions {
		        flex-direction: column;
		    }
		
		    .primary-btn,
		    .secondary-btn {
		        width: 100%;
		        text-align: center;
		    }
		
		    .trust-row {
		        flex-direction: column;
		        gap: 8px;
		    }
		
		    .preview-grid {
		        grid-template-columns: 1fr;
		    }
		
		    .section {
		        padding: 45px 18px;
		    }
		
		    .section-title h2 {
		        font-size: 28px;
		    }
		
		    .section-title p {
		        font-size: 15px;
		    }
		
		    .steps {
		        grid-template-columns: 1fr;
		    }
		
		    .category-header {
		        flex-direction: column;
		        align-items: flex-start;
		        gap: 8px;
		    }
		
		    .categories {
		        grid-template-columns: 1fr 1fr;
		    }
		
		    .cta {
		        margin: 45px 18px;
		        padding: 45px 18px;
		    }
		
		    .cta h2 {
		        font-size: 30px;
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
		<a href="cart.php" class="cart-link"> Cart</a>
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

				<a href="wishlist.php" class="mobile-only">
				    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
				         stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
				        <path d="M20.8 4.6c-1.6-1.5-4.1-1.4-5.6.2L12 8.1 8.8 4.8C7.3 3.2 4.8 3.1 3.2 4.6c-1.7 1.6-1.7 4.3-.1 5.9L12 19.5l8.9-9c1.6-1.6 1.6-4.3-.1-5.9z"></path>
				    </svg>
				    Wishlist
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

<section class="hero">
    <div>
        <div class="badge">✦ Verified students only</div>

        <h1>
            Buy, sell & <span>hustle safely</span> on campus.
        </h1>

        <p>
            Welcome, <?php echo $full_name; ?>. CampusLink is the trusted C2C marketplace
            built for South African university students. Trade textbooks, gear and side-hustle
            services backed by student verification, ratings and safer communication.
        </p>

        <div class="hero-actions">
            <a href="add_product.php" class="primary-btn">Post a listing</a>
            <a href="products.php" class="secondary-btn">Browse marketplace →</a>
        </div>

        <div class="trust-row">
            <span>✓ Student email required</span>
            <span>✓ Buyer & seller ratings</span>
            <span>✓ Safer messaging</span>
        </div>
    </div>

    <div class="preview-grid">

        <div class="preview-card">
        <div class="preview-icon">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round">

                <path d="M3 5.5A2.5 2.5 0 0 1 5.5 3H11a3 3 0 0 1 3 3v15a2 2 0 0 0-2-2H5.5A2.5 2.5 0 0 0 3 21z"/>

                <path d="M21 5.5A2.5 2.5 0 0 0 18.5 3H13a3 3 0 0 0-3 3v15a2 2 0 0 1 2-2h6.5A2.5 2.5 0 0 1 21 21z"/>

            </svg>
        </div>

            <h3>Textbook bundle</h3>
            <div class="price">R450</div>
            <span class="condition">Like new</span>
       </div>

        <div class="preview-card">
            <div class="preview-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="1.8"
                stroke-linecap="round" stroke-linejoin="round">
                <circle cx="6" cy="6" r="3"/>
                <circle cx="6" cy="18" r="3"/>
                <path d="M8.2 8.2L20 20"/>
                <path d="M8.2 15.8L20 4"/>
            </svg>
            </div>
            <h3>Box braids</h3>
            <div class="price">R350</div>
            <span class="condition">Service</span>
        </div>

        <div class="preview-card">
            <div class="preview-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="1.8"
                stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 14a8 8 0 0 1 16 0"/>
                <rect x="3" y="13" width="4" height="7" rx="2"/>
                <rect x="17" y="13" width="4" height="7" rx="2"/>
            </svg>
        </div>
            <h3>Sony headphones</h3>
            <div class="price">R1200</div>
            <span class="condition">Good</span>
        </div>

        <div class="preview-card">
            <div class="preview-icon">
             <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="1.8"
                stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="4" width="18" height="12" rx="2"/>
                <path d="M7 20h10"/>
                <path d="M12 16v4"/>
                <path d="M7 8h6"/>
                <path d="M7 12h4"/>
                <circle cx="17" cy="10" r="2"/>
            </svg>
        </div>
            <h3>Calculus tutor</h3>
            <div class="price">R150/hr</div>
            <span class="condition">Service</span>
        </div>
    </div>
</section>

<section class="section">
    <div class="section-title">
        <h2>How CampusLink works</h2>
        <p>
            A safer alternative to WhatsApp groups and Facebook Marketplace —
            built around trust, ratings and easy student-to-student trading.
        </p>
    </div>

    <div class="steps">
        <div class="step-card">
            <div class="step-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
            </div>
            <h3>1. Verify with your student email</h3>
            <p>Students register using their institution details to help build a trusted campus community.</p>
        </div>

        <div class="step-card">
            <div class="step-icon">
                 <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round">

                    <path d="M20.59 13.41L11 3H4v7l9.59 9.59a2 2 0 0 0 2.82 0l4.18-4.18a2 2 0 0 0 0-2.82z"/>
                    <circle cx="7.5" cy="7.5" r="1.5"/>

                </svg>
            </div>
            <h3>2. List under 3 minutes</h3>
            <p>Upload a photo, add a price, choose a category and publish your product or service.</p>
        </div>

        <div class="step-card">
            <div class="step-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round">

                    <circle cx="11" cy="11" r="7"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>

                </svg>
            </div>
            <h3>3. Buy and rate</h3>
            <p>Browse listings, message sellers and leave ratings after successful student trades.</p>
        </div>
    </div>
</section>

<section class="section">
    <div class="category-header">
        <div>
            <h2>Popular categories</h2>
            <p>From textbooks to braids — find your hustle.</p>
        </div>

        <a href="products.php">See all →</a>
    </div>

    <div class="categories">
        <div class="category-card"><span>Product</span><strong>Textbooks</strong></div>
        <div class="category-card"><span>Product</span><strong>Electronics</strong></div>
        <div class="category-card"><span>Product</span><strong>Clothing</strong></div>
        <div class="category-card"><span>Product</span><strong>Food</strong></div>
        <div class="category-card"><span>Product</span><strong>Furniture</strong></div>
        <div class="category-card"><span>Product</span><strong>Other products</strong></div>
    </div>
</section>

<section class="cta">
    <h2>Ready to start hustling?</h2>
    <p>Join South African students trading the smart way.</p>

    <div class="hero-actions" style="justify-content:center;">
        <a href="add_product.php" class="primary-btn">Create a listing</a>
        <a href="products.php" class="secondary-btn">Browse first</a>
    </div>
</section>

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
    document.getElementById('dropdownMenu').classList.toggle('open');
}

document.addEventListener('click', function(e) {
    var wrap = document.querySelector('.user-menu-wrap');
    if (wrap && !wrap.contains(e.target)) {
        document.getElementById('dropdownMenu').classList.remove('open');
    }
});
</script>
</body>
</html>