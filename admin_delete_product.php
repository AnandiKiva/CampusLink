<?php
include 'auth.php';
include 'db.php';

requireAdmin();
verifyCsrf();

if (isset($_POST['id'])) {

    $product_id = (int) $_POST['id'];
    $stmt = mysqli_prepare($conn, "DELETE FROM products WHERE product_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);

    header("Location: admin_dashboard.php");
    exit();
}
?>