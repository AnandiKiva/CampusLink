<?php
/**
 * admin_edit_user.php
 * Handles the admin edit-user form submission.
 * Validates CSRF, sanitises inputs, updates the users table,
 * then redirects back to the admin dashboard.
 */

include 'auth.php';
include 'db.php';

requireAdmin();
verifyCsrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin_dashboard.php");
    exit();
}

$user_id    = (int) ($_POST['user_id'] ?? 0);
$full_name  = trim($_POST['full_name']  ?? '');
$role       = in_array($_POST['role'] ?? '', ['user', 'admin']) ? $_POST['role'] : 'user';
$is_verified = isset($_POST['is_verified']) && $_POST['is_verified'] === '1' ? 1 : 0;

if ($user_id <= 0 || $full_name === '') {
    header("Location: admin_dashboard.php?error=invalid");
    exit();
}

$stmt = mysqli_prepare($conn,
    "UPDATE users SET full_name = ?, role = ?, is_verified = ? WHERE user_id = ?"
);
mysqli_stmt_bind_param($stmt, "ssii", $full_name, $role, $is_verified, $user_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

header("Location: admin_dashboard.php?updated=1");
exit();
