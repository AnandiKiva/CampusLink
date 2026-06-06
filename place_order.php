<?php

include 'auth.php';
include 'db.php';
include 'notify.php';

requireLogin();
requireVerified();
verifyCsrf();

$user_id = $_SESSION['user_id'];

// Collect form data
$full_name       = mysqli_real_escape_string($conn, $_POST['full_name']);
$email           = mysqli_real_escape_string($conn, $_POST['email']);
$phone           = mysqli_real_escape_string($conn, $_POST['phone']);
$delivery_method = mysqli_real_escape_string($conn, $_POST['delivery_method']);
$delivery_note   = mysqli_real_escape_string($conn, $_POST['delivery_note'] ?? '');
$address         = mysqli_real_escape_string($conn, $_POST['address'] ?? '');
$city            = mysqli_real_escape_string($conn, $_POST['city'] ?? '');
$postal_code     = mysqli_real_escape_string($conn, $_POST['postal_code'] ?? '');
$payment_method  = mysqli_real_escape_string($conn, $_POST['payment_method']);

// Fetch cart items
$stmt = mysqli_prepare($conn, "SELECT cart.quantity, products.price, products.product_id FROM cart JOIN products ON cart.product_id = products.product_id WHERE cart.user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$cart_result = mysqli_stmt_get_result($stmt);
$cart_items  = [];
$total       = 0;

while ($row = mysqli_fetch_assoc($cart_result)) {
    $cart_items[] = $row;
    $total += $row['price'] * $row['quantity'];
}

if (empty($cart_items)) {
    header("Location: cart.php");
    exit();
}

// Payment reference and status
$payment_reference = "PAY-" . strtoupper(uniqid());

if ($payment_method === 'cash') {
    $payment_status = 'pending';
} elseif ($payment_method === 'eft') {
    $payment_status = 'pending - awaiting EFT confirmation';
} elseif ($payment_method === 'snapscan') {
    $payment_status = 'pending - awaiting SnapScan confirmation';
} else {
    $payment_status = 'pending';
}

// Insert order
$stmt = mysqli_prepare($conn, "INSERT INTO orders (user_id, full_name, email, phone, delivery_method, delivery_note, address, city, postal_code, payment_method, total, payment_status, payment_reference) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
mysqli_stmt_bind_param($stmt, "isssssssssdss", $user_id, $full_name, $email, $phone, $delivery_method, $delivery_note, $address, $city, $postal_code, $payment_method, $total, $payment_status, $payment_reference);
mysqli_stmt_execute($stmt);
$order_id = mysqli_stmt_insert_id($stmt);

// Insert order items
$item_stmt = mysqli_prepare($conn, "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");

foreach ($cart_items as $item) {
    $product_id = $item['product_id'];
    $quantity   = $item['quantity'];
    $price      = $item['price'];

    // Insert order item
    mysqli_stmt_bind_param($item_stmt, "iiid", $order_id, $product_id, $quantity, $price);
    mysqli_stmt_execute($item_stmt);

   // Deduct stock
	$qty_int = (int)$quantity;
	$pid_int = (int)$product_id;
	$deduct = mysqli_prepare($conn, "UPDATE products SET quantity = quantity - ? WHERE product_id = ?");
	mysqli_stmt_bind_param($deduct, "ii", $qty_int, $pid_int);
	mysqli_stmt_execute($deduct);

    // Check remaining stock
   	$check_qty = mysqli_prepare($conn, "SELECT quantity, user_id, title FROM products WHERE product_id = ?");
	mysqli_stmt_bind_param($check_qty, "i", $pid_int);
    mysqli_stmt_execute($check_qty);
    $qty_row = mysqli_fetch_assoc(mysqli_stmt_get_result($check_qty));

    // Mark as sold if stock hits 0
    if ($qty_row && $qty_row['quantity'] <= 0) {
        $sold_stmt = mysqli_prepare($conn, "UPDATE products SET status = 'sold' WHERE product_id = ?");
        mysqli_stmt_bind_param($sold_stmt, "i", $product_id);
        mysqli_stmt_execute($sold_stmt);
    }

    // Notify seller (always, not just when stock hits 0)
    if ($qty_row && $qty_row['user_id'] != $user_id) {
        $seller_id     = $qty_row['user_id'];
        $product_title = $qty_row['title'];

        $notif_message = "🎉 Your listing \"$product_title\" was purchased! Check your order history for details.";
        sendNotification($conn, $seller_id, $notif_message);

        $notify_stmt = mysqli_prepare($conn, "INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($notify_stmt, "iis", $user_id, $seller_id, $notif_message);
        mysqli_stmt_execute($notify_stmt);
    }
}

// Clear cart
mysqli_query($conn, "DELETE FROM cart WHERE user_id = '$user_id'");

// Redirect to confirmation
header("Location: order_confirmation.php?order_id=$order_id");
exit();
?>