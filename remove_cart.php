<?php
include 'auth.php';
include 'db.php';

requireLogin();
verifyCsrf();

$cart_id = (int) $_POST['cart_id'];

$stmt = mysqli_prepare($conn, "DELETE FROM cart WHERE cart_id = ?");

mysqli_stmt_bind_param($stmt, "i", $cart_id);

mysqli_stmt_execute($stmt);

header("Location: cart.php");
exit();
?>