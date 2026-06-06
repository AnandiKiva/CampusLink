<?php
include 'auth.php';
include 'db.php';

requireLogin();
verifyCsrf();

$cart_id = (int) $_POST['id'];
$action  = $_POST['action'];
$user_id = $_SESSION['user_id'];

// Get current cart quantity and the product's available quantity
$stmt = mysqli_prepare($conn, "SELECT cart.quantity, products.quantity AS max_qty 
                                FROM cart 
                                JOIN products ON cart.product_id = products.product_id
                                WHERE cart.cart_id = ? AND cart.user_id = ?");
mysqli_stmt_bind_param($stmt, "ii", $cart_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);

if ($row) {
    $new_qty = $action === 'increase' ? $row['quantity'] + 1 : $row['quantity'] - 1;

    if ($new_qty <= 0) {
        // Remove from cart
        $del = mysqli_prepare($conn, "DELETE FROM cart WHERE cart_id = ? AND user_id = ?");
        mysqli_stmt_bind_param($del, "ii", $cart_id, $user_id);
        mysqli_stmt_execute($del);
    } elseif ($new_qty > $row['max_qty']) {
        // Cap at seller's quantity — do nothing, just redirect
    } else {
        // Update quantity
        $upd = mysqli_prepare($conn, "UPDATE cart SET quantity = ? WHERE cart_id = ? AND user_id = ?");
        mysqli_stmt_bind_param($upd, "iii", $new_qty, $cart_id, $user_id);
        mysqli_stmt_execute($upd);
    }
}

header("Location: cart.php");
exit();
?>