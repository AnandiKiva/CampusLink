<?php
include 'auth.php';
include 'db.php';

// Auth guard
requireLogin();


$user_id = $_SESSION['user_id'];

// Fetch logged-in user's data
$user_res = mysqli_query($conn, "SELECT * FROM users WHERE user_id = $user_id");
$user     = mysqli_fetch_assoc($user_res);

// Fetch user's listings
$listings_res = mysqli_query($conn,
    "SELECT p.*, c.category_name
     FROM products p
     JOIN categories c ON p.category_id = c.category_id
     WHERE p.user_id = $user_id
     ORDER BY p.created_at DESC"
);
$listing_count = mysqli_num_rows($listings_res);

// Fetch reviews received
$reviews_res = mysqli_query($conn,
    "SELECT r.*, u.full_name AS reviewer_name
     FROM reviews r
     JOIN users u ON r.reviewer_id = u.user_id
     WHERE r.reviewed_user_id = $user_id
     ORDER BY r.created_at DESC"
);
$review_count = mysqli_num_rows($reviews_res);

// Average rating
$avg_res    = mysqli_query($conn, "SELECT AVG(rating) AS avg FROM reviews WHERE reviewed_user_id = $user_id");
$avg_row    = mysqli_fetch_assoc($avg_res);
$avg_rating = $avg_row['avg'] ? round($avg_row['avg'], 1) : null;

