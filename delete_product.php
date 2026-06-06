<?php
include 'auth.php';
include 'db.php';

requireLogin();
requireVerified();
verifyCsrf();

$user_id = $_SESSION['user_id'];

if (isset($_POST['id'])) {

    $product_id = (int) $_POST['id'];

    $stmt = mysqli_prepare($conn, "DELETE FROM products WHERE product_id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $product_id, $user_id);
    mysqli_stmt_execute($stmt);

    header("Location: my_listings.php");
    exit();

} else {

    echo "Invalid request.";

}
?>