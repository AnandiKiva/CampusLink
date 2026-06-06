<?php
include 'auth.php';
include 'db.php';

$search = "";
$category_filter = "";
$type_filter = $_GET['type'] ?? 'all';

$sql = "SELECT products.*, users.full_name, categories.category_name
        FROM products
        JOIN users ON products.user_id = users.user_id
        JOIN categories ON products.category_id = categories.category_id
		WHERE products.status = 'active' AND 1=1";
       

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $sql .= " AND products.title LIKE '%$search%'";
}

if (isset($_GET['category']) && !empty($_GET['category'])) {
    $category_filter = $_GET['category'];
    $sql .= " AND categories.category_id = '$category_filter'";
}

if ($type_filter === 'products') {
    $sql .= " AND LOWER(categories.category_name) != 'services'";
}

if ($type_filter === 'services') {
    $sql .= " AND LOWER(categories.category_name) = 'services'";
}

$sql .= " ORDER BY products.created_at DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CampusLink | Browse</title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
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
		}
		
		.cart-link:hover { 
			background: #fff0e5; 
			color: orange; }

        /*  User dropdown  */
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

        .dropdown-menu .sign-out {
            color: #dc2626;
        }

        .dropdown-menu .sign-out svg {
            color: #dc2626;
        }

        /*  Page  */
        .page {
            max-width: 1200px;
            margin: auto;
            padding: 45px 20px;
        }

        .top-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 26px;
        }

        .top-section h1 {
            font-size: 38px;
            margin: 0;
            color: #020617;
        }

        .top-section p {
            color: #64748b;
            margin-top: 6px;
            font-size: 16px;
        }

        .post-btn {
            background: orange;
            color: white;
            padding: 13px 22px;
            border-radius: 14px;
            font-weight: 700;
            box-shadow: 0 3px 8px rgba(0,0,0,0.12);
        }

        .search-box {
            background: white;
            border: 1px solid #eadfd2;
            border-radius: 22px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        }

        .search-row {
            display: grid;
            grid-template-columns: 1fr 240px 120px;
            gap: 14px;
        }

        input,
        select {
            padding: 14px;
            border: 1px solid #ddd6ce;
            border-radius: 14px;
            font-size: 15px;
            outline: none;
        }

        input:focus,
        select:focus {
            border-color: orange;
        }

        button {
            background: orange;
            color: white;
            border: none;
            border-radius: 14px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
        }

        .tabs {
            margin-top: 16px;
            display: flex;
            gap: 10px;
        }

        .tab {
            background: #f7efe6;
            padding: 9px 18px;
            border-radius: 14px;
            color: #374151;
            font-size: 14px;
        }

        .tab.active {
            background: #fff0e5;
            color: orange;
            font-weight: 700;
        }

        .products-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
        }

        .product-card {
            background: white;
            border-radius: 22px;
            overflow: hidden;
            border: 1px solid #eadfd2;
            box-shadow: 0 4px 14px rgba(0,0,0,0.06);
        }

        .product-card img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            object-position: center;
            background: #f1f1f1;
        }

        .product-info {
            padding: 18px;
        }

        .category {
            background: #fff0e5;
            color: orange;
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .product-info h3 {
            margin: 5px 0;
            font-size: 20px;
            color: #020617;
        }

        .description {
            color: #64748b;
            font-size: 14px;
            min-height: 42px;
            line-height: 1.4;
        }

        .price {
            color: orange;
            font-size: 24px;
            font-weight: 800;
            margin: 12px 0;
        }

        .seller {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .card-actions {
            display: flex;
            gap: 10px;
        }

        .card-actions form {
            flex: 1;
        }

        .wishlist-btn,
        .details-btn {
            width: 100%;
            display: block;
            text-align: center;
            padding: 11px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
        }

        .wishlist-btn {
            background: #fff0e5;
            color: orange;
        }

        .details-btn {
            background: orange;
            color: white;
        }

        .empty-state {
            background: white;
            border: 1px dashed #d9cfc3;
            border-radius: 22px;
            text-align: center;
            padding: 70px 20px;
            color: #374151;
        }

        .empty-state h2 {
            margin-bottom: 5px;
            color: #020617;
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
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 11px;
        }

		.mobile-only {
    display: none;
}

@media (max-width: 768px) {
    body {
        overflow-x: hidden;
    }

    nav {
        height: 64px;
        padding: 0 16px;
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

    .nav-logo-text span {
        display: none;
    }

    .sell-btn {
        padding: 8px 12px;
        font-size: 13px;
    }

    .page {
        padding: 25px 14px;
        width: 100%;
        max-width: 100vw;
    }

    .top-section {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }

    .top-section h1 {
        font-size: 28px;
    }

    .post-btn {
        width: 100%;
        text-align: center;
    }

    .search-box {
		    padding: 14px;
		}
		
		.search-row {
		    grid-template-columns: 1fr;
		    gap: 10px;
		}
		
		.search-row input,
		.search-row select,
		.search-row button {
		    width: 100%;
		    max-width: 100%;
		}
		
		.search-row button {
		    padding: 13px;
		}

    .products-container {
        grid-template-columns: 1fr 1fr;
        gap: 14px;
    }

    .product-card img {
        height: 140px;
    }

    .description {
        display: none;
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
        <a href="products.php" class= "active">Browse</a>
        <a href="my_listings.php">My listings</a>
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

        <!-- User icon + dropdown -->
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


				<a href="wishlist.php" class="mobile-only">
				    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
				         stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
				        <path d="M20.8 4.6c-1.6-1.5-4.1-1.4-5.6.2L12 8.1 8.8 4.8C7.3 3.2 4.8 3.1 3.2 4.6c-1.7 1.6-1.7 4.3-.1 5.9L12 19.5l8.9-9c1.6-1.6 1.6-4.3-.1-5.9z"></path>
				    </svg>
				    Wishlist
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

<div class="page">

    <div class="top-section">
        <div>
            <h1>Browse the marketplace</h1>
            <p>Find textbooks, electronics, services, fashion, food and more from verified students.</p>
        </div>
        <a class="post-btn" href="add_product.php">+ Post a listing</a>
    </div>

    <div class="search-box">
        <form method="GET">
			<input type="hidden" name="type" value="<?php echo htmlspecialchars($type_filter); ?>">
            <div class="search-row">
                <input type="text"
                       name="search"
                       placeholder="Search products or services..."
                        value="<?php echo htmlspecialchars($search); ?>">
                <select name="category">
                    <option value="">All categories</option>
                    <?php
                    $categories = mysqli_query($conn, "SELECT * FROM categories");
                    while ($category = mysqli_fetch_assoc($categories)) {
                        $selected = ($category_filter == $category['category_id']) ? "selected" : "";
                        echo "<option value='".$category['category_id']."' $selected>"
                             .$category['category_name'].
                             "</option>";
                    }
                    ?>
                </select>

                <button type="submit">Search</button>
            </div>

            <div class="tabs">
			    <a href="products.php?type=all"
			       class="tab <?php echo ($type_filter === 'all') ? 'active' : ''; ?>">
			        All
			    </a>
			
			    <a href="products.php?type=products"
			       class="tab <?php echo ($type_filter === 'products') ? 'active' : ''; ?>">
			        Products
			    </a>
			
			    <a href="products.php?type=services"
			       class="tab <?php echo ($type_filter === 'services') ? 'active' : ''; ?>">
			        Services
			    </a>
			</div>
        </form>
    </div>

    <?php if (mysqli_num_rows($result) > 0) { ?>

        <div class="products-container">
            <?php while ($product = mysqli_fetch_assoc($result)) { ?>

                <div class="product-card">
			    <?php $first_image = !empty($product['image']) ? explode(',', $product['image'])[0] : ''; ?>
			    <img src="uploads/<?php echo rawurlencode(trim($first_image)); ?>"
			         alt="<?php echo htmlspecialchars($product['title']); ?>">

                    <div class="product-info">
                        <div class="category">
                            <?php echo htmlspecialchars($product['category_name']); ?>
                        </div>

                        <h3><?php echo htmlspecialchars($product['title']); ?></h3>

                        <p class="description">
                          
						<?php
						    $preview = strip_tags(str_replace(['\r\n', '\r', '\n'], ' ', $product['description']));
						    echo htmlspecialchars(strlen($preview) > 80 ? substr($preview, 0, 80) : $preview);
						    ?>
						
                        </p>

                        <div class="price">
                            R<?php echo number_format($product['price'], 2); ?>
                        </div>

                        <p class="seller">
                            Seller: <?php echo htmlspecialchars($product['full_name']); ?>
                        </p>

                        <div class="card-actions">
                            <form method="POST" action="add_wishlist.php">
                                <?php csrfInput(); ?>
                                <input type="hidden" name="product_id"
                                       value="<?php echo ($product['product_id']); ?>">
                                <button class="wishlist-btn" type="submit">
                                    Wishlist
                                </button>
                            </form>

                            <a class="details-btn"
                               href="product_details.php?id=<?php echo htmlspecialchars($product['product_id']); ?>">
                                View
                            </a>
                        </div>
                    </div>
                </div>

            <?php } ?>
        </div>

    <?php } else { ?>

        <div class="empty-state">
            <h2>No listings yet</h2>
            <p>
			    <?php
			    if ($type_filter === 'services') {
			        echo "Be the first to post a service on CampusLink.";
			    } elseif ($type_filter === 'products') {
			        echo "Be the first to post a product for sale on CampusLink.";
			    } else {
			        echo "Be the first to post something for sale on CampusLink.";
			    }
			    ?>
			</p>
            <br>
            <a class="post-btn" href="add_product.php">Post a listing</a>
        </div>

    <?php } ?>

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

    // Close dropdown when clicking anywhere outside it
    document.addEventListener('click', function(e) {
        var wrap = document.querySelector('.user-menu-wrap');
        if (wrap && !wrap.contains(e.target)) {
            document.getElementById('dropdownMenu').classList.remove('open');
        }
    });
</script>

</body>
</html>