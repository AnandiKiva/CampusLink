<?php
include 'auth.php';
include 'db.php';

requireLogin();
requireVerified();

$user_id = $_SESSION['user_id'];

// Fetch all orders for this user
$orders_sql = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC";
$stmt = mysqli_prepare($conn, $orders_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$orders_result = mysqli_stmt_get_result($stmt);
$orders = [];
while ($row = mysqli_fetch_assoc($orders_result)) {
    $orders[] = $row;
}

// Fetch order items for each order
$order_data = [];
foreach ($orders as $order) {
    $items_sql = "SELECT order_items.*, products.title, products.image
                  FROM order_items
                  JOIN products ON order_items.product_id = products.product_id
                  WHERE order_items.order_id = ?";
    $istmt = mysqli_prepare($conn, $items_sql);
    mysqli_stmt_bind_param($istmt, "i", $order['order_id']);
    mysqli_stmt_execute($istmt);
    $items_result = mysqli_stmt_get_result($istmt);
    $items = [];
    while ($item = mysqli_fetch_assoc($items_result)) {
        $items[] = $item;
    }
    $order_data[] = ['order' => $order, 'items' => $items];
}

// Fetch sales — order items where the product belongs to this user
$sales_sql = "SELECT
                order_items.quantity,
                order_items.price,
                orders.created_at,
                orders.delivery_method,
                orders.city,
                products.title,
                products.image,
                users.full_name  AS buyer_name
              FROM order_items
              JOIN products ON order_items.product_id = products.product_id
              JOIN orders   ON order_items.order_id   = orders.order_id
              JOIN users    ON orders.user_id          = users.user_id
              WHERE products.user_id = ?
                AND orders.user_id  != ?
              ORDER BY orders.created_at DESC";
$sstmt = mysqli_prepare($conn, $sales_sql);
mysqli_stmt_bind_param($sstmt, "ii", $user_id, $user_id);
mysqli_stmt_execute($sstmt);
$sales_result = mysqli_stmt_get_result($sstmt);
$sales = [];
while ($row = mysqli_fetch_assoc($sales_result)) {
    $sales[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History — CampusLink</title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        * { box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: #f8f1e8;
            margin: 0;
            color: #111827;
        }

        a { text-decoration: none; }

        /* Nav */
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

        .nav-center a.active {
            background: #fff0e5;
            color: orange;
            font-weight: 700;
        }

        .nav-center a:hover { background: #fff0e5; color: orange; }

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
        }

        .cart-link:hover {
            background: #fff0e5;
            color: orange;
        }

        .user-menu-wrap { position: relative; }

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

        .dropdown-menu svg {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
            color: #6b7280;
        }

        .dropdown-menu .sign-out { color: #ef4444; }
        .dropdown-menu .sign-out svg { color: #ef4444; }

        hr.divider {
            border: none;
            border-top: 1px solid #eadfd2;
            margin: 4px 0;
        }

        /* Page */
        .page-wrap {
            max-width: 860px;
            margin: 48px auto;
            padding: 0 20px;
        }

        .page-header {
            margin-bottom: 32px;
        }

        .page-header h1 {
            font-family: 'Sora', sans-serif;
            font-size: 28px;
            font-weight: 800;
            margin: 0 0 6px;
        }

        .page-header p {
            color: #6b7280;
            margin: 0;
            font-size: 15px;
        }

        /* Section heading */
        .section-heading {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0 0 20px;
        }

        .section-heading h2 {
            font-family: 'Sora', sans-serif;
            font-size: 20px;
            font-weight: 800;
            margin: 0;
            color: #111827;
        }

        .section-heading .section-count {
            background: #eadfd2;
            color: #6b7280;
            font-size: 12px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 20px;
        }

        .section-divider {
            border: none;
            border-top: 2px solid #eadfd2;
            margin: 48px 0 36px;
        }

        /* Empty state */
        .empty-state {
            background: #fffaf3;
            border: 1px solid #eadfd2;
            border-radius: 20px;
            padding: 64px 32px;
            text-align: center;
        }

        .empty-state svg {
            color: #d1c4b0;
            margin-bottom: 16px;
        }

        .empty-state h3 {
            font-size: 18px;
            font-weight: 700;
            margin: 0 0 8px;
        }

        .empty-state p {
            color: #6b7280;
            margin: 0 0 24px;
        }

        .empty-state a {
            background: orange;
            color: white;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 700;
        }

        /* Order card */
        .order-card {
            background: #fffaf3;
            border: 1px solid #eadfd2;
            border-radius: 20px;
            margin-bottom: 20px;
            overflow: hidden;
        }

        .order-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 24px;
            border-bottom: 1px solid #eadfd2;
            flex-wrap: wrap;
            gap: 10px;
        }

        .order-meta {
            display: flex;
            gap: 24px;
            flex-wrap: wrap;
        }

        .order-meta-item {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .order-meta-item span:first-child {
            font-size: 11px;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            font-weight: 600;
        }

        .order-meta-item span:last-child {
            font-size: 14px;
            font-weight: 600;
            color: #111827;
        }

        .status-badge {
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .status-pending   { background: #fef3c7; color: #92400e; }
        .status-completed { background: #d1fae5; color: #065f46; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }

        /* Order items */
        .order-items {
            padding: 16px 24px;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .order-item {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .order-item-img {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            object-fit: cover;
            background: #eadfd2;
            flex-shrink: 0;
        }

        .order-item-img-placeholder {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            background: #eadfd2;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: #9ca3af;
        }

        .order-item-info {
            flex: 1;
        }

        .order-item-title {
            font-size: 14px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 4px;
        }

        .order-item-qty {
            font-size: 13px;
            color: #6b7280;
        }

        .order-item-price {
            font-size: 15px;
            font-weight: 700;
            color: #111827;
        }

        /* Order footer */
        .order-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 24px;
            background: #f8f1e8;
            border-top: 1px solid #eadfd2;
            flex-wrap: wrap;
            gap: 8px;
        }

        .order-footer-left {
            font-size: 13px;
            color: #6b7280;
        }

        .order-total {
            font-size: 16px;
            font-weight: 800;
            color: #111827;
        }

        /* ── Sale card (Items Sold) ── */
        .sale-card {
            background: #fffaf3;
            border: 1px solid #eadfd2;
            border-radius: 20px;
            margin-bottom: 20px;
            overflow: hidden;
            display: flex;
            align-items: stretch;
        }

        .sale-card-img {
            width: 100px;
            flex-shrink: 0;
            object-fit: cover;
            border-right: 1px solid #eadfd2;
        }

        .sale-card-img-placeholder {
            width: 100px;
            flex-shrink: 0;
            background: #eadfd2;
            display: flex;
            align-items: center;
            justify-content: center;
            border-right: 1px solid #eadfd2;
            color: #9ca3af;
        }

        .sale-card-body {
            flex: 1;
            padding: 18px 22px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .sale-card-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .sale-card-title {
            font-size: 15px;
            font-weight: 700;
            color: #111827;
        }

        .sale-card-amount {
            font-size: 16px;
            font-weight: 800;
            color: #111827;
            white-space: nowrap;
        }

        .sale-card-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
        }

        .sale-meta-item {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .sale-meta-item span:first-child {
            font-size: 11px;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            font-weight: 600;
        }

        .sale-meta-item span:last-child {
            font-size: 13px;
            font-weight: 600;
            color: #374151;
        }

        .sale-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #d1fae5;
            color: #065f46;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            align-self: flex-start;
        }

        footer {
            margin-top: 70px;
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
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 800;
            font-size: 11px;
        }

        @media (max-width: 768px) {
            .cart-link { display: none; }

            nav {
                height: 64px;
                padding: 0 16px;
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
            }

            .nav-logo-icon        { width: 36px; height: 36px; }
            .nav-logo-text strong { font-size: 15px; }
            .nav-logo-text span   { display: none; }
            .nav-center           { display: none; }
            .nav-right            { gap: 8px; }

            .sell-btn {
                padding: 8px 12px;
                font-size: 13px;
                border-radius: 10px;
            }

            .user-icon-btn { width: 36px; height: 36px; }

            .order-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .order-meta { gap: 14px; }

            .sale-card { flex-direction: column; }

            .sale-card-img,
            .sale-card-img-placeholder {
                width: 100%;
                height: 140px;
                border-right: none;
                border-bottom: 1px solid #eadfd2;
            }

            footer {
                flex-direction: column;
                gap: 15px;
                text-align: center;
                align-items: center;
                padding: 20px;
            }
        }
    </style>
</head>
<body>

<!-- Nav -->
<nav>
    <a href="index.php" class="nav-logo">
        <div class="nav-logo-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>
                <line x1="3" y1="6" x2="21" y2="6"/>
                <path d="M16 10a4 4 0 01-8 0"/>
            </svg>
        </div>
        <div class="nav-logo-text">
            <strong>CampusLink</strong>
            <span>Built for student hustle</span>
        </div>
    </a>

    <div class="nav-center">
        <a href="products.php">Browse</a>
        <a href="my_listings.php">My Listings</a>
        <a href="order_history.php" class="active">Orders</a>
        <a href="inbox.php">Messages</a>
        <a href="wishlist.php">Wishlist</a>
    </div>

    <div class="nav-right">
        <a href="cart.php" class="cart-link">Cart</a>
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

                <a href="wishlist.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/>
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

                <a href="inbox.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>
                    </svg>
                    Messages
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

<!-- Page Content -->
<div class="page-wrap">

    <div class="page-header">
        <h1>Order History</h1>
        <p>Your purchases and sales on CampusLink.</p>
    </div>

    <!-- ── PURCHASES ── -->
    <div class="section-heading">
        <h2>My Purchases</h2>
        <span class="section-count"><?php echo count($order_data); ?></span>
    </div>

    <?php if (empty($order_data)): ?>
        <div class="empty-state">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="1.5"
                 stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>
                <line x1="3" y1="6" x2="21" y2="6"/>
                <path d="M16 10a4 4 0 01-8 0"/>
            </svg>
            <h3>No orders yet</h3>
            <p>You haven't bought anything on CampusLink yet.</p>
            <a href="products.php">Browse listings</a>
        </div>

    <?php else: ?>
        <?php foreach ($order_data as $entry):
            $order = $entry['order'];
            $items = $entry['items'];
            $status_class = 'status-' . strtolower($order['payment_status']);
            $date = date('d M Y', strtotime($order['created_at']));
        ?>
        <div class="order-card">

            <div class="order-header">
                <div class="order-meta">
                    <div class="order-meta-item">
                        <span>Order ID</span>
                        <span>#<?php echo $order['order_id']; ?></span>
                    </div>
                    <div class="order-meta-item">
                        <span>Date</span>
                        <span><?php echo $date; ?></span>
                    </div>
                    <div class="order-meta-item">
                        <span>Payment</span>
                        <span><?php echo htmlspecialchars(ucfirst($order['payment_method'])); ?></span>
                    </div>
                    <div class="order-meta-item">
                        <span>Delivery</span>
                        <span><?php echo htmlspecialchars(ucfirst($order['delivery_method'])); ?></span>
                    </div>
                </div>
                <span class="status-badge <?php echo $status_class; ?>">
                    <?php echo htmlspecialchars(ucfirst($order['payment_status'])); ?>
                </span>
            </div>

            <div class="order-items">
                <?php foreach ($items as $item):
                    $first_image = !empty($item['image']) ? explode(',', $item['image'])[0] : '';
                ?>
                <div class="order-item">
                    <?php if ($first_image): ?>
                        <img class="order-item-img"
                             src="uploads/<?php echo rawurlencode(trim($first_image)); ?>"
                             alt="<?php echo htmlspecialchars($item['title']); ?>">
                    <?php else: ?>
                        <div class="order-item-img-placeholder">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="1.5">
                                <rect x="3" y="3" width="18" height="18" rx="2"/>
                                <circle cx="8.5" cy="8.5" r="1.5"/>
                                <polyline points="21 15 16 10 5 21"/>
                            </svg>
                        </div>
                    <?php endif; ?>

                    <div class="order-item-info">
                        <div class="order-item-title"><?php echo htmlspecialchars($item['title']); ?></div>
                        <div class="order-item-qty">Qty: <?php echo $item['quantity']; ?></div>
                    </div>

                    <div class="order-item-price">
                        R<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="order-footer">
                <div class="order-footer-left">
                    <?php echo count($items); ?> item<?php echo count($items) !== 1 ? 's' : ''; ?>
                    · <?php echo htmlspecialchars($order['city']); ?>
                </div>

                <div style="display:flex; align-items:center; gap:16px;">
                    <?php if ($order['payment_status'] === 'pending'): ?>
                    <form method="POST" action="cancel_order.php"
                          onsubmit="return confirm('Are you sure you want to cancel this order?');">
                        <?php csrfInput(); ?>
                        <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                        <button type="submit" style="background:#fee2e2; color:#991b1b; border:none;
                                padding:8px 18px; border-radius:10px; font-weight:700;
                                font-size:13px; cursor:pointer;">
                            Cancel Order
                        </button>
                    </form>
                    <?php endif; ?>
                    <div class="order-total">Total: R<?php echo number_format($order['total'], 2); ?></div>
                </div>
            </div>

        </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- ── SALES ── -->
    <hr class="section-divider">

    <div class="section-heading">
        <h2>Items Sold</h2>
        <span class="section-count"><?php echo count($sales); ?></span>
    </div>

    <?php if (empty($sales)): ?>
        <div class="empty-state">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="1.5"
                 stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 12 20 22 4 22 4 12"/>
                <rect x="2" y="7" width="20" height="5"/>
                <line x1="12" y1="22" x2="12" y2="7"/>
                <path d="M12 7H7.5a2.5 2.5 0 010-5C11 2 12 7 12 7z"/>
                <path d="M12 7h4.5a2.5 2.5 0 000-5C13 2 12 7 12 7z"/>
            </svg>
            <h3>No sales yet</h3>
            <p>When another student buys one of your listings, it will appear here.</p>
            <a href="add_product.php">+ List an item</a>
        </div>

    <?php else: ?>
        <?php foreach ($sales as $sale):
            $first_image = !empty($sale['image']) ? explode(',', $sale['image'])[0] : '';
            $sale_date   = date('d M Y', strtotime($sale['created_at']));
            $sale_amount = number_format($sale['price'] * $sale['quantity'], 2);
        ?>
        <div class="sale-card">

            <?php if ($first_image): ?>
                <img class="sale-card-img"
                     src="uploads/<?php echo rawurlencode(trim($first_image)); ?>"
                     alt="<?php echo htmlspecialchars($sale['title']); ?>">
            <?php else: ?>
                <div class="sale-card-img-placeholder">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="1.5">
                        <rect x="3" y="3" width="18" height="18" rx="2"/>
                        <circle cx="8.5" cy="8.5" r="1.5"/>
                        <polyline points="21 15 16 10 5 21"/>
                    </svg>
                </div>
            <?php endif; ?>

            <div class="sale-card-body">
                <div class="sale-card-top">
                    <div class="sale-card-title"><?php echo htmlspecialchars($sale['title']); ?></div>
                    <div class="sale-card-amount">R<?php echo $sale_amount; ?></div>
                </div>

                <div class="sale-card-meta">
                    <div class="sale-meta-item">
                        <span>Buyer</span>
                        <span><?php echo htmlspecialchars($sale['buyer_name']); ?></span>
                    </div>
                    <div class="sale-meta-item">
                        <span>Quantity</span>
                        <span><?php echo $sale['quantity']; ?></span>
                    </div>
                    <div class="sale-meta-item">
                        <span>Delivery</span>
                        <span><?php echo htmlspecialchars(ucfirst($sale['delivery_method'])); ?></span>
                    </div>
                    <div class="sale-meta-item">
                        <span>Date</span>
                        <span><?php echo $sale_date; ?></span>
                    </div>
                </div>

                <span class="sale-badge">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2.5"
                         stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    Sold
                </span>
            </div>

        </div>
        <?php endforeach; ?>
    <?php endif; ?>

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
        document.getElementById('dropdownMenu').classList.toggle('open');
    }
    document.addEventListener('click', function(e) {
        const wrap = document.querySelector('.user-menu-wrap');
        if (wrap && !wrap.contains(e.target)) {
            document.getElementById('dropdownMenu').classList.remove('open');
        }
    });
</script>

</body>
</html>