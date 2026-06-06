<?php
include 'auth.php';
include 'db.php';

if (!isset($_GET['id'])) {
    die("Product not found.");
}

$product_id = (int) $_GET['id'];

$stmt = mysqli_prepare($conn, "SELECT products.*, users.full_name, users.user_id, users.institution, categories.category_name
        FROM products
        JOIN users ON products.user_id = users.user_id
        JOIN categories ON products.category_id = categories.category_id
        WHERE products.product_id = ?");
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    die("Product not found.");
}

$product = mysqli_fetch_assoc($result);

// Parse images
$all_images = !empty($product['image']) ? array_values(array_filter(explode(',', $product['image']))) : [];
$img_count  = count($all_images);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CampusLink | <?php echo htmlspecialchars($product['title']); ?></title>
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
			color: orange; }

        /*  Dropdown  */
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
            max-width: 1150px;
            margin: auto;
            padding: 45px 20px 70px;
        }

        .back-link {
            color: orange;
            font-weight: 700;
            margin-bottom: 22px;
            display: inline-block;
        }

        .details-grid {
            display: grid;
            grid-template-columns: 1fr 0.9fr;
            gap: 30px;
            align-items: start;
        }

        /*  Image card / Gallery  */
        .image-card {
		    background: white;
		    border: 1px solid #eadfd2;
		    border-radius: 25px;
		    box-shadow: 0 4px 14px rgba(0,0,0,0.06);
		    padding: 18px;
		    max-width: 100%;
		}


        .gallery-wrap {
		    position: relative;
		    border-radius: 18px;
		    overflow: hidden;
		    background: #f1f1f1;
		    max-width: 100%;
		}


        .gallery-track {
            display: flex;
            transition: transform 0.38s cubic-bezier(.4,0,.2,1);
        }

       .gallery-slide {
		    min-width: 100%;
		    height: 380px;
		    object-fit: cover;
		    object-position: center;
		    flex-shrink: 0;
		    display: block;
		}

		.details-grid {
	    display: grid;
	    grid-template-columns: 50% 1fr;
	    gap: 30px;
	    align-items: start;
	}
        /* Arrows */
        .gallery-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 42px;
            height: 42px;
            background: white;
            border: 1px solid #eadfd2;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #374151;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: background 0.15s, color 0.15s, border-color 0.15s;
            z-index: 10;
            padding: 0;
        }

        .gallery-arrow svg {
            width: 18px;
            height: 18px;
            pointer-events: none;
        }

        .gallery-arrow.left  { left: 12px; }
        .gallery-arrow.right { right: 12px; }

        .gallery-arrow:hover {
            background: #fff0e5;
            color: orange;
            border-color: orange;
        }

        /* Dots */
        .gallery-dots {
            position: absolute;
            bottom: 14px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 7px;
            z-index: 10;
        }

        .gallery-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(255,255,255,0.45);
            transition: background 0.3s;
            cursor: pointer;
        }

        .gallery-dot.active {
            background: orange;
        }

        .gallery-dot.fading {
            transition: background 0.3s, opacity 1.2s ease;
            opacity: 0;
        }

        /*  Info / Action cards  */
        .info-card,
        .action-card {
            background: white;
            border: 1px solid #eadfd2;
            border-radius: 24px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.06);
            padding: 28px;
        }

        .action-card { margin-top: 20px; }

        .category {
            background: #fff0e5;
            color: orange;
            display: inline-block;
            padding: 7px 14px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 800;
            margin-bottom: 14px;
        }

        h1 {
            font-size: 34px;
            margin: 0 0 12px;
            color: #020617;
            font-weight: 800;
        }

        .description {
            color: #64748b;
            line-height: 1.6;
            font-size: 16px;
            margin-bottom: 20px;
        }

        .price {
            color: orange;
            font-size: 34px;
            font-weight: 900;
            margin: 20px 0;
        }

        .seller-box {
            background: #f8f1e8;
            border-radius: 14px;
            padding: 14px 16px;
            margin: 16px 0;
        }

        .seller-box strong {
            display: block;
            margin-bottom: 6px;
            font-size: 13px;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .button-row {
            display: flex;
            gap: 12px;
            margin-top: 18px;
        }

        .button-row form { flex: 1; }

        .action-card h2 {
            margin-top: 0;
            font-size: 22px;
            font-weight: 800;
        }

        textarea,
        select {
            width: 100%;
            padding: 13px 16px;
            border: 1.5px solid #e5ddd4;
            border-radius: 12px;
            font-size: 15px;
            font-family: inherit;
            outline: none;
            margin-bottom: 14px;
            color: #111827;
            background: white;
        }

        textarea { height: 110px; resize: vertical; }

        textarea:focus,
        select:focus { border-color: orange; }

        /* Buttons */
        .btn {
            width: 100%;
            padding: 14px;
            border-radius: 14px;
            border: none;
            font-weight: 800;
            cursor: pointer;
            font-size: 15px;
            font-family: inherit;
        }

        .btn-primary  { background: orange; color: white; }
        .btn-secondary { background: #fff0e5; color: orange; }

        .btn:hover { opacity: 0.9; }

        /*  Footer  */
        footer {
            margin-top: 60px;
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
            width: 28px;
            height: 28px;
            background: orange;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 10px;
        }

        /*  Responsive  */
     	 @media (max-width: 768px) {
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
		
		    .nav-right {
		        gap: 8px;
		    }
		
		    .sell-btn {
		        padding: 8px 12px;
		        font-size: 13px;
		        border-radius: 10px;
		    }
		
		    .cart-link {
		        padding: 8px 12px;
		        font-size: 13px;
		        border-radius: 10px;
		    }
		
		    .user-icon-btn {
		        width: 36px;
		        height: 36px;
		    }
		
		    .page {
		        padding: 16px 12px 60px;
		        width: 100%;
		        max-width: 100vw;
		        overflow-x: hidden;
		    }
		
		    .details-grid {
		        grid-template-columns: 1fr;
		        width: 100%;
		        max-width: 100%;
		    }
		
		    .image-card,
		    .info-card,
		    .action-card {
		        width: 100%;
		        max-width: 100%;
		        margin-left: 0;
		        margin-right: 0;
		        padding: 16px;
		        border-radius: 18px;
		        overflow-x: hidden;
		    }
		
		    .action-card {
		        margin-top: 16px;
		    }
		
		    .gallery-wrap {
		        width: 100%;
		        max-width: 100%;
		    }
		
		    .gallery-slide {
		        height: 240px;
		        width: 100%;
		        max-width: 100%;
		    }
		
		    h1 {
		        font-size: 22px;
		    }
		
		    .price {
		        font-size: 24px;
		    }
		
		    .action-card h2 {
		        font-size: 17px;
		    }
		
		    .button-row {
		        flex-direction: column;
		    }
		
		    .button-row form {
		        width: 100%;
		    }
		
		    textarea,
		    select {
		        width: 100%;
		        max-width: 100%;
		        font-size: 14px;
		    }
		
		    .btn {
		        padding: 12px;
		        font-size: 14px;
		    }
		
		    footer {
		        flex-direction: column;
		        text-align: center;
		        gap: 12px;
		        align-items: center;
		        padding: 20px;
		    }
		}
    </style>
</head>
<body>

<!-- Nav -->
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
                        <path d="M12 20h9"/>
                        <path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"/>
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

<!-- Page -->
<div class="page">

    <a href="products.php" class="back-link">← Back to marketplace</a>

    <div class="details-grid">

        <!-- Image / Gallery -->
        <div class="image-card">
            <div class="gallery-wrap" id="gallery">

                <div class="gallery-track" id="galleryTrack">
                    <?php if (!empty($all_images)): ?>
                        <?php foreach ($all_images as $img): ?>
                            <img class="gallery-slide"
                                 src="uploads/<?php echo rawurlencode(trim($img)); ?>"
                                 alt="<?php echo htmlspecialchars($product['title']); ?>">
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="gallery-slide" style="background:#f1f1f1;display:flex;align-items:center;justify-content:center;color:#9ca3af;font-size:14px;">
                            No image uploaded
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($img_count > 1): ?>
                <!-- Left arrow -->
                <button class="gallery-arrow left" onclick="galleryMove(-1)" aria-label="Previous image">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="15 18 9 12 15 6"/>
                    </svg>
                </button>

                <!-- Right arrow -->
                <button class="gallery-arrow right" onclick="galleryMove(1)" aria-label="Next image">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9 18 15 12 9 6"/>
                    </svg>
                </button>

                <!-- Dots -->
                <div class="gallery-dots" id="galleryDots">
                    <?php for ($i = 0; $i < $img_count; $i++): ?>
                        <span class="gallery-dot <?php echo $i === 0 ? 'active' : ''; ?>"
                              onclick="galleryGoTo(<?php echo $i; ?>)"></span>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>

            </div>
        </div>

        <!-- Right column -->
        <div>

            <!-- Info card -->
            <div class="info-card">

                <div class="category">
                    <?php echo htmlspecialchars($product['category_name']); ?>
                </div>

                <h1><?php echo htmlspecialchars($product['title']); ?></h1>

                <p class="description">
				   <?php echo nl2br(htmlspecialchars(str_replace('\r\n', "\n", $product['description']))); ?>
				</p>

                <div class="price">R<?php echo number_format($product['price'], 2); ?></div>

                <div class="seller-box">
				    <strong>Seller</strong>
				    <div style="display:flex;align-items:center;gap:6px;font-size:15px;font-weight:600;color:#111827;">
				        <?php echo htmlspecialchars($product['full_name']); ?>
				    </div>
				    <?php if (!empty($product['institution'])): ?>
				    <div style="display:flex;align-items:center;gap:6px;margin-top:6px;font-size:13px;color:#6b7280;">
				        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
				             stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
				            <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
				            <path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"/>
				        </svg>
				        <?php echo htmlspecialchars($product['institution']); ?>
				    </div>
				    <?php endif; ?>
				</div>

                <?php
                $delivery_labels = [
                    'paxi'     => 'PAXI collection point',
                    'courier'  => 'Door-to-door courier',
                    'campus'   => 'On-campus pickup',
                    'inperson' => 'In-person',
                    'online'   => 'Online / remote',
                ];

                $delivery_icons = [
                    'paxi'     => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>',
                    'courier'  => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>',
                    'campus'   => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
                    'inperson' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
                    'online'   => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 010 20M12 2a15.3 15.3 0 000 20"/></svg>',
                ];

                $offered = !empty($product['delivery_options'])
                         ? explode(',', $product['delivery_options'])
                         : [];
                ?>

                <?php if (!empty($offered)): ?>
                    <div class="seller-box">
                        <strong>Delivery options</strong>
                        <?php foreach ($offered as $opt): ?>
                            <?php $opt = trim($opt); if (isset($delivery_labels[$opt])): ?>
                                <div style="display:flex;align-items:center;gap:8px;margin-top:8px;font-size:14px;color:#374151;">
                                    <?php echo $delivery_icons[$opt]; ?>
                                    <?php echo $delivery_labels[$opt]; ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Add to cart + Wishlist -->
                <div class="button-row">
                    <form method="POST" action="add_to_cart.php">
                        <?php csrfInput(); ?>
                        <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                        <input type="hidden" name="delivery_options" value="<?php echo htmlspecialchars($product['delivery_options']); ?>">
                        <button class="btn btn-primary" type="submit">Add to cart</button>
                    </form>

                    <form method="POST" action="add_wishlist.php">
                        <?php csrfInput(); ?>
                        <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                        <button class="btn btn-secondary" type="submit">Add to wishlist</button>
                    </form>
                </div>

            </div>

            <!-- Message seller -->
            <div class="action-card">
                <h2>Message seller</h2>
                <form method="POST" action="send_message.php">
                    <?php csrfInput(); ?>
                    <input type="hidden" name="receiver_id" value="<?php echo $product['user_id']; ?>">
                    <textarea name="message" placeholder="Ask about availability, delivery or price..." required></textarea>
                    <button class="btn btn-primary" type="submit">Send message</button>
                </form>
            </div>

            <!-- Leave a review -->
            <div class="action-card">
                <h2>Leave a review</h2>
                <form method="POST" action="submit_review.php">
                    <?php csrfInput(); ?>
                    <input type="hidden" name="reviewed_user_id" value="<?php echo $product['user_id']; ?>">
                    <select name="rating" required>
                        <option value="">Select rating</option>
                        <option value="1">1 star</option>
                        <option value="2">2 stars</option>
                        <option value="3">3 stars</option>
                        <option value="4">4 stars</option>
                        <option value="5">5 stars</option>
                    </select>
                    <textarea name="comment" placeholder="Share your experience with this seller..." required></textarea>
                    <button class="btn btn-primary" type="submit">Submit review</button>
                </form>
            </div>

            <!-- Report -->
            <div class="action-card">
                <h2>Report suspicious activity</h2>
                <form method="POST" action="report_product.php">
                    <?php csrfInput(); ?>
                    <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                    <input type="hidden" name="reported_user_id" value="<?php echo $product['user_id']; ?>">
                    <select name="reason" required>
                        <option value="">Select reason</option>
                        <option value="Fake listing">Fake listing</option>
                        <option value="Scam attempt">Scam attempt</option>
                        <option value="Suspicious seller">Suspicious seller</option>
                        <option value="Inappropriate content">Inappropriate content</option>
                        <option value="Other">Other</option>
                    </select>
                    <textarea name="description" placeholder="Explain what happened..." required></textarea>
                    <button class="btn btn-primary" type="submit" name="submit_report">Submit report</button>
                </form>
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
    //  Dropdown 
    function toggleDropdown() {
        document.getElementById('dropdownMenu').classList.toggle('open');
    }

    document.addEventListener('click', function(e) {
        var wrap = document.querySelector('.user-menu-wrap');
        if (wrap && !wrap.contains(e.target)) {
            document.getElementById('dropdownMenu').classList.remove('open');
        }
    });

    //  Gallery 
    var galleryCurrent = 0;
    var galleryTotal   = <?php echo max($img_count, 1); ?>;
    var dotFadeTimer   = null;

    function galleryMove(dir) {
        galleryCurrent = (galleryCurrent + dir + galleryTotal) % galleryTotal;
        applyGallery();
    }

    function galleryGoTo(index) {
        galleryCurrent = index;
        applyGallery();
    }

    function applyGallery() {
        // Slide
        document.getElementById('galleryTrack').style.transform =
            'translateX(-' + (galleryCurrent * 100) + '%)';

        // Dots
        var dots = document.querySelectorAll('.gallery-dot');
        dots.forEach(function(d) {
            d.classList.remove('active', 'fading');
            d.style.opacity = '';
        });

        if (dots[galleryCurrent]) {
            dots[galleryCurrent].classList.add('active');
        }

        // Clear existing fade timer
        if (dotFadeTimer) clearTimeout(dotFadeTimer);

        // Fade active dot after 8 seconds
        dotFadeTimer = setTimeout(function() {
            var activeDot = document.querySelector('.gallery-dot.active');
            if (activeDot) {
                activeDot.classList.add('fading');
            }
        }, 8000);
    }

    // Kick off fade timer on page load if there are multiple images
    if (galleryTotal > 1) {
        applyGallery();
    }
</script>

</body>
</html>