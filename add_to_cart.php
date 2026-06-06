<?php
include 'auth.php';
include 'db.php';

requireLogin();
requireVerified();
verifyCsrf();

$user_id = (int) $_SESSION['user_id'];
$product_id = (int) $_POST['product_id'];

// Check if already in cart
$stmt = mysqli_prepare($conn, "SELECT * FROM cart WHERE user_id = ? AND product_id = ?");

mysqli_stmt_bind_param($stmt, "ii", $user_id, $product_id);

mysqli_stmt_execute($stmt);

$check = mysqli_stmt_get_result($stmt);

// Insert only if not already there
if (mysqli_num_rows($check) == 0) {

    $stmt = mysqli_prepare($conn, "INSERT INTO cart (user_id, product_id) VALUES (?, ?)");

    mysqli_stmt_bind_param($stmt, "ii", $user_id, $product_id);

    mysqli_stmt_execute($stmt);
}

header("Location: cart.php");
exit();
?>