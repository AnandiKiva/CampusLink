<?php
include 'auth.php';
include 'db.php';

// Auth guard
requireLogin();
requireVerified();

$user_id = $_SESSION['user_id'];

// Fetch ONLY logged-in user's products
$sql = "SELECT products.*, categories.category_name
        FROM products
        JOIN categories ON products.category_id = categories.category_id
        WHERE products.user_id = '$user_id'
        ORDER BY products.created_at DESC";

$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Listings — CampusLink</title>
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

        /*  Page  */
        .page {
            max-width: 1200px;
            margin: auto;
            padding: 45px 20px 60px;
        }

        /* Page header */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .page-header h1 {
            font-size: 38px;
            font-weight: 800;
            color: #020617;
            margin: 0;
        }

        .page-header p {
            color: #64748b;
            font-size: 16px;
            margin-top: 6px;
        }

        .new-listing-btn {
            background: orange;
            color: white;
            padding: 13px 22px;
            border-radius: 14px;
            font-weight: 700;
            font-size: 15px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.12);
            white-space: nowrap;
            transition: background 0.15s;
        }

        .new-listing-btn:hover {
            background: #e07b00;
        }

        /*  Products grid  */
        .products-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 24px;
        }

        /*  Product card  */
        .product-card {
            background: white;
            border-radius: 22px;
            overflow: hidden;
            border: 1px solid #eadfd2;
            box-shadow: 0 4px 14px rgba(0,0,0,0.06);
            display: flex;
            flex-direction: column;
            transition: transform 0.15s, box-shadow 0.15s;
        }

        .product-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        }

        .product-card img {
            width: 100%;
            height: 210px;
            object-fit: cover;
            background: #f7efe6;
        }

        .no-image {
            width: 100%;
            height: 210px;
            background: #f7efe6;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #d1c4b4;
        }

        .no-image svg {
            width: 40px;
            height: 40px;
        }

        .product-info {
            padding: 18px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .category {
            background: #fff0e5;
            color: orange;
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        /* Status pill */
        .status-pill {
            display: inline-block;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 4px 10px;
            border-radius: 20px;
            margin-left: 6px;
            vertical-align: middle;
        }

        .status-pill.available { background: #dcfce7; color: #166534; }
        .status-pill.sold      { background: #fee2e2; color: #991b1b; }

        .product-title {
            font-size: 20px;
            font-weight: 700;
            color: #020617;
            margin-bottom: 8px;
        }

        .product-desc {
            color: #64748b;
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 12px;
            flex: 1;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .price {
            color: orange;
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 6px;
        }

        .listed-date {
            font-size: 12px;
            color: #9ca3af;
            margin-bottom: 16px;
        }

        /* Action buttons */
        .buttons {
            display: flex;
            gap: 10px;
            border-top: 1px solid #f3ede4;
            padding-top: 16px;
            margin-top: auto;
        }

        .edit-btn,
        .delete-btn {
            flex: 1;
            padding: 11px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 700;
            text-align: center;
            transition: background 0.15s;
        }

        .edit-btn {
            background: #fff0e5;
            color: orange;
        }

        .edit-btn:hover {
            background: #ffe0c8;
        }

        .delete-btn {
            background: #fef2f2;
            color: #dc2626;
        }

        .delete-btn:hover {
            background: #fee2e2;
        }

        /*  Empty state  */
        .empty-state {
            background: white;
            border: 1px solid #eadfd2;
            border-radius: 22px;
            text-align: center;
            padding: 70px 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        }

        .empty-state h2 {
            font-size: 22px;
            font-weight: 700;
            color: #020617;
            margin-bottom: 8px;
        }

        .empty-state p {
            color: #64748b;
            font-size: 15px;
            margin-bottom: 24px;
        }

        .post-btn {
            display: inline-block;
            background: orange;
            color: white;
            padding: 12px 26px;
            border-radius: 14px;
            font-weight: 700;
            font-size: 15px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.12);
            transition: background 0.15s;
        }

        .post-btn:hover {
            background: #e07b00;
        }

        /*  Footer  */
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

        /*  Responsive  */
        @media (max-width: 900px) {
			.cart-link {
			    display: none;
			}
			
		    body {
		        overflow-x: hidden;
		    }
		
		    nav {
		        height: 64px;
		        padding: 0 16px;
		        flex-direction: row;
		        align-items: center;
		        justify-content: space-between;
		    }
		
		    .nav-logo-text span {
		        display: none;
		    }
		
		    .nav-center {
		        display: none;
		    }
		
		    .nav-right {
		        gap: 8px;
		    }
		
		    .sell-btn {
		        padding: 8px 12px;
		        font-size: 13px;
		    }
		
		    .user-icon-btn {
		        width: 36px;
		        height: 36px;
		    }
		
		    .page {
		        padding: 25px 14px 60px;
		        width: 100%;
		        max-width: 100vw;
		        overflow-x: hidden;
		    }
		
		    .page-header {
		        flex-direction: column;
		        align-items: flex-start;
		    }
		
		    .page-header h1 {
		        font-size: 28px;
		    }
		
		    .new-listing-btn {
		        width: 100%;
		        text-align: center;
		    }
		
		    .products-container {
		        grid-template-columns: 1fr 1fr;
		        gap: 14px;
		    }
		
		    .product-card img,
		    .no-image {
		        height: 150px;
		    }
		
		    .product-title {
		        font-size: 16px;
		    }
		
		    .price {
		        font-size: 19px;
		    }
		
		    footer {
		        flex-direction: column;
		        gap: 10px;
		        text-align: center;
		        align-items: center;
		        padding: 20px;
		    }
		}
		
		@media (max-width: 480px) {
		    .products-container {
		        grid-template-columns: 1fr;
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
        <a href="my_listings.php" class="active">My listings</a>
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

<!--  Page  -->
<div class="page">

    <div class="page-header">
        <div>
            <h1>My listings</h1>
            <p>Manage what you have for sale.</p>
        </div>
        <a href="add_product.php" class="new-listing-btn">+ New listing</a>
    </div>

    <?php if (mysqli_num_rows($result) > 0): ?>

        <div class="products-container">
            <?php while ($product = mysqli_fetch_assoc($result)): ?>

            <div class="product-card">

                <!-- Image -->
                <?php 
				$first_image = !empty($product['image']) ? explode(',', $product['image'])[0] : '';
				?>
				<?php if (!empty($first_image) && file_exists("uploads/" . trim($first_image))): ?>
				    <img src="uploads/<?php echo rawurlencode(trim($first_image)); ?>"
				         alt="<?php echo htmlspecialchars($product['title']); ?>">
				<?php else: ?>
				    <div class="no-image">
				        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
				             stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
				            <rect x="3" y="3" width="18" height="18" rx="2"/>
				            <circle cx="8.5" cy="8.5" r="1.5"/>
				            <polyline points="21 15 16 10 5 21"/>
				        </svg>
				    </div>
				<?php endif; ?>

                <div class="product-info">

                    <div>
                        <span class="category">
                            <?php echo htmlspecialchars($product['category_name']); ?>
                        </span>
                        <span class="status-pill <?php echo $product['status']; ?>">
                            <?php echo ucfirst($product['status']); ?>
                        </span>
                    </div>

                    <div class="product-title">
                        <?php echo htmlspecialchars($product['title']); ?>
                    </div>

                    <p class="product-desc">
                        <?php echo htmlspecialchars($product['description']); ?>
                    </p>

                    <div class="price">
                        R<?php echo number_format($product['price'], 2); ?>
                    </div>

                    <div class="listed-date">
                        Listed <?php echo date('d M Y', strtotime($product['created_at'])); ?>
                    </div>

                    <div class="buttons">
                        <a class="edit-btn"
                           href="edit_product.php?id=<?php echo $product['product_id']; ?>">
                            Edit
                        </a>
                        <form method="POST" action="delete_product.php" onsubmit="return confirm('Are you sure you want to delete this listing?');">
                            <?php csrfInput(); ?>
                            <input type="hidden" name="id" value="<?php echo $product['product_id']; ?>">
                            <button type="submit" class="delete-btn">Delete</button>
                        </form>
                    </div>

                </div>
            </div>

            <?php endwhile; ?>
        </div>

    <?php else: ?>

        <div class="empty-state">
            <h2>Nothing posted yet</h2>
            <p>Make your first listing and start hustling.</p>
            <a href="add_product.php" class="post-btn">Post a listing</a>
        </div>

    <?php endif; ?>

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