<?php
include 'db.php';
include 'auth.php';

requireLogin();
requireVerified();
verifyCsrf();

$message      = "";
$message_type = "success";
$unread_count = 0;

if (isset($_POST['add_product']))
{
    $user_id     = $_SESSION['user_id'];
    $category_id = (int) $_POST['category_id'];
    $title       = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $price       = (float) $_POST['price'];
	$quantity = max(1, (int) $_POST['quantity']);
    $delivery = isset($_POST['delivery']) ? implode(',', $_POST['delivery']) : '';
    $delivery = mysqli_real_escape_string($conn, $delivery);

	
// Image upload (up to 3)
$image_names   = [];
$allowed_ext   = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
$allowed_mimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
$upload_error  = false;

if (!empty($_FILES['images']['name'][0])) {
    $file_count = min(count($_FILES['images']['name']), 3);

    for ($i = 0; $i < $file_count; $i++) {
        if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) continue;

        $ext = strtolower(pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed_ext)) {
            $message      = "Only JPG, PNG, WEBP or GIF images are allowed.";
            $message_type = "error";
            $upload_error  = true;
            break;
        }

        if ($_FILES['images']['size'][$i] > 5 * 1024 * 1024) {
            $message      = "Each image must be under 5MB.";
            $message_type = "error";
            $upload_error  = true;
            break;
        }

        $finfo      = finfo_open(FILEINFO_MIME_TYPE);
        $mime       = finfo_file($finfo, $_FILES['images']['tmp_name'][$i]);
        $image_info = getimagesize($_FILES['images']['tmp_name'][$i]);

        if (!in_array($mime, $allowed_mimes) || $image_info === false) {
            $message      = "One or more files are not valid images.";
            $message_type = "error";
            $upload_error  = true;
            break;
        }

        $image_name = uniqid('img_', true) . '.' . $ext;
        move_uploaded_file($_FILES['images']['tmp_name'][$i], __DIR__ . "/uploads/" . $image_name);
        $image_names[] = $image_name;
    }
}

$images_str = implode(',', $image_names);
	
    if ($message === "") {
        $stmt = mysqli_prepare($conn, "INSERT INTO products (user_id, category_id, title, description, price, quantity, image, delivery_options) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
		mysqli_stmt_bind_param($stmt, "iissdiss", $user_id, $category_id, $title, $description, $price, $quantity, $images_str, $delivery);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "Your listing was posted successfully!";
        } else {
            $message      = "Error: " . mysqli_error($conn);
            $message_type = "error";
        }
    }
}

