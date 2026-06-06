<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'auth.php';
include 'db.php';

requireLogin();
verifyCsrf();

$order_id = (int) $_POST['order_id'];
$user_id  = $_SESSION['user_id'];

// Make sure this order belongs to the logged-in user and is still pending
$stmt = mysqli_prepare($conn, "SELECT order_id FROM orders WHERE order_id = ? AND user_id = ? AND payment_status = 'pending'");
mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 1) {
    // Cancel the order
    $upd = mysqli_prepare($conn, "UPDATE orders SET payment_status = 'cancelled' WHERE order_id = ?");
    mysqli_stmt_bind_param($upd, "i", $order_id);
    mysqli_stmt_execute($upd);

    // Re-activate the products so they show up for sale again
    $items = mysqli_prepare($conn, "SELECT product_id, quantity FROM order_items WHERE order_id = ?");
    mysqli_stmt_bind_param($items, "i", $order_id);
    mysqli_stmt_execute($items);
    $items_result = mysqli_stmt_get_result($items);
    while ($item = mysqli_fetch_assoc($items_result)) {
		
    // Restore quantity
    $restore_qty = mysqli_prepare($conn, "UPDATE products SET quantity = quantity + ? WHERE product_id = ?");
    mysqli_stmt_bind_param($restore_qty, "ii", $item['quantity'], $item['product_id']);
    mysqli_stmt_execute($restore_qty);

    // If product was marked sold, set back to active
    $restore_status = mysqli_prepare($conn, "UPDATE products SET status = 'active' WHERE product_id = ? AND status = 'sold'");
    mysqli_stmt_bind_param($restore_status, "i", $item['product_id']);
    mysqli_stmt_execute($restore_status);
	}
}

header("Location: order_history.php");
exit();
?>