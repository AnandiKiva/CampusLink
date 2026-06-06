<?php
include 'auth.php';
include 'db.php';

requireLogin();
requireVerified();
verifyCsrf();

$sender_id = (int) $_SESSION['user_id'];
$receiver_id = (int) $_POST['receiver_id'];

// Verify receiver exists
$check = mysqli_prepare($conn, "SELECT user_id FROM users WHERE user_id = ?");
mysqli_stmt_bind_param($check, "i", $receiver_id);
mysqli_stmt_execute($check);
$result = mysqli_stmt_get_result($check);

if (mysqli_num_rows($result) === 0) {
    header("Location: inbox.php");
    exit();
}

$message = $_POST['message'];

$stmt = mysqli_prepare($conn, "INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");

mysqli_stmt_bind_param($stmt, "iis", $sender_id, $receiver_id, $message);

mysqli_stmt_execute($stmt);

header("Location: inbox.php");
exit();
?>