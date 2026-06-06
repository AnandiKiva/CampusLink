<?php
include 'auth.php';
include 'db.php';

requireLogin();
requireVerified();
verifyCsrf();

if (isset($_POST['submit_report'])) {
    $reporter_id = (int) $_SESSION['user_id'];
$product_id = (int) $_POST['product_id'];
$reason = $_POST['reason'];
$description = $_POST['description'];

$stmt = mysqli_prepare($conn, "INSERT INTO reports (reporter_id, reported_product_id, reason, description) VALUES (?, ?, ?, ?)");
mysqli_stmt_bind_param($stmt, "iiss", $reporter_id, $product_id, $reason, $description);

if (mysqli_stmt_execute($stmt)) {
        header("Location: product_details.php?id=$product_id&reported=1");
        exit();
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>