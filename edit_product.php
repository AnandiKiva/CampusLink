<?php
include 'auth.php';
include 'db.php';

requireLogin();
requireVerified();

$user_id = $_SESSION['user_id'];

// Check if product ID exists
if (!isset($_GET['id'])) {
    die("Product ID missing.");
}

$product_id = (int) $_GET['id'];
$stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE product_id = ? AND user_id = ?");
mysqli_stmt_bind_param($stmt, "ii", $product_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    die("Product not found.");
}

$product = mysqli_fetch_assoc($result);

$message      = "";
$message_type = "success";

// Update product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {

    verifyCsrf();

    $title       = $_POST['title'];
    $description = $_POST['description'];
    $price       = (float) $_POST['price'];
    $category_id = (int) $_POST['category_id'];
    $delivery    = isset($_POST['delivery']) ? implode(',', $_POST['delivery']) : '';
    $image_name  = $product['image']; // keep existing image by default
	$allowed_ext   = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
	$allowed_mimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

	// Handle new image upload
	if (!empty($_FILES['images']['name'][0])) {
	    $new_images = [];
	    $file_count = min(count($_FILES['images']['name']), 3);
	
	    for ($i = 0; $i < $file_count; $i++) {
	        if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) continue;
	
	        $ext = strtolower(pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION));
	
	        if (!in_array($ext, $allowed_ext)) {
	            $message      = "Only JPG, PNG, WEBP or GIF images are allowed.";
	            $message_type = "error";
	            break;
	        }
	
	        if ($_FILES['images']['size'][$i] > 5 * 1024 * 1024) {
	            $message      = "Each image must be under 5MB.";
	            $message_type = "error";
	            break;
	        }
	
	        $finfo      = finfo_open(FILEINFO_MIME_TYPE);
	        $mime       = finfo_file($finfo, $_FILES['images']['tmp_name'][$i]);
	        $image_info = getimagesize($_FILES['images']['tmp_name'][$i]);
	
	        if (!in_array($mime, $allowed_mimes) || $image_info === false) {
	            $message      = "One or more files are not valid images.";
	            $message_type = "error";
	            break;
	        }
	
	        $new_name = uniqid('img_', true) . '.' . $ext;
	        move_uploaded_file($_FILES['images']['tmp_name'][$i], "uploads/" . $new_name);
	        $new_images[] = $new_name;
	    }
	
	    if (!empty($new_images)) {
	        $image_name = implode(',', $new_images);
	    }
	}

    if ($message === "") {
        $stmt = mysqli_prepare($conn, "UPDATE products SET title = ?, description = ?, price = ?, category_id = ?, image = ?, delivery_options = ? WHERE product_id = ? AND user_id = ?");
        mysqli_stmt_bind_param($stmt, "ssdissii", $title, $description, $price, $category_id, $image_name, $delivery, $product_id, $user_id);

        if (mysqli_stmt_execute($stmt)) {
            // Refresh product data
            $stmt2 = mysqli_prepare($conn, "SELECT * FROM products WHERE product_id = ? AND user_id = ?");
            mysqli_stmt_bind_param($stmt2, "ii", $product_id, $user_id);
            mysqli_stmt_execute($stmt2);
            $product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt2));

            $message      = "Listing updated successfully!";
            $message_type = "success";
        } else {
            $message      = "Error updating listing.";
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

// Parse existing delivery options
$existing_delivery = !empty($product['delivery_options']) ? explode(',', $product['delivery_options']) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Listing — CampusLink</title>

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

        .nav-logo-text strong { display: block; font-size: 17px; font-weight: 800; }
        .nav-logo-text span {
            display: block;
            font-size: 11px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .nav-center { display: flex; align-items: center; gap: 8px; }

        .nav-center a {
            color: #374151;
            font-size: 15px;
            font-weight: 500;
            padding: 10px 18px;
            border-radius: 14px;
        }

        .nav-center a:hover  { background: #fff0e5; color: orange; }
        .nav-center a.active { background: #fff0e5; color: orange; font-weight: 700; }

        .nav-right { display: flex; align-items: center; gap: 12px; }

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

        .dropdown-menu a svg { width: 16px; height: 16px; flex-shrink: 0; color: #9ca3af; }

        .dropdown-menu .divider { border: none; border-top: 1px solid #f3ede4; margin: 4px 0; }
        .dropdown-menu .sign-out     { color: #dc2626; }
        .dropdown-menu .sign-out svg { color: #dc2626; }

        /* Page */
        .page {
            max-width: 680px;
            margin: 0 auto;
            padding: 48px 20px 60px;
        }

        /* Form card */
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

        /* Select wrapper */
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

        .delivery-option:hover { border-color: orange; background: #fff8f0; }

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

        /* Current photo + upload */
        .photo-section {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            flex-wrap: wrap;
        }

        .current-photo {
            width: 110px;
            height: 110px;
            border-radius: 14px;
            object-fit: cover;
            border: 1.5px solid #e5ddd4;
        }

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
            font-weight: 500;
            transition: border-color 0.15s, background 0.15s;
            position: relative;
            overflow: hidden;
        }

        .photo-upload:hover { border-color: orange; background: #fff8f0; color: orange; }
        .photo-upload svg { width: 26px; height: 26px; }

        .photo-upload input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }

        .photo-hint {
            font-size: 12px;
            color: #9ca3af;
            margin-top: 8px;
        }

        /* Buttons row */
        .btn-row {
            display: flex;
            gap: 12px;
            margin-top: 10px;
        }

        .cancel-btn {
            flex: 1;
            padding: 16px;
            background: #f7efe6;
            color: #374151;
            border: none;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            text-align: center;
            transition: background 0.15s;
            font-family: inherit;
        }

        .cancel-btn:hover { background: #eee0d0; }

        .submit-btn {
            flex: 2;
            padding: 16px;
            background: orange;
            color: white;
            border: none;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 3px 10px rgba(0,0,0,0.12);
            transition: background 0.15s;
            font-family: inherit;
        }

        .submit-btn:hover { background: #e07b00; }

        /* Footer */
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

        .footer-brand { display: flex; align-items: center; gap: 10px; }

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

        /* Responsive */
      @media (max-width: 768px) {
    nav {
        height: 64px;
        padding: 0 16px;
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
    }
    .nav-logo-text span { display: none; }
    .nav-center         { display: none; }
    .nav-right          { gap: 8px; }
    .sell-btn           { padding: 8px 12px; font-size: 13px; border-radius: 10px; }
    .user-icon-btn      { width: 36px; height: 36px; }
    .nav-logo-icon      { width: 36px; height: 36px; }

    .page        { padding: 25px 14px 60px; }
    .form-card   { padding: 22px; border-radius: 18px; }
    .form-row    { grid-template-columns: 1fr; }
    .delivery-grid { grid-template-columns: 1fr; }
    .delivery-option.full-width { max-width: 100%; }
    .btn-row     { flex-direction: column; }
    footer       { flex-direction: column; gap: 10px; text-align: center; padding: 20px; }
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
        <a href="my_listings.php" class="active">My listings</a>
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
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>
                    </svg>
                    My profile
                </a>
                <a href="account_settings.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
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

					<a href="products.php">
					    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
					         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
					        <circle cx="11" cy="11" r="8"/>
					        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
					    </svg>
					    Browse
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

<!-- Page -->
<div class="page">
    <div class="form-card">

        <h1>Edit listing</h1>
        <p class="subtitle">Update your listing details below.</p>

        <?php if ($message): ?>
            <div class="flash <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <?php csrfInput(); ?>

            <!-- Title -->
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title"
                       value="<?php echo htmlspecialchars($product['title']); ?>"
                       required>
            </div>

            <!-- Description -->
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" required><?php echo htmlspecialchars($product['description']); ?></textarea>
            </div>

            <!-- Category + Price -->
            <div class="form-row">
                <div class="form-group">
                    <label>Category</label>
                    <div class="select-wrap">
                        <select name="category_id" required>
                            <option value="">Pick a category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['category_id']; ?>"
                                    <?php echo ($cat['category_id'] == $product['category_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Price (ZAR)</label>
                    <input type="number" name="price" step="0.01" min="0"
                           value="<?php echo htmlspecialchars($product['price']); ?>"
                           required>
                </div>
            </div>

            <!-- Delivery options -->
            <div class="form-group">
                <label>Delivery options <span style="font-weight:400;color:#9ca3af;font-size:13px;">— Pick all that apply.</span></label>
                <div class="delivery-grid">
                    <label class="delivery-option">
                        <input type="checkbox" name="delivery[]" value="paxi"
                               <?php echo in_array('paxi', $existing_delivery) ? 'checked' : ''; ?>>
                        PAXI collection point
                    </label>
                    <label class="delivery-option">
                        <input type="checkbox" name="delivery[]" value="courier"
                               <?php echo in_array('courier', $existing_delivery) ? 'checked' : ''; ?>>
                        Door-to-door courier
                    </label>
                    <label class="delivery-option">
                        <input type="checkbox" name="delivery[]" value="campus"
                               <?php echo in_array('campus', $existing_delivery) ? 'checked' : ''; ?>>
                        On-campus pickup
                    </label>
                    <label class="delivery-option">
                        <input type="checkbox" name="delivery[]" value="inperson"
                               <?php echo in_array('inperson', $existing_delivery) ? 'checked' : ''; ?>>
                        In-person service
                    </label>
                    <label class="delivery-option full-width">
                        <input type="checkbox" name="delivery[]" value="online"
                               <?php echo in_array('online', $existing_delivery) ? 'checked' : ''; ?>>
                        Online / remote
                    </label>
                </div>
            </div>

            <!-- Photo -->
            <div class="form-group">
                <label>Photo</label>
                <div class="photo-section">
                   <?php if (!empty($product['image'])): ?>
					    <?php $existing_images = array_filter(explode(',', $product['image'])); ?>
					    <?php foreach ($existing_images as $img): ?>
					        <img class="current-photo"
					             src="uploads/<?php echo rawurlencode(trim($img)); ?>"
					             alt="Current photo">
					    <?php endforeach; ?>
					<?php endif; ?>
                    <div>
                        <div class="photo-upload" onclick="document.getElementById('editPhotoInput').click()">
						    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
						         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
						        <polyline points="16 16 12 12 8 16"/>
						        <line x1="12" y1="12" x2="12" y2="21"/>
						        <path d="M20.39 18.39A5 5 0 0018 9h-1.26A8 8 0 103 16.3"/>
						    </svg>
						    <span id="editUploadLabel">Replace photos</span>
						</div>
						<input type="file" name="images[]" id="editPhotoInput" accept="image/*" multiple style="display:none;" onchange="previewEditImages(this)">
						<p class="photo-hint">Leave empty to keep current photos. Hold Ctrl to select up to 3.</p>
                    </div>
                </div>
            </div>

            <!-- Buttons -->
            <div class="btn-row">
                <button type="button" class="cancel-btn" onclick="window.location='my_listings.php'">
                    Cancel
                </button>
                <button type="submit" name="update_product" class="submit-btn">
                    Save changes
                </button>
            </div>

        </form>
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
    function toggleDropdown() {
        document.getElementById('dropdownMenu').classList.toggle('open');
    }

    document.addEventListener('click', function(e) {
        var wrap = document.querySelector('.user-menu-wrap');
        if (wrap && !wrap.contains(e.target)) {
            document.getElementById('dropdownMenu').classList.remove('open');
        }
    });

    // Live preview when a new image is selected
    function previewEditImages(input) {
    if (!input.files || input.files.length === 0) return;

    var files = Array.from(input.files).slice(0, 3);
    var section = document.querySelector('.photo-section');

    // Remove existing previews
    section.querySelectorAll('.current-photo').forEach(function(img) {
        img.remove();
    });

    var insertTarget = section.querySelector('div');

    files.forEach(function(file) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var img = document.createElement('img');
            img.className = 'current-photo';
            img.src = e.target.result;
            section.insertBefore(img, insertTarget);
        };
        reader.readAsDataURL(file);
    });

    document.getElementById('editUploadLabel').textContent =
        files.length + ' photo' + (files.length > 1 ? 's' : '') + ' selected';
	}

</script>

</body>
</html>