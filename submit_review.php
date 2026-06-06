<?php
include 'auth.php';
include 'db.php';

requireLogin();
requireVerified();
verifyCsrf();

$reviewer_id      = (int) $_SESSION['user_id'];
$reviewed_user_id = (int) $_POST['reviewed_user_id'];
$rating           = (int) $_POST['rating'];
$comment          = $_POST['comment'];

// Clamp rating to valid range
if ($rating < 1 || $rating > 5) {
    header("Location: products.php");
    exit();
}

$stmt = mysqli_prepare($conn, "INSERT INTO reviews (reviewer_id, reviewed_user_id, rating, comment) VALUES (?, ?, ?, ?)");
mysqli_stmt_bind_param($stmt, "iiis", $reviewer_id, $reviewed_user_id, $rating, $comment);
mysqli_stmt_execute($stmt);

header("Location: products.php");
exit();
?>