// Fetch categories
$cats_res   = mysqli_query($conn, "SELECT * FROM categories ORDER BY category_name");
$categories = [];
while ($c = mysqli_fetch_assoc($cats_res)) {
    $categories[] = $c;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post a Listing — CampusLink</title>

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

        .nav-center a:hover  { background: #fff0e5; color: orange; }
        .nav-center a.active { background: #fff0e5; color: orange; font-weight: 700; }

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

        /* Dropdown */
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
            max-width: 680px;
            margin: 0 auto;
            padding: 48px 20px 60px;
        }

        /*  Form card  */
        .form-card {
            background: white;
            border-radius: 22px;
            border: 1px solid #eadfd2;
            padding: 40px 44px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        }

        .form-card h1 {
            font-size: 28px;
            font-weight: 800;
            color: #020617;
            margin-bottom: 6px;
        }

        .form-card .subtitle {
            font-size: 15px;
            color: #64748b;
            margin-bottom: 28px;
        }

        /* Flash message */
        .flash {
            padding: 14px 18px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 24px;
            border-left: 4px solid;
        }

        .flash.success { background: #f0fdf4; color: #166534; border-color: #22c55e; }
        .flash.error   { background: #fef2f2; color: #991b1b; border-color: #ef4444; }

        /* Product / Service toggle */
        .type-toggle {
            display: flex;
            gap: 8px;
            margin-bottom: 28px;
        }

        .type-btn {
            padding: 9px 22px;
            border-radius: 20px;
            border: 1.5px solid #e5ddd4;
            background: white;
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            cursor: pointer;
            transition: all 0.15s;
        }

        .type-btn.active {
            background: #111827;
            color: white;
            border-color: #111827;
        }

        /* Form fields */
        .form-group { margin-bottom: 22px; }

        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 8px;
        }

        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 13px 16px;
            border: 1.5px solid #e5ddd4;
            border-radius: 12px;
            font-size: 15px;
            font-family: inherit;
            color: #111827;
            background: white;
            transition: border-color 0.15s;
            appearance: none;
            -webkit-appearance: none;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: orange;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        /* Select wrapper for custom arrow */
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

        /* Two-column row */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        /* Delivery options */
        .delivery-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .delivery-option {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 13px 16px;
            border: 1.5px solid #e5ddd4;
            border-radius: 12px;
            cursor: pointer;
            transition: border-color 0.15s, background 0.15s;
            font-size: 14px;
            color: #374151;
        }

        .delivery-option:hover {
            border-color: orange;
            background: #fff8f0;
        }

        .delivery-option input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: orange;
            cursor: pointer;
            flex-shrink: 0;
        }

        .delivery-option.full-width {
            grid-column: 1 / -1;
            max-width: calc(50% - 5px);
        }

        /* Photo upload */
		.photo-upload {
		    width: 120px;
		    height: 110px;
		    border: 2px dashed #e5ddd4;
		    border-radius: 14px;
		    display: flex;
		    flex-direction: column;
		    align-items: center;
		    justify-content: center;
		    gap: 8px;
		    cursor: pointer;
		    color: #9ca3af;
		    font-size: 13px;
		    transition: border-color 0.15s, background 0.15s;
		    position: relative;
		    overflow: hidden;
		}
		
		.photo-upload:hover {
		    border-color: orange;
		    background: #fff8f0;
		    color: orange;
		}
		
		.photo-upload svg { width: 26px; height: 26px; }
		
		.photo-upload input[type="file"] {
		    position: absolute;
		    inset: 0;
		    opacity: 0;
		    cursor: pointer;
		    width: 100%;
		    height: 100%;
		}

        /* Submit button */
        .submit-btn {
            width: 100%;
            padding: 16px;
            background: orange;
            color: white;
            border: none;
            border-radius: 14px;
            font-size: 17px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.12);
            transition: background 0.15s;
        }

        .submit-btn:hover { background: #e07b00; }

        /* Hidden sections */
        .product-only,
        .service-only { display: none; }

        .product-only.visible,
        .service-only.visible { display: block; }

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

        /*  Responsive  */
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
		
		    .form-card {
		        padding: 24px 18px;
		    }
		
		    .form-card h1 {
		        font-size: 22px;
		    }
		
		    .form-row {
		        grid-template-columns: 1fr;
		    }
		
		    .delivery-grid {
		        grid-template-columns: 1fr;
		    }
		
		    .delivery-option.full-width {
		        max-width: 100%;
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
        <a href="add_product.php" class="sell-btn active">+ Sell</a>
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
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>
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
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/>
                        <rect x="9" y="3" width="6" height="4" rx="1"/>
                    </svg>
                    My listings
                </a>
                <a href="inbox.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
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
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
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
    <div class="form-card">

        <h1>Post a listing</h1>
        <p class="subtitle">Share your hustle with verified students across SA.</p>

        <?php if ($message): ?>
            <div class="flash <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <!-- Product / Service toggle -->
        <div class="type-toggle">
            <button type="button" class="type-btn active" id="btnProduct" onclick="setType('product')">Product</button>
            <button type="button" class="type-btn"        id="btnService" onclick="setType('service')">Service</button>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <?php csrfInput(); ?>
            <input type="hidden" name="listing_type" id="listing_type" value="product">

            <!-- Title -->
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" id="titleInput"
                       placeholder="e.g. ECO 102 textbook (3rd ed)" required>
            </div>

            <!-- Description -->
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" id="descInput"
                          placeholder="Tell buyers about the condition, what's included, and why you're selling."
                          required></textarea>
            </div>

            <!-- Category + Price -->
            <div class="form-row">
                <div class="form-group">
                    <label>Category</label>
                    <div class="select-wrap">
                        <select name="category_id" id="categorySelect" required>
                            <option value="">Pick a category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['category_id']; ?>"
                                        data-type="<?php echo (strtolower($cat['category_name']) === 'services') ? 'service' : 'product'; ?>">
                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Price (ZAR)</label>
						<input type="number" name="price" step="0.01" min="0"
						       placeholder="0" required>
						</div>
						
						<div class="form-group">
						    <label>Quantity Available</label>
						    <input type="number" name="quantity" min="1" max="99"
						           placeholder="1" required>
						    <p class="field-hint">How many of this item do you have?</p>
						</div>
					</div>

            <!-- Condition (Product only) -->
            <div class="form-group product-only visible" id="conditionGroup">
                <label>Condition</label>
                <div class="select-wrap">
                    <select name="condition">
                        <option value="">Pick the condition</option>
                        <option value="new">New</option>
                        <option value="like_new">Like new</option>
                        <option value="good">Good</option>
                        <option value="fair">Fair</option>
                        <option value="for_parts">For parts</option>
                    </select>
                </div>
            </div>

            <!-- Rate type (Service only) -->
            <div class="form-group service-only" id="rateGroup">
                <label>Rate type</label>
                <div class="select-wrap">
                    <select name="rate_type">
                        <option value="">How do you charge?</option>
                        <option value="per_hour">Per hour</option>
                        <option value="per_session">Per session</option>
                        <option value="fixed">Fixed price</option>
                        <option value="negotiable">Negotiable</option>
                    </select>
                </div>
            </div>

            <!-- Delivery options -->
            <div class="form-group">
                <label>Delivery options <span style="font-weight:400;color:#9ca3af;font-size:13px;">— Pick all that apply.</span></label>
                <div class="delivery-grid">
                    <label class="delivery-option">
                        <input type="checkbox" name="delivery[]" value="paxi">
                        PAXI collection point
                    </label>
                    <label class="delivery-option">
                        <input type="checkbox" name="delivery[]" value="courier">
                        Door-to-door courier
                    </label>
                    <label class="delivery-option">
                        <input type="checkbox" name="delivery[]" value="campus">
                        On-campus pickup
                    </label>
                    <label class="delivery-option">
                        <input type="checkbox" name="delivery[]" value="inperson">
                        In-person service
                    </label>
                    <label class="delivery-option full-width">
                        <input type="checkbox" name="delivery[]" value="online">
                        Online / remote
                    </label>
                </div>
            </div>
            

			<!-- Photo upload -->
			<div class="form-group">
			    <label>Photos (up to 3)</label>
			 
			    <div class="photo-upload" onclick="document.getElementById('photoInput').click()">
			        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
			             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
			            <polyline points="16 16 12 12 8 16"/>
			            <line x1="12" y1="12" x2="12" y2="21"/>
			            <path d="M20.39 18.39A5 5 0 0018 9h-1.26A8 8 0 103 16.3"/>
			        </svg>
			        <span id="uploadLabel">Add up to 3 photos (hold Ctrl to select multiple)</span>
			    </div>
			
			   <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple id="photoInput" style="display:none;">
			
			    <div id="previewRow" style="display:flex; gap:10px; margin-top:14px; flex-wrap:wrap;"></div>
			
			    <div id="uploadToast" style="display:none; margin-top:12px; padding:12px 16px; border-radius:12px; font-size:14px; font-weight:600; border-left:4px solid;"></div>
			</div>

            <!-- Submit -->
            <button type="submit" name="add_product" class="submit-btn">
                Post listing
            </button>
        </form>

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
        document.getElementById('dropdownMenu').classList.toggle('open');
    }

    document.addEventListener('click', function(e) {
        var wrap = document.querySelector('.user-menu-wrap');
        if (wrap && !wrap.contains(e.target)) {
            document.getElementById('dropdownMenu').classList.remove('open');
        }
    });

    function setType(type) {
        document.getElementById('listing_type').value = type;

        // Toggle buttons
        document.getElementById('btnProduct').classList.toggle('active', type === 'product');
        document.getElementById('btnService').classList.toggle('active', type === 'service');

        // Toggle fields
        document.querySelectorAll('.product-only').forEach(function(el) {
            el.classList.toggle('visible', type === 'product');
        });
        document.querySelectorAll('.service-only').forEach(function(el) {
            el.classList.toggle('visible', type === 'service');
        });

        // Update placeholders
        var titleInput = document.getElementById('titleInput');
        var descInput  = document.getElementById('descInput');

        if (type === 'product') {
            titleInput.placeholder = 'e.g. ECO 102 textbook (3rd ed)';
            descInput.placeholder  = 'Tell buyers about the condition, what\'s included, and why you\'re selling.';
        } else {
            titleInput.placeholder = 'e.g. Maths tutoring for 1st years';
            descInput.placeholder  = 'What you offer, your experience, where/when you\'re available.';
        }

        // Filter categories to match type
        var opts = document.querySelectorAll('#categorySelect option');
        opts.forEach(function(opt) {
            if (!opt.value) return; // keep placeholder
            var optType = opt.getAttribute('data-type');
            opt.hidden = (type === 'service') ? (optType !== 'service') : (optType === 'service');
        });

        // Reset category if wrong type selected
        var sel = document.getElementById('categorySelect');
        if (sel.selectedOptions[0] && sel.selectedOptions[0].hidden) {
            sel.value = '';
        }
    }

    // Run on load to set initial state
    setType('product');

	document.getElementById('photoInput').addEventListener('change', function() {
    var files   = Array.from(this.files).slice(0, 3);
    var preview = document.getElementById('previewRow');
    var toast   = document.getElementById('uploadToast');
    var label   = document.getElementById('uploadLabel');

    preview.innerHTML  = '';
    toast.style.display = 'none';

    if (files.length === 0) return;

    var hasError = false;

    files.forEach(function(file) {
        if (!file.type.startsWith('image/')) { hasError = true; return; }
        if (file.size > 5 * 1024 * 1024)    { hasError = true; return; }

        var reader = new FileReader();
        reader.onload = function(e) {
            var img       = document.createElement('img');
            img.src       = e.target.result;
            img.style.cssText = 'width:90px;height:80px;object-fit:cover;border-radius:10px;border:1.5px solid #eadfd2;';
            preview.appendChild(img);
        };
        reader.readAsDataURL(file);
    });

    if (hasError) {
        toast.style.background  = '#fef2f2';
        toast.style.color       = '#991b1b';
        toast.style.borderColor = '#ef4444';
        toast.textContent       = 'Some files could not be added. Only images under 5MB are allowed.';
        toast.style.display     = 'block';
        label.textContent       = 'Try again';
    } else {
        toast.style.background  = '#f0fdf4';
        toast.style.color       = '#166534';
        toast.style.borderColor = '#22c55e';
        toast.textContent       = files.length + ' photo' + (files.length > 1 ? 's' : '') + ' ready to upload.';
        toast.style.display     = 'block';
        label.textContent       = files.length + ' photo' + (files.length > 1 ? 's' : '') + ' selected';
    }
});
</script>

</body>
</html>