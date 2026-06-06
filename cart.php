<?php
include 'auth.php';
include 'db.php';

requireLogin();
requireVerified();

$user_id = $_SESSION['user_id'];

$sql = "SELECT products.*, cart.cart_id, cart.quantity
        FROM cart
        JOIN products ON cart.product_id = products.product_id
        WHERE cart.user_id = '$user_id'";

$result = mysqli_query($conn, $sql);

$total = 0;
$rows = [];
while ($row = mysqli_fetch_assoc($result)) {
    $row['subtotal'] = $row['price'] * $row['quantity'];
    $total += $row['subtotal'];
    $rows[] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>

	<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CampusLink | Cart</title>

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

        .cart-layout {
            display: grid;
            grid-template-columns: 1fr 360px;
            gap: 28px;
            align-items: start;
        }

        /*  Cart items  */
        .cart-item {
            background: white;
            border: 1px solid #eadfd2;
            border-radius: 20px;
            padding: 22px;
            margin-bottom: 16px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.05);
            display: grid;
            grid-template-columns: 100px 1fr auto;
            gap: 20px;
            align-items: center;
        }

        .item-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 14px;
            background: #f1ece4;
        }

        .item-image-placeholder {
            width: 100px;
            height: 100px;
            border-radius: 14px;
            background: #f1ece4;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
        }

        .item-info h3 {
            font-size: 18px;
            margin: 0 0 6px;
            color: #111827;
        }

        .item-info .unit-price {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .item-info .subtotal {
            color: orange;
            font-size: 20px;
            font-weight: 800;
        }

        .qty-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 12px;
        }

        .qty-row label {
            font-size: 13px;
            color: #64748b;
            font-weight: 600;
        }

        .qty-form {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .qty-btn {
            width: 30px;
            height: 30px;
            border: 1px solid #eadfd2;
            background: white;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #374151;
            font-weight: 700;
            transition: background 0.12s;
        }

        .qty-btn:hover { background: #fff0e5; color: orange; }

        .qty-display {
            font-size: 15px;
            font-weight: 700;
            min-width: 28px;
            text-align: center;
        }

        .item-actions {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 10px;
        }

        .remove-btn {
            background: #fff0f0;
            color: #dc2626;
            border: 1px solid #fecaca;
            border-radius: 10px;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
            transition: background 0.12s;
        }

        .remove-btn:hover { background: #fee2e2; }

        /*  Empty state  */
        .empty-state {
            background: white;
            border: 1px dashed #d9cfc3;
            border-radius: 22px;
            text-align: center;
            padding: 80px 20px;
            color: #374151;
        }

        .empty-state .empty-icon {
            font-size: 48px;
            margin-bottom: 18px;
        }

        .empty-state h2 {
            font-size: 24px;
            margin: 0 0 10px;
        }

        .empty-state p {
            color: #64748b;
            margin-bottom: 24px;
        }

        .browse-btn {
            background: orange;
            color: white;
            padding: 13px 28px;
            border-radius: 14px;
            font-weight: 800;
            display: inline-block;
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
            font-size: 22px;
            margin: 0 0 22px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            font-size: 15px;
            margin-bottom: 12px;
            color: #374151;
        }

        .summary-row.total {
            font-size: 20px;
            font-weight: 800;
            color: #111827;
            border-top: 1px solid #eadfd2;
            padding-top: 16px;
            margin-top: 8px;
        }

        .summary-row.total span:last-child {
            color: orange;
        }

        .checkout-btn {
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
            box-shadow: 0 3px 8px rgba(0,0,0,0.14);
            transition: opacity 0.15s;
        }

        .checkout-btn:hover { opacity: 0.9; }

        .continue-link {
            display: block;
            text-align: center;
            margin-top: 14px;
            color: #64748b;
            font-size: 14px;
        }

        .continue-link:hover { color: orange; }

        .item-count-badge {
            background: #fff0e5;
            color: orange;
            border: 1px solid #ffd3b8;
            display: inline-block;
            padding: 5px 14px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 22px;
        }

        /*  Footer  */
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
		
		    .cart-layout {
		        grid-template-columns: 1fr;
		    }
		
		    .summary-card {
		        position: static;
		    }
		
		    .cart-item {
		        grid-template-columns: 80px 1fr;
		        gap: 14px;
		        padding: 16px;
		    }
		
		    .item-image,
		    .item-image-placeholder {
		        width: 80px;
		        height: 80px;
		    }
		
		    .item-info h3 {
		        font-size: 15px;
		    }
		
		    .item-info .subtotal {
		        font-size: 17px;
		    }
		
		    .item-actions {
		        grid-column: 1 / -1;
		        flex-direction: row;
		        align-items: center;
		        justify-content: flex-end;
		    }
		
		    .item-count-badge {
		        font-size: 12px;
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

<!-- ── Page ── -->
<div class="page">

    <div class="page-header">
        <h1>My Cart</h1>
        <p>Review your items before checking out.</p>
    </div>

    <?php if (empty($rows)) { ?>

                            <div class="empty-state">
                        <div class="empty-icon">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#d1c4b0"
                                stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                                <line x1="3" y1="6" x2="21" y2="6"/>
                                <path d="M16 10a4 4 0 0 1-8 0"/>
                            </svg>
                        </div>
                        <h2>Your cart is empty</h2>
                        <p>Browse the marketplace and add something you like.</p>
                        <a href="products.php" class="browse-btn">Browse marketplace</a>
                    </div>

                    <?php } else { ?>

                        <div class="item-count-badge">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="orange"
                                stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"
                                style="vertical-align: middle; margin-right: 5px; margin-top: -2px;">
                                <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                                <line x1="3" y1="6" x2="21" y2="6"/>
                                <path d="M16 10a4 4 0 0 1-8 0"/>
                            </svg>
                            <?php echo count($rows); ?> item<?php echo count($rows) !== 1 ? 's' : ''; ?> in your cart
                        </div>

        <div class="cart-layout">

            <!-- Items column -->
            <div>
                <?php foreach ($rows as $row) { ?>

                    <div class="cart-item">

                        <!-- Image -->
                        <?php if (!empty($row['image'])) { ?>
                            <?php $first_image = explode(',', $row['image'])[0]; ?>
							<img class="item-image"
							     src="uploads/<?php echo rawurlencode(trim($first_image)); ?>"
							     alt="<?php echo htmlspecialchars($row['title']); ?>">
                        <?php } else { ?>
                            <div class="item-image-placeholder">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#c4b8a8"
                                    stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                                    <line x1="12" y1="22.08" x2="12" y2="12"/>
                                </svg>
                            </div>
                        <?php } ?>

                        <!-- Info -->
                       
						<div class="item-info">
						    <h3><?php echo htmlspecialchars($row['title']); ?></h3>
						    <div class="unit-price">R<?php echo number_format($row['price'], 2); ?> each</div>
						    <div class="subtotal">R<?php echo number_format($row['subtotal'], 2); ?></div>
						
						    <div class="qty-row">
						        <span class="label">Qty:</span>
						        <div class="qty-form">
						            <form method="POST" action="update_cart.php" style="display:inline;">
						                <?php csrfInput(); ?>
						                <input type="hidden" name="id" value="<?php echo $row['cart_id']; ?>">
						                <input type="hidden" name="action" value="decrease">
						                <button type="submit" class="qty-btn">−</button>
						            </form>
						            <span class="qty-display"><?php echo $row['quantity']; ?></span>
						            <form method="POST" action="update_cart.php" style="display:inline;">
						                <?php csrfInput(); ?>
						                <input type="hidden" name="id" value="<?php echo $row['cart_id']; ?>">
						                <input type="hidden" name="action" value="increase">
						                <button type="submit" class="qty-btn">+</button>
						            </form>
						        </div>
						    </div>
						</div>

                        <!-- Remove -->
                        <div class="item-actions">
                            <form method="POST" action="remove_cart.php">
                                <?php csrfInput(); ?>
                                <input type="hidden" name="cart_id" value="<?php echo $row['cart_id']; ?>">
                                <button type="submit" class="remove-btn">Remove</button>
                            </form>
                            </a>
                        </div>

                    </div>

                <?php } ?>
            </div>

            <!-- Summary panel -->
            <div class="summary-card">
                <h2>Order summary</h2>

                <?php foreach ($rows as $row) { ?>
                    <div class="summary-row">
                        <span><?php echo $row['title']; ?></span>
                        <span>R<?php echo number_format($row['subtotal'], 2); ?></span>
                    </div>
                <?php } ?>

                <div class="summary-row total">
                    <span>Total</span>
                    <span>R<?php echo number_format($total, 2); ?></span>
                </div>

                <a href="checkout.php" class="checkout-btn">Proceed to checkout →</a>
                <a href="products.php" class="continue-link">← Continue shopping</a>
            </div>

        </div>

    <?php } ?>

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