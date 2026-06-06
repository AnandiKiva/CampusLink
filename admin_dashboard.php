<?php


include 'auth.php';
include 'db.php';

requireAdmin();

$admin_user_id = $_SESSION['user_id'];

// Unread notification count for bell badge 
$bell_stmt = mysqli_prepare($conn,
    "SELECT COUNT(*) AS cnt FROM notifications WHERE user_id = ? AND is_read = 0"
);
mysqli_stmt_bind_param($bell_stmt, "i", $admin_user_id);
mysqli_stmt_execute($bell_stmt);
$bell_result  = mysqli_stmt_get_result($bell_stmt);
$bell_row     = mysqli_fetch_assoc($bell_result);
$unread_count = (int) ($bell_row['cnt'] ?? 0);
mysqli_stmt_close($bell_stmt);

// Search term 
$search = trim($_GET['search'] ?? '');
$like   = '%' . $search . '%';

//  Queries (all use prepared statements + search filter) 

// Reports
if ($search !== '') {
    $report_stmt = mysqli_prepare($conn, "
        SELECT reports.*,
               reporter.full_name AS reporter_name,
               products.title     AS product_title,
               seller.full_name   AS seller_name
        FROM reports
        JOIN users    reporter ON reports.reporter_id        = reporter.user_id
        JOIN products          ON reports.reported_product_id = products.product_id
        JOIN users    seller   ON products.user_id            = seller.user_id
        WHERE reports.reason LIKE ? OR reports.description LIKE ?
        ORDER BY reports.created_at DESC
    ");
    mysqli_stmt_bind_param($report_stmt, "ss", $like, $like);
} else {
    $report_stmt = mysqli_prepare($conn, "
        SELECT reports.*,
               reporter.full_name AS reporter_name,
               products.title     AS product_title,
               seller.full_name   AS seller_name
        FROM reports
        JOIN users    reporter ON reports.reporter_id        = reporter.user_id
        JOIN products          ON reports.reported_product_id = products.product_id
        JOIN users    seller   ON products.user_id            = seller.user_id
        ORDER BY reports.created_at DESC
    ");
}
mysqli_stmt_execute($report_stmt);
$reports = mysqli_stmt_get_result($report_stmt);

// Users
if ($search !== '') {
    $user_stmt = mysqli_prepare($conn,
        "SELECT * FROM users WHERE full_name LIKE ? OR student_email LIKE ? ORDER BY created_at DESC"
    );
    mysqli_stmt_bind_param($user_stmt, "ss", $like, $like);
} else {
    $user_stmt = mysqli_prepare($conn, "SELECT * FROM users ORDER BY created_at DESC");
}
mysqli_stmt_execute($user_stmt);
$users = mysqli_stmt_get_result($user_stmt);

// Products
if ($search !== '') {
    $product_stmt = mysqli_prepare($conn, "
        SELECT products.*, users.full_name
        FROM products
        JOIN users ON products.user_id = users.user_id
        WHERE products.title LIKE ?
        ORDER BY products.created_at DESC
    ");
    mysqli_stmt_bind_param($product_stmt, "s", $like);
} else {
    $product_stmt = mysqli_prepare($conn, "
        SELECT products.*, users.full_name
        FROM products
        JOIN users ON products.user_id = users.user_id
        ORDER BY products.created_at DESC
    ");
}
mysqli_stmt_execute($product_stmt);
$products = mysqli_stmt_get_result($product_stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CampusLink | Admin Dashboard</title>

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
            padding: 0 5%;
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
        .nav-logo-text span   { display: block; font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.06em; }

        .nav-right { display: flex; align-items: center; gap: 12px; }

        .nav-back-btn {
            background: none;
            border: 1.5px solid #eadfd2;
            color: #374151;
            padding: 8px 18px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .nav-back-btn:hover { background: #fff0e5; border-color: orange; color: orange; }

        /*  Bell icon  */
        .bell-wrap {
            position: relative;
            display: inline-flex;
            align-items: center;
        }

        .bell-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 6px;
            display: flex;
            align-items: center;
            color: #374151;
            border-radius: 10px;
            transition: background 0.15s;
        }

        .bell-btn:hover { background: #fff0e5; color: orange; }

        .bell-badge {
            position: absolute;
            top: 0;
            right: 0;
            background: orange;
            color: white;
            font-size: 10px;
            font-weight: 700;
            border-radius: 999px;
            min-width: 17px;
            height: 17px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 4px;
            pointer-events: none;
        }

        /*  Page layout  */
        .page-wrap {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 24px 60px;
        }

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 32px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .page-title {
            font-size: 26px;
            font-weight: 800;
        }

        /* Search bar  */
        .search-bar-form {
            display: flex;
            gap: 10px;
            width: 100%;
            margin-bottom: 32px;
        }

        .search-bar-form input[type="text"] {
            flex: 1;
            padding: 13px 18px;
            border: 1.5px solid #eadfd2;
            border-radius: 14px;
            font-size: 15px;
            font-family: 'Inter', sans-serif;
            background: white;
            color: #111827;
            outline: none;
            transition: border-color 0.15s;
        }

        .search-bar-form input[type="text"]:focus {
            border-color: orange;
        }

        .search-bar-form button {
            background: orange;
            color: white;
            border: none;
            padding: 13px 24px;
            border-radius: 14px;
            font-size: 15px;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
        }

        .search-bar-form button:hover { background: #e69500; }

        .clear-search {
            background: none;
            border: 1.5px solid #eadfd2;
            color: #6b7280;
            padding: 13px 18px;
            border-radius: 14px;
            font-size: 14px;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            white-space: nowrap;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }

        .clear-search:hover { background: #f3f4f6; }

        .search-notice {
            background: #fff8ee;
            border: 1.5px solid orange;
            border-radius: 14px;
            padding: 12px 18px;
            font-size: 14px;
            color: #92400e;
            margin-bottom: 24px;
            font-weight: 500;
        }

        /*  Sections  */
        .section {
            background: white;
            border-radius: 22px;
            padding: 28px 28px 20px;
            margin-bottom: 32px;
            border: 1.5px solid #eadfd2;
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
        }

        .section h2 {
            font-size: 18px;
            font-weight: 700;
            margin: 0 0 20px;
            color: #111827;
        }

        /*  Tables  */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th {
            text-align: left;
            padding: 10px 12px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #9ca3af;
            border-bottom: 1.5px solid #eadfd2;
        }

        table td {
            padding: 14px 12px;
            font-size: 14px;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: middle;
        }

        table tr:last-child td { border-bottom: none; }

        /*  Badges  */
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
        }

        .badge-admin    { background: #fef3c7; color: #92400e; }
        .badge-user     { background: #f3f4f6; color: #374151; }
        .badge-verified { background: #d1fae5; color: #065f46; }
        .badge-unverified { background: #fee2e2; color: #991b1b; }
        .badge-pending  { background: #fef3c7; color: #92400e; }
        .badge-resolved { background: #d1fae5; color: #065f46; }
        .badge-sold     { background: #ede9fe; color: #5b21b6; }

        /*  Buttons  */
        .btn-danger {
            background: #fee2e2;
            color: #991b1b;
            border: none;
            padding: 7px 14px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
        }

        .btn-danger:hover { background: #fca5a5; }

        .btn-resolve {
            background: #d1fae5;
            color: #065f46;
            border: none;
            padding: 7px 14px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
        }

        .btn-resolve:hover { background: #6ee7b7; }

        /* Edit button  */
        .btn-edit {
            background: #fff0e5;
            color: orange;
            border: 1.5px solid orange;
            padding: 7px 14px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
            margin-right: 6px;
        }

        .btn-edit:hover { background: orange; color: white; }

        /*  Inline edit form  */
        .inline-edit-row td {
            background: #fff8ee;
            border-top: 2px solid orange;
        }

        .inline-edit-form {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: flex-end;
            padding: 4px 0;
        }

        .inline-edit-form label {
            display: flex;
            flex-direction: column;
            gap: 4px;
            font-size: 12px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .inline-edit-form input[type="text"],
        .inline-edit-form select {
            padding: 8px 12px;
            border: 1.5px solid #eadfd2;
            border-radius: 10px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            color: #111827;
            background: white;
            outline: none;
        }

        .inline-edit-form input[type="text"]:focus,
        .inline-edit-form select:focus {
            border-color: orange;
        }

        .inline-edit-form .btn-save {
            background: orange;
            color: white;
            border: none;
            padding: 9px 20px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
        }

        .inline-edit-form .btn-save:hover { background: #e69500; }

        .btn-cancel-edit {
            background: none;
            border: 1.5px solid #eadfd2;
            color: #6b7280;
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
        }

        .btn-cancel-edit:hover { background: #f3f4f6; }

        /*  Empty state  */
        .empty-row td {
            text-align: center;
            color: #9ca3af;
            padding: 32px;
            font-size: 14px;
        }

        /*  Toast  */
        .toast {
            background: #d1fae5;
            border: 1.5px solid #6ee7b7;
            color: #065f46;
            padding: 12px 20px;
            border-radius: 14px;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 20px;
        }

        /*  Footer  */
        footer {
            text-align: center;
            padding: 40px 20px;
            color: #9ca3af;
            font-size: 13px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .footer-brand {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 700;
            color: #374151;
        }

        .footer-logo {
            width: 28px;
            height: 28px;
            background: orange;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 11px;
            font-weight: 800;
        }

        /*  Mobile  */
        @media (max-width: 768px) {
    body {
        overflow-x: hidden;
    }

    nav {
        height: 64px;
        padding: 0 14px;
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

    .nav-back-btn {
        padding: 8px 10px;
        font-size: 13px;
        border-radius: 10px;
    }

    .bell-btn {
        padding: 6px;
    }

    .page-wrap {
        padding: 24px 14px 40px;
        width: 100%;
        max-width: 100vw;
    }

    .section {
        padding: 18px 14px;
        border-radius: 16px;
        overflow-x: auto;
    }

    table {
        display: block;
        overflow-x: auto;
        white-space: nowrap;
    }

    .search-bar-form {
        flex-direction: column;
        gap: 10px;
    }

    .search-bar-form input[type="text"],
    .search-bar-form button,
    .clear-search {
        width: 100%;
    }

    .page-header {
        flex-direction: column;
        align-items: flex-start;
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

    <div class="nav-right">
        <a href="dashboard.php" class="nav-back-btn">← Back to site</a>

        <!--  Bell icon with unread badge -->
        <div class="bell-wrap">
            <a href="notifications.php" class="bell-btn" title="Notifications">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
            </a>
            <?php if ($unread_count > 0): ?>
            <span class="bell-badge"><?php echo $unread_count; ?></span>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!--  Page content  -->
<div class="page-wrap">

    <div class="page-header">
        <div class="page-title" style="display:flex;align-items:center;gap:10px;">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="orange"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="3"/>
                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06
                         a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09
                         A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83
                         l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09
                         A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83
                         l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09
                         a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83
                         l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09
                         a1.65 1.65 0 0 0-1.51 1z"/>
            </svg>
            Admin Dashboard
        </div>
    </div>

    <?php if (isset($_GET['updated'])): ?>
    <div class="toast" style="display:flex;align-items:center;gap:10px;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#065f46"
             stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"/>
        </svg>
        User updated successfully.
    </div>
    <?php endif; ?>

    <!-- Search bar (above all sections) -->
    <form method="GET" action="admin_dashboard.php" class="search-bar-form">
        <input
            type="text"
            name="search"
            placeholder="Search users, products, or reports…"
            value="<?php echo htmlspecialchars($search); ?>"
        >
        <button type="submit">Search</button>
        <?php if ($search !== ''): ?>
        <a href="admin_dashboard.php" class="clear-search">✕ Clear</a>
        <?php endif; ?>
    </form>

    <?php if ($search !== ''): ?>
    <div class="search-notice">
        Showing results for "<strong><?php echo htmlspecialchars($search); ?></strong>" across users, products, and reports.
    </div>
    <?php endif; ?>

    <!--  Users  -->
    <div class="section">
        <h2>All Users</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Institution</th>
                    <th>Role</th>
                    <th>Verified</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $has_users = false;
            while ($user = mysqli_fetch_assoc($users)):
                $has_users = true;
                $uid = $user['user_id'];
            ?>
                <!-- Normal row -->
                <tr id="user-row-<?php echo $uid; ?>">
                    <td><?php echo $uid; ?></td>
                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($user['student_email']); ?></td>
                    <td><?php echo htmlspecialchars($user['institution'] ?? '—'); ?></td>
                    <td>
                        <span class="badge <?php echo $user['role'] === 'admin' ? 'badge-admin' : 'badge-user'; ?>">
                            <?php echo $user['role']; ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge <?php echo $user['is_verified'] ? 'badge-verified' : 'badge-unverified'; ?>">
                            <?php echo $user['is_verified'] ? 'Verified' : 'Unverified'; ?>
                        </span>
                    </td>
                    <td><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                    <td>
                        <!-- Edit button toggles inline form -->
                        <button class="btn-edit" onclick="toggleEdit(<?php echo $uid; ?>)">Edit</button>
                    </td>
                </tr>

                <!-- Inline edit row (hidden by default) -->
                <tr class="inline-edit-row" id="edit-row-<?php echo $uid; ?>" style="display:none;">
                    <td colspan="8">
                        <form method="POST" action="admin_edit_user.php" class="inline-edit-form">
                            <?php csrfInput(); ?>
                            <input type="hidden" name="user_id" value="<?php echo $uid; ?>">

                            <label>
                                Full Name
                                <input
                                    type="text"
                                    name="full_name"
                                    value="<?php echo htmlspecialchars($user['full_name']); ?>"
                                    required
                                    style="min-width:200px;"
                                >
                            </label>

                            <label>
                                Role
                                <select name="role">
                                    <option value="user"  <?php echo $user['role'] === 'user'  ? 'selected' : ''; ?>>User</option>
                                    <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                </select>
                            </label>

                            <label>
                                Verified
                                <select name="is_verified">
                                    <option value="1" <?php echo $user['is_verified'] ? 'selected' : ''; ?>>Yes</option>
                                    <option value="0" <?php echo !$user['is_verified'] ? 'selected' : ''; ?>>No</option>
                                </select>
                            </label>

                            <button type="submit" class="btn-save">Save</button>
                            <button type="button" class="btn-cancel-edit" onclick="toggleEdit(<?php echo $uid; ?>)">Cancel</button>
                        </form>
                    </td>
                </tr>

            <?php endwhile; ?>
            <?php if (!$has_users): ?>
                <tr class="empty-row"><td colspan="8">No users found<?php echo $search ? ' for that search' : ''; ?>.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!--  Products  -->
    <div class="section">
        <h2>All Products</h2>
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Seller</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Listed</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $has_products = false;
            while ($product = mysqli_fetch_assoc($products)):
                $has_products = true;
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($product['title']); ?></td>
                    <td><?php echo htmlspecialchars($product['full_name']); ?></td>
                    <td>R<?php echo number_format($product['price'], 2); ?></td>
                    <td>
                        <span class="badge <?php echo $product['status'] === 'sold' ? 'badge-sold' : 'badge-user'; ?>">
                            <?php echo htmlspecialchars($product['status']); ?>
                        </span>
                    </td>
                    <td><?php echo date('d M Y', strtotime($product['created_at'])); ?></td>
                    <td>
                        <form method="POST" action="admin_delete_product.php"
                              onsubmit="return confirm('Delete this product?');">
                            <?php csrfInput(); ?>
                            <input type="hidden" name="id" value="<?php echo $product['product_id']; ?>">
                            <button type="submit" class="btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
            <?php if (!$has_products): ?>
                <tr class="empty-row"><td colspan="6">No products found<?php echo $search ? ' for that search' : ''; ?>.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!--  Reports  -->
    <div class="section">
        <h2>Fraud Reports</h2>
        <table>
            <thead>
                <tr>
                    <th>Reporter</th>
                    <th>Product</th>
                    <th>Seller</th>
                    <th>Reason</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $has_reports = false;
            while ($report = mysqli_fetch_assoc($reports)):
                $has_reports = true;
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($report['reporter_name']); ?></td>
                    <td><?php echo htmlspecialchars($report['product_title']); ?></td>
                    <td><?php echo htmlspecialchars($report['seller_name']); ?></td>
                    <td><?php echo htmlspecialchars($report['reason']); ?></td>
                    <td style="max-width:200px;white-space:normal;"><?php echo htmlspecialchars($report['description']); ?></td>
                    <td>
                        <span class="badge <?php echo $report['status'] === 'resolved' ? 'badge-resolved' : 'badge-pending'; ?>">
                            <?php echo $report['status']; ?>
                        </span>
                    </td>
                    <td><?php echo date('d M Y', strtotime($report['created_at'])); ?></td>
                    <td>
                        <?php if ($report['status'] === 'pending'): ?>
                        <form method="POST" action="resolve_report.php">
                            <?php csrfInput(); ?>
                            <input type="hidden" name="id" value="<?php echo $report['report_id']; ?>">
                            <button type="submit" class="btn-resolve">Mark Resolved</button>
                        </form>
                        <?php else: ?>
                        <span style="color:#9ca3af;font-size:13px;">Resolved</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            <?php if (!$has_reports): ?>
                <tr class="empty-row"><td colspan="8">No reports found<?php echo $search ? ' for that search' : ''; ?>.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

</div><!-- /page-wrap -->

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
// Toggle inline edit row visibility
function toggleEdit(userId) {
    var editRow = document.getElementById('edit-row-' + userId);
    if (editRow.style.display === 'none') {
        editRow.style.display = 'table-row';
    } else {
        editRow.style.display = 'none';
    }
}
</script>

</body>
</html>