// Avatar initial
$avatar_letter = strtoupper(substr($user['full_name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile — CampusLink</title>
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
		    transition: all 0.15s ease;
		}
		
		.cart-link:hover {
		    background: #fff0e5;
		    color: orange;
		}

        .profile-btn {
            width: 40px;
            height: 40px;
            background: white;
            color: #111827;
            border-radius: 50%;
            border: 1px solid #eadfd2;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
            font-size: 18px;
        }

        .logout-btn {
            color: #6b7280;
            font-size: 14px;
        }

        .logout-btn:hover {
            color: orange;
        }

        /*  Page  */
        .page {
            max-width: 860px;
            margin: 0 auto;
            padding: 45px 20px 60px;
        }

        /*  Profile card  */
        .profile-card {
            background: white;
            border: 1px solid #eadfd2;
            border-radius: 22px;
            padding: 28px 32px;
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 36px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.06);
        }

        .avatar {
            width: 68px;
            height: 68px;
            border-radius: 50%;
            background: #fff0e5;
            color: orange;
            font-size: 28px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .profile-info h2 {
            font-size: 22px;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 6px;
            color: #020617;
        }

        .verified-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: #d1fae5;
            color: #065f46;
            font-size: 12px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 20px;
        }

        .verified-badge svg { width: 13px; height: 13px; }

        .profile-institution {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
            color: #64748b;
        }

        .profile-institution svg { width: 14px; height: 14px; }

        /*  Section  */
        .section { margin-bottom: 40px; }

        .section-title {
            font-size: 22px;
            font-weight: 800;
            color: #020617;
            margin-bottom: 4px;
        }

        .section-sub {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 20px;
        }

        /*  Listings grid  */
        .listings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 20px;
        }

        .listing-card {
            background: white;
            border: 1px solid #eadfd2;
            border-radius: 22px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 4px 14px rgba(0,0,0,0.06);
            transition: box-shadow 0.15s, transform 0.15s;
        }

        .listing-card:hover {
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .listing-img {
            height: 170px;
            background: #f7efe6;
            position: relative;
            overflow: hidden;
        }

        .listing-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .listing-img .no-img {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #d1c4b4;
        }

        .listing-img .no-img svg { width: 32px; height: 32px; }

        .status-pill {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 4px 10px;
            border-radius: 20px;
        }

        .status-pill.available { background: #dcfce7; color: #166534; }
        .status-pill.sold      { background: #fee2e2; color: #991b1b; }

        .listing-body { padding: 16px; }

        .listing-category {
            background: #fff0e5;
            color: orange;
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .listing-title {
            font-size: 16px;
            font-weight: 700;
            color: #020617;
            margin-bottom: 6px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .listing-price {
            font-size: 20px;
            font-weight: 800;
            color: orange;
        }

        /*  Reviews  */
        .reviews-list { display: flex; flex-direction: column; gap: 16px; }

        .review-card {
            background: white;
            border: 1px solid #eadfd2;
            border-radius: 18px;
            padding: 20px 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .review-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .reviewer-name {
            font-size: 15px;
            font-weight: 700;
            color: #020617;
        }

        .star-rating { display: flex; gap: 3px; }
        .star-rating svg { width: 17px; height: 17px; }
        .star-filled { color: orange; }
        .star-empty  { color: #eadfd2; }

        .review-comment {
            font-size: 14px;
            color: #374151;
            line-height: 1.6;
            margin-bottom: 10px;
        }

        .review-date { font-size: 12px; color: #9ca3af; }

        /*  Empty state  */
        .empty { font-size: 14px; color: #9ca3af; padding: 8px 0; }

        /*  Back link  */
        .back-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 40px;
            font-size: 14px;
            font-weight: 700;
            color: orange;
            transition: opacity 0.15s;
        }

        .back-link:hover { opacity: 0.7; }

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
		
		    .profile-card {
		        flex-direction: column;
		        align-items: flex-start;
		        padding: 20px;
		    }
		
		    .profile-info h2 {
		        font-size: 18px;
		    }
		
		    .section-title {
		        font-size: 18px;
		    }
		
		    .listings-grid {
		        grid-template-columns: 1fr 1fr;
		        gap: 14px;
		    }
		
		    .listing-img {
		        height: 130px;
		    }
		
		    .listing-title {
		        font-size: 14px;
		    }
		
		    .listing-price {
		        font-size: 17px;
		    }
		
		    .review-card {
		        padding: 16px;
		    }
		
		    .reviewer-name {
		        font-size: 14px;
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
		    .listings-grid {
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

    <!-- Profile card -->
    <div class="profile-card">
        <div class="avatar"><?php echo $avatar_letter; ?></div>

        <div class="profile-info">
            <h2>
                <?php echo htmlspecialchars($user['full_name']); ?>

                <?php if ($user['is_verified'] == 1): ?>
                <span class="verified-badge">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    Verified
                </span>
                <?php endif; ?>
            </h2>

            <div class="profile-institution">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/>
                    <circle cx="12" cy="10" r="3"/>
                </svg>
                <?php echo htmlspecialchars($user['institution'] ?? 'No institution set'); ?>
            </div>
        </div>
    </div>

    <!-- Listings section -->
    <div class="section">
        <div class="section-title">Listings</div>
        <div class="section-sub">
            <?php echo $listing_count; ?> item<?php echo $listing_count != 1 ? 's' : ''; ?> for sale
        </div>

        <?php if ($listing_count > 0): ?>
        <div class="listings-grid">
            <?php while ($listing = mysqli_fetch_assoc($listings_res)): ?>

            <a href="my_listings.php" class="listing-card">
                <div class="listing-img">
                   <?php 
					    $first_image = !empty($listing['image']) ? explode(',', $listing['image'])[0] : ''; ?>
					<?php if (!empty($first_image)): ?>
					    <img src="uploads/<?php echo rawurlencode(trim($first_image)); ?>"
					         alt="<?php echo htmlspecialchars($listing['title']); ?>">
                    <?php else: ?>
                        <div class="no-img">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="18" height="18" rx="2"/>
                                <circle cx="8.5" cy="8.5" r="1.5"/>
                                <polyline points="21 15 16 10 5 21"/>
                            </svg>
                        </div>
                    <?php endif; ?>

                    <span class="status-pill <?php echo $listing['status']; ?>">
                        <?php echo ucfirst($listing['status']); ?>
                    </span>
                </div>

                <div class="listing-body">
                    <div class="listing-category">
                        <?php echo htmlspecialchars($listing['category_name']); ?>
                    </div>
                    <div class="listing-title">
                        <?php echo htmlspecialchars($listing['title']); ?>
                    </div>
                    <div class="listing-price">
                        R<?php echo number_format($listing['price'], 2); ?>
                    </div>
                </div>
            </a>

            <?php endwhile; ?>
        </div>

        <?php else: ?>
            <p class="empty">No listings yet.</p>
        <?php endif; ?>
    </div>

    <!-- Ratings & reviews section -->
    <div class="section">
        <div class="section-title">Ratings &amp; reviews</div>
        <div class="section-sub">
            <?php if ($avg_rating): ?>
                <?php echo $avg_rating; ?> out of 5 &nbsp;&middot;&nbsp;
                <?php echo $review_count; ?> review<?php echo $review_count != 1 ? 's' : ''; ?>
            <?php else: ?>
                No reviews yet
            <?php endif; ?>
        </div>

        <?php if ($review_count > 0): ?>
        <div class="reviews-list">
            <?php while ($review = mysqli_fetch_assoc($reviews_res)): ?>

            <div class="review-card">
                <div class="review-top">
                    <span class="reviewer-name">
                        <?php echo htmlspecialchars($review['reviewer_name']); ?>
                    </span>

                    <div class="star-rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <?php if ($i <= $review['rating']): ?>
                                <svg class="star-filled" viewBox="0 0 24 24" fill="currentColor">
                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                                </svg>
                            <?php else: ?>
                                <svg class="star-empty" viewBox="0 0 24 24" fill="currentColor">
                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                                </svg>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                </div>

                <?php if (!empty($review['comment'])): ?>
                    <div class="review-comment">
                        <?php echo htmlspecialchars($review['comment']); ?>
                    </div>
                <?php endif; ?>

                <div class="review-date">
                    <?php echo date('d M Y', strtotime($review['created_at'])); ?>
                </div>
            </div>

            <?php endwhile; ?>
        </div>

        <?php else: ?>
            <p class="empty">No reviews yet.</p>
        <?php endif; ?>
    </div>

    <!-- Back to marketplace -->
    <a href="products.php" class="back-link">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="19" y1="12" x2="5" y2="12"/>
            <polyline points="12 19 5 12 12 5"/>
        </svg>
        Back to marketplace
    </a>

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
        var wrap = document.querySelector('.user-menu-wrap');
        if (wrap && !wrap.contains(e.target)) {
            document.getElementById('dropdownMenu').classList.remove('open');
        }
    });
</script>

</body>
</html>