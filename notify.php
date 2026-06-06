<?php
/**
 * notify.php
 * Reusable notification helper for CampusLink.
 * Include this file wherever you need to send notifications.
 *
 * Usage:
 *   include 'notify.php';
 *   sendNotification($conn, $user_id, "Your item sold!");
 */

function sendNotification($conn, $user_id, $message) {
    $stmt = mysqli_prepare($conn,
        "INSERT INTO notifications (user_id, message) VALUES (?, ?)"
    );
    mysqli_stmt_bind_param($stmt, "is", $user_id, $message);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}
