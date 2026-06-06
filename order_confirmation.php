<?php
include 'auth.php';
include 'db.php';

requireLogin();
requireVerified();

if (!isset($_GET['order_id'])) {
    header("Location: dashboard.php");
    exit();
}

$order_id = $_GET['order_id'];
$user_id  = $_SESSION['user_id'];

// Fetch order details
$order = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM orders WHERE order_id = '$order_id' AND user_id = '$user_id'"));

if (!$order) {
    header("Location: dashboard.php");
    exit();
}

// Fetch order items
$items_result = mysqli_query($conn,
    "SELECT order_items.*, products.title
     FROM order_items
     JOIN products ON order_items.product_id = products.product_id
     WHERE order_items.order_id = '$order_id'");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CampusLink | Order Confirmed</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
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

        .page {
            max-width: 600px;
            margin: 60px auto;
            padding: 20px;
            text-align: center;
        }

        .icon {
            width: 70px;
            height: 70px;
            background: #f0fdf4;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
        }

        h1 { font-size: 32px; margin: 0 0 10px; }

        .subtitle { color: #64748b; margin-bottom: 12px; }

        .order-num {
            font-size: 13px;
            color: #9ca3af;
            margin-bottom: 32px;
        }

        .card {
            background: white;
            border: 1px solid #eadfd2;
            border-radius: 20px;
            padding: 28px;
            text-align: left;
            margin-bottom: 20px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.05);
        }

        .card h2 { font-size: 17px; margin: 0 0 16px; }

        .row {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            color: #374151;
            margin-bottom: 10px;
            gap: 12px;
        }

        .row span:first-child {
            color: #64748b;
            flex-shrink: 0;
        }

        .row span:last-child {
            font-weight: 700;
            text-align: right;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 18px;
            font-weight: 800;
            margin-top: 14px;
            padding-top: 14px;
            border-top: 1px solid #eadfd2;
        }

        .total-row span:last-child { color: orange; }

        .btn {
            display: inline-block;
            background: orange;
            color: white;
            padding: 14px 32px;
            border-radius: 14px;
            font-weight: 700;
            margin-top: 10px;
            margin-bottom: 60px;
        }

        .btn:hover { opacity: 0.9; }

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
            font-size: 11px;
        }

        @media (max-width: 900px) {
            .page {
                padding: 20px 14px;
                margin: 30px auto;
            }

            h1 { font-size: 26px; }

            .card {
                padding: 20px 16px;
            }

            .row {
                flex-direction: column;
                gap: 2px;
                margin-bottom: 14px;
            }

            .row span:last-child {
                text-align: left;
                font-size: 15px;
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

<div class="page">

    <div class="icon">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#22c55e"
             stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"/>
        </svg>
    </div>

    <h1>Order placed!</h1>
    <p class="subtitle">Thanks <?php echo htmlspecialchars($order['full_name']); ?>, your order is confirmed.<br>
    A summary has been sent to <?php echo htmlspecialchars($order['email']); ?>.</p>
    <p class="order-num">Order #<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?></p>

    <!-- Order items -->
    <div class="card">
        <h2>Items ordered</h2>
        <?php while ($item = mysqli_fetch_assoc($items_result)): ?>
            <div class="row">
                <span><?php echo htmlspecialchars($item['title']); ?> x<?php echo $item['quantity']; ?></span>
                <span>R<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
            </div>
        <?php endwhile; ?>
        <div class="total-row">
            <span>Total</span>
            <span>R<?php echo number_format($order['total'], 2); ?></span>
        </div>
    </div>

    <!-- Delivery info -->
    <div class="card">
        <h2>Delivery details</h2>
        <div class="row"><span>Method</span><span><?php echo ucfirst($order['delivery_method']); ?></span></div>
        <div class="row"><span>Payment</span><span><?php echo ucfirst($order['payment_method']); ?></span></div>
        <div class="row"><span>Payment Status</span><span><?php echo ucfirst($order['payment_status']); ?></span></div>
        <div class="row"><span>Payment Reference</span><span><?php echo htmlspecialchars($order['payment_reference']); ?></span></div>
		<?php if ($order['payment_method'] === 'eft'): ?>
		<div style="margin-top:18px; background:#fff8f0; border:1px solid #fcd9a0; border-radius:14px; padding:18px;">
		    <strong style="color:#b45309;">⚠️ Action required – EFT Payment</strong>
		    <p style="margin:8px 0 0; font-size:14px; color:#374151;">
		        Please do an EFT to the seller's bank account and use your payment reference
		        <strong><?php echo htmlspecialchars($order['payment_reference']); ?></strong> as your proof of payment reference.
		        Your order will be confirmed once the seller verifies receipt.
		    </p>
		</div>
		<?php elseif ($order['payment_method'] === 'snapscan'): ?>
		<div style="margin-top:18px; background:#fff8f0; border:1px solid #fcd9a0; border-radius:14px; padding:18px;">
		    <strong style="color:#b45309;">⚠️ Action required – SnapScan Payment</strong>
		    <p style="margin:8px 0 0; font-size:14px; color:#374151;">
		        Please scan the seller's SnapScan QR code and use reference
		        <strong><?php echo htmlspecialchars($order['payment_reference']); ?></strong>.
		        Your order will be confirmed once the seller verifies receipt.
		    </p>
		</div>
		<?php elseif ($order['payment_method'] === 'cash'): ?>
		<div style="margin-top:18px; background:#f0fdf4; border:1px solid #86efac; border-radius:14px; padding:18px;">
		    <strong style="color:#166534;">✓ Cash on collection</strong>
		    <p style="margin:8px 0 0; font-size:14px; color:#374151;">
		        No payment needed right now. Pay the seller in cash when you collect your item.
		        Your order reference is <strong><?php echo htmlspecialchars($order['payment_reference']); ?></strong>.
		    </p>
		</div>
		<?php endif; ?>
		
        <?php if ($order['address']): ?>
            <div class="row"><span>Address</span><span><?php echo htmlspecialchars($order['address']); ?></span></div>
        <?php endif; ?>
        <?php if ($order['delivery_note']): ?>
            <div class="row"><span>Note</span><span><?php echo htmlspecialchars($order['delivery_note']); ?></span></div>
        <?php endif; ?>
    </div>

    <a href="products.php" class="btn">Continue shopping</a>

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

</body>
</html>