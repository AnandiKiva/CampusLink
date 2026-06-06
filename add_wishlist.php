<?php
include 'auth.php';
include 'db.php';

requireLogin();
requireVerified();
verifyCsrf();

$user_id = $_SESSION['user_id'];
$product_id = $_POST['product_id'];

// Prevent duplicates
$check = mysqli_query($conn, "SELECT * FROM wishlist 
WHERE user_id='$user_id' AND product_id='$product_id'");

if (mysqli_num_rows($check) == 0) {
    $sql = "INSERT INTO wishlist (user_id, product_id)
            VALUES ('$user_id', '$product_id')";
    mysqli_query($conn, $sql);
}

header("Location: products.php");
?>