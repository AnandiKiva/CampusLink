<?php
require '/home/campusli/domains/campuslink.co.za/config.php';

$conn = mysqli_connect(
    $config['db_host'],
    $config['db_user'],
    $config['db_pass'],
    $config['db_name']
);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}