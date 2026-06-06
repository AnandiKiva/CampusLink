<?php

include 'auth.php';
include 'db.php';

requireLogin();

$user_id = $_SESSION['user_id'];

// Fetch all notifications for this user (newest first)
$stmt = mysqli_prepare($conn,
    "SELECT notification_id, message, is_read, created_at
     FROM notifications
     WHERE user_id = ?
     ORDER BY created_at DESC"
);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result        = mysqli_stmt_get_result($stmt);
$notifications = [];
while ($row = mysqli_fetch_assoc($result)) {
    $notifications[] = $row;
}
mysqli_stmt_close($stmt);

// Capture unread count BEFORE marking as read (so badge still shows on this load)
$unread_count = 0;
foreach ($notifications as $n) {
    if ($n['is_read'] == 0) $unread_count++;
}

// Mark all as read
$mark = mysqli_prepare($conn,
    "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0"
);
mysqli_stmt_bind_param($mark, "i", $user_id);
mysqli_stmt_execute($mark);
mysqli_stmt_close($mark);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CampusLink | Notifications</title>

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

        /* ── Nav ── */
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
        .nav-logo-text span   { display: block; font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.06em; }

        .nav-center { display: flex; align-items: center; gap: 8px; }
        .nav-center a { color: #374151; font-size: 15px; font-weight: 500; padding: 10px 18px; border-radius: 14px; }
        .nav-center a:hover { background: #fff0e5; color: orange; }

        .nav-right { display: flex; align-items: center; gap: 12px; }

        .sell-btn {
            background: orange;
            color: white;
            padding: 10px 20px;
            border-radius: 12px;
            font-weight: 700;
            box-shadow: 0 2px 6px rgba(0,0,0,0.12);
        }

        /*  Bell  */
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
            text-decoration: none;
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

        /*  User dropdown  */
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
            z-index: 999;
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

        /*  Page content  */
        .page-wrap {
            max-width: 720px;
            margin: 48px auto;
            padding: 0 20px;
        }

        .page-title {
            font-size: 26px;
            font-weight: 800;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .notif-card {
            background: white;
            border-radius: 22px;
            padding: 20px 24px;
            margin-bottom: 14px;
            border: 1.5px solid #eadfd2;
            display: flex;
            align-items: flex-start;
            gap: 16px;
        }

        .notif-card.unread {
            background: #fff8ee;
            border-color: orange;
            box-shadow: 0 2px 12px rgba(255,165,0,0.10);
        }

        .notif-icon {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #fff0e5;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: orange;
        }

        .notif-icon.unread-icon { background: orange; color: white; }

        .notif-body { flex: 1; }

        .notif-message {
            font-size: 15px;
            font-weight: 500;
            color: #111827;
            margin: 0 0 6px;
        }

        .notif-time {
            font-size: 12px;
            color: #9ca3af;
        }

        .unread-dot {
            width: 9px;
            height: 9px;
            background: orange;
            border-radius: 50%;
            margin-top: 6px;
            flex-shrink: 0;
        }

        .empty-state {
            text-align: center;
            padding: 64px 20px;
            color: #9ca3af;
        }

        .empty-state p { font-size: 16px; margin: 8px 0 0; }

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
        }
			body {
		    display: flex;
		    flex-direction: column;
		    min-height: 100vh;
		}
		
		.page-wrap {
		    flex: 1;
		}

        /*  Mobile  */
        .mobile-only { display: none; }

		@media (max-width: 1000px) {
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
        <a href="add_product.php" class="sell-btn">+ Sell</a>

        <!-- Bell icon -->
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

        <!-- User dropdown -->
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

<!--  Page content  -->
<div class="page-wrap">

    <div class="page-title">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="orange"
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
        </svg>
        Notifications
    </div>

    <?php if (empty($notifications)): ?>
    <div class="empty-state">
        <svg width="52" height="52" viewBox="0 0 24 24" fill="none"
             stroke="#d1d5db" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
        </svg>
        <p>You're all caught up — no notifications yet.</p>
    </div>
    <?php else: ?>

    <?php foreach ($notifications as $n): ?>
        <?php $isUnread = ($n['is_read'] == 0); ?>
        <div class="notif-card <?php echo $isUnread ? 'unread' : ''; ?>">

            <div class="notif-icon <?php echo $isUnread ? 'unread-icon' : ''; ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
            </div>

            <div class="notif-body">
                <p class="notif-message"><?php echo htmlspecialchars($n['message']); ?></p>
                <span class="notif-time"><?php echo date('d M Y, H:i', strtotime($n['created_at'])); ?></span>
            </div>

            <?php if ($isUnread): ?>
            <div class="unread-dot"></div>
            <?php endif; ?>

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
    var wrap = document.querySelector('.user-menu-wrap');
    if (wrap && !wrap.contains(e.target)) {
        document.getElementById('dropdownMenu').classList.remove('open');
    }
});
</script>

</body>
</html>
