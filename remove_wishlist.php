<?php
include 'auth.php';
include 'db.php';

requireLogin();
requireVerified();
verifyCsrf();

$user_id = $_SESSION['user_id'];
$product_id = $_POST['product_id'];

mysqli_query($conn, "DELETE FROM wishlist 
WHERE user_id='$user_id' AND product_id='$product_id'");

header("Location: wishlist.php");
?>