<?php
include 'auth.php';
include 'db.php';

requireLogin();
requireVerified();

$user_id = $_SESSION['user_id'];

// Get all unique conversation partners
$conv_sql = "
    SELECT 
        CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END AS other_user_id,
        MAX(created_at) AS last_message_time
    FROM messages
    WHERE sender_id = ? OR receiver_id = ?
    GROUP BY other_user_id
    ORDER BY last_message_time DESC
";
$conv_stmt = mysqli_prepare($conn, $conv_sql);
mysqli_stmt_bind_param($conv_stmt, "iii", $user_id, $user_id, $user_id);
mysqli_stmt_execute($conv_stmt);
$conv_result = mysqli_stmt_get_result($conv_stmt);
$conversations = [];
while ($row = mysqli_fetch_assoc($conv_result)) {
    $conversations[] = $row;
}

// Get selected conversation
$selected_user_id = isset($_GET['with']) ? (int)$_GET['with'] : null;
if (!$selected_user_id && !empty($conversations)) {
    $selected_user_id = $conversations[0]['other_user_id'];
}

// Fetch messages for selected conversation
$thread = [];
$other_user = null;
if ($selected_user_id) {
    $msg_sql = "SELECT messages.*, users.full_name
                FROM messages
                JOIN users ON messages.sender_id = users.user_id
                WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
                ORDER BY created_at ASC";
    $msg_stmt = mysqli_prepare($conn, $msg_sql);
    mysqli_stmt_bind_param($msg_stmt, "iiii", $user_id, $selected_user_id, $selected_user_id, $user_id);
    mysqli_stmt_execute($msg_stmt);
    $thread = mysqli_fetch_all(mysqli_stmt_get_result($msg_stmt), MYSQLI_ASSOC);

    // Get other user's name
    $u_stmt = mysqli_prepare($conn, "SELECT full_name FROM users WHERE user_id = ?");
    mysqli_stmt_bind_param($u_stmt, "i", $selected_user_id);
    mysqli_stmt_execute($u_stmt);
    $u_result = mysqli_stmt_get_result($u_stmt);
    $other_user = mysqli_fetch_assoc($u_result);
}

