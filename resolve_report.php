<?php
include 'auth.php';
include 'db.php';
include 'notify.php';

requireAdmin();
verifyCsrf();

if (isset($_POST['id'])) {
    $report_id = (int) $_POST['id'];

    $lookup = mysqli_prepare($conn,
        "SELECT r.reporter_id, p.title
         FROM reports r
         JOIN products p ON r.reported_product_id = p.product_id
         WHERE r.report_id = ?"
    );
    mysqli_stmt_bind_param($lookup, "i", $report_id);
    mysqli_stmt_execute($lookup);
    $lookup_result = mysqli_stmt_get_result($lookup);
    $lookup_row    = mysqli_fetch_assoc($lookup_result);
    mysqli_stmt_close($lookup);

    $stmt = mysqli_prepare($conn, "UPDATE reports SET status = 'resolved' WHERE report_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $report_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if ($lookup_row) {
        $notif_message = "✅ Your report about \"{$lookup_row['title']}\" has been reviewed and marked as resolved by an admin.";
        sendNotification($conn, $lookup_row['reporter_id'], $notif_message);
    }
}

header("Location: admin_dashboard.php");
exit();