// Get names for conversation list
$conv_names = [];
foreach ($conversations as $conv) {
    $n_stmt = mysqli_prepare($conn, "SELECT full_name FROM users WHERE user_id = ?");
    mysqli_stmt_bind_param($n_stmt, "i", $conv['other_user_id']);
    mysqli_stmt_execute($n_stmt);
    $n_result = mysqli_stmt_get_result($n_stmt);
    $n_row = mysqli_fetch_assoc($n_result);
    $conv_names[$conv['other_user_id']] = $n_row['full_name'] ?? 'Unknown';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inbox — CampusLink</title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f8f1e8; margin: 0; color: #111827; }
        a { text-decoration: none; }

        /* Nav */
        nav { background: #fffaf3; border-bottom: 1px solid #eadfd2; padding: 0 12%; height: 72px; display: flex; align-items: center; justify-content: space-between; }
        .nav-logo { display: flex; align-items: center; gap: 10px; color: #111827; }
        .nav-logo-icon { width: 42px; height: 42px; background: orange; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        .nav-logo-text strong { display: block; font-size: 17px; font-weight: 800; }
        .nav-logo-text span { display: block; font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.06em; }
        .nav-center { display: flex; align-items: center; gap: 8px; }
        .nav-center a { color: #374151; font-size: 15px; font-weight: 500; padding: 10px 18px; border-radius: 14px; }
		.nav-center a.active {
    background: #fff0e5;
    color: orange;
    font-weight: 700;
}
        .nav-center a:hover { background: #fff0e5; color: orange; }
        .nav-right { display: flex; align-items: center; gap: 12px; }
        .sell-btn { background: orange; color: white; padding: 10px 20px; border-radius: 12px; font-weight: 700; box-shadow: 0 2px 6px rgba(0,0,0,0.15); }
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
        .user-menu-wrap { position: relative; }
        .user-icon-btn { width: 40px; height: 40px; background: white; border: 1px solid #eadfd2; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 6px rgba(0,0,0,0.08); cursor: pointer; color: #374151; transition: background 0.15s; }
        .user-icon-btn:hover { background: #fff0e5; }
        .dropdown-menu { display: none; position: absolute; top: calc(100% + 10px); right: 0; background: #fff; border: 1px solid #eadfd2; border-radius: 16px; box-shadow: 0 8px 28px rgba(0,0,0,0.12); min-width: 210px; overflow: hidden; z-index: 500; }
        .dropdown-menu.open { display: block; }
        .dropdown-menu a { display: flex; align-items: center; gap: 12px; padding: 13px 18px; font-size: 14px; font-weight: 500; color: #111827; transition: background 0.12s; }
        .dropdown-menu a:hover { background: #fff8f0; }
        .dropdown-menu svg { width: 16px; height: 16px; flex-shrink: 0; color: #6b7280; }
        .dropdown-menu .sign-out { color: #ef4444; }
        .dropdown-menu .sign-out svg { color: #ef4444; }
        hr.divider { border: none; border-top: 1px solid #eadfd2; margin: 4px 0; }

        /* Layout */
        .inbox-wrap { max-width: 1000px; margin: 40px auto; padding: 0 20px; display: flex; gap: 20px; height: 620px; }

        /* Conversation list */
        .conv-list { width: 280px; flex-shrink: 0; background: #fffaf3; border: 1px solid #eadfd2; border-radius: 20px; overflow-y: auto; }
        .conv-list-header { padding: 18px 20px; font-weight: 700; font-size: 15px; border-bottom: 1px solid #eadfd2; }
        .conv-item { display: flex; align-items: center; gap: 12px; padding: 14px 20px; cursor: pointer; border-bottom: 1px solid #f3ede4; transition: background 0.12s; text-decoration: none; color: #111827; }
        .conv-item:hover { background: #fff0e5; }
        .conv-item.active { background: #fff0e5; border-left: 3px solid orange; }
        .conv-avatar { width: 40px; height: 40px; background: orange; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 800; font-size: 15px; flex-shrink: 0; }
        .conv-name { font-weight: 600; font-size: 14px; }
        .conv-time { font-size: 11px; color: #9ca3af; margin-top: 2px; }

        /* Empty conversations */
        .conv-empty { padding: 32px 20px; text-align: center; color: #9ca3af; font-size: 14px; }

        /* Chat area */
        .chat-area { flex: 1; background: #fffaf3; border: 1px solid #eadfd2; border-radius: 20px; display: flex; flex-direction: column; overflow: hidden; }
        .chat-header { padding: 18px 24px; border-bottom: 1px solid #eadfd2; font-weight: 700; font-size: 16px; }
        .chat-messages { flex: 1; overflow-y: auto; padding: 20px 24px; display: flex; flex-direction: column; gap: 12px; }

        /* Bubbles */
        .bubble-wrap { display: flex; flex-direction: column; max-width: 65%; }
        .bubble-wrap.mine { align-self: flex-end; align-items: flex-end; }
        .bubble-wrap.theirs { align-self: flex-start; align-items: flex-start; }
        .bubble { padding: 12px 16px; border-radius: 18px; font-size: 14px; line-height: 1.5; }
        .bubble.mine { background: #fff0d6; border-radius: 18px 18px 4px 18px; }
        .bubble.theirs { background: white; border: 1px solid #eadfd2; border-radius: 18px 18px 18px 4px; }
        .bubble-name { font-size: 11px; color: #9ca3af; margin-bottom: 4px; font-weight: 600; }
        .bubble-time { font-size: 11px; color: #9ca3af; margin-top: 4px; }

        /* Reply form */
        .reply-form { padding: 16px 24px; border-top: 1px solid #eadfd2; display: flex; gap: 10px; align-items: flex-end; }
        .reply-form textarea { flex: 1; padding: 12px 16px; border: 1.5px solid #e5ddd4; border-radius: 12px; font-size: 14px; font-family: 'Inter', sans-serif; resize: none; outline: none; height: 48px; }
        .reply-form textarea:focus { border-color: orange; }
        .reply-form button { background: orange; color: white; border: none; padding: 12px 20px; border-radius: 12px; font-weight: 700; cursor: pointer; white-space: nowrap; }

        /* No conversation selected */
        .chat-placeholder { flex: 1; display: flex; align-items: center; justify-content: center; flex-direction: column; gap: 12px; color: #9ca3af; }

        /* Footer */
        footer { margin-top: 40px; padding: 25px 12%; background: #fffaf3; border-top: 1px solid #eadfd2; color: #6b7280; display: flex; justify-content: space-between; font-size: 14px; }
        .footer-brand { display: flex; align-items: center; gap: 10px; }
        .footer-logo { width: 30px; height: 30px; background: orange; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 800; font-size: 11px; }

		
		/* Responsive*/
       @media (max-width: 768px) {
		   .cart-link {
			    display: none;
			}
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
		    .sell-btn             { padding: 8px 12px; font-size: 13px; border-radius: 10px; }
		    .user-icon-btn        { width: 36px; height: 36px; }
		
		    .inbox-wrap  { flex-direction: column; height: auto; }
		    .conv-list   { width: 100%; height: 200px; }
		
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

<!-- Nav -->
<nav>
    <a href="index.php" class="nav-logo">
        <div class="nav-logo-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
        <a href="inbox.php" class="active">Messages</a>
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
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </button>
            <div class="dropdown-menu" id="dropdownMenu">
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <a href="admin_dashboard.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
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
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                    </svg>
                    Order history
                </a>
                <a href="inbox.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>
                    </svg>
                    Messages
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

<!-- Inbox -->
<div class="inbox-wrap">

    <!-- Conversation list -->
    <div class="conv-list">
        <div class="conv-list-header">Messages</div>
        <?php if (empty($conversations)): ?>
            <div class="conv-empty">No conversations yet.</div>
        <?php else: ?>
            <?php foreach ($conversations as $conv):
                $other_id   = $conv['other_user_id'];
                $other_name = $conv_names[$other_id] ?? 'Unknown';
                $initial    = strtoupper(substr($other_name, 0, 1));
                $is_active  = ($selected_user_id == $other_id);
                $time       = date('d M', strtotime($conv['last_message_time']));
            ?>
            <a href="inbox.php?with=<?php echo $other_id; ?>"
               class="conv-item <?php echo $is_active ? 'active' : ''; ?>">
                <div class="conv-avatar"><?php echo $initial; ?></div>
                <div>
                    <div class="conv-name"><?php echo htmlspecialchars($other_name); ?></div>
                    <div class="conv-time"><?php echo $time; ?></div>
                </div>
            </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Chat area -->
    <div class="chat-area">
        <?php if ($selected_user_id && $other_user): ?>

            <div class="chat-header">
                <?php echo htmlspecialchars($other_user['full_name']); ?>
            </div>

            <div class="chat-messages" id="chatMessages">
                <?php if (empty($thread)): ?>
                    <div style="text-align:center; color:#9ca3af; margin-top:40px;">No messages yet. Say hello!</div>
                <?php else: ?>
                    <?php foreach ($thread as $msg):
                        $is_mine = ($msg['sender_id'] == $user_id);
                    ?>
                    <div class="bubble-wrap <?php echo $is_mine ? 'mine' : 'theirs'; ?>">
                        <div class="bubble-name"><?php echo $is_mine ? 'You' : htmlspecialchars($msg['full_name']); ?></div>
                        <div class="bubble <?php echo $is_mine ? 'mine' : 'theirs'; ?>">
                            <?php echo htmlspecialchars($msg['message']); ?>
                        </div>
                        <div class="bubble-time"><?php echo date('d M Y, H:i', strtotime($msg['created_at'])); ?></div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <form method="POST" action="send_message.php" class="reply-form">
                <?php csrfInput(); ?>
                <input type="hidden" name="receiver_id" value="<?php echo $selected_user_id; ?>">
                <textarea name="message" placeholder="Write a message..." required></textarea>
                <button type="submit">Send</button>
            </form>

        <?php else: ?>
            <div class="chat-placeholder">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>
                </svg>
                <p>Select a conversation to start messaging</p>
            </div>
        <?php endif; ?>
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
    // Auto scroll to bottom of chat
    const chatMessages = document.getElementById('chatMessages');
    if (chatMessages) chatMessages.scrollTop = chatMessages.scrollHeight;

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
