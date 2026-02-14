<?php

$host = "localhost";
$user = "root";
$password = "";
$database = "lovesync";

$conn = new mysqli(
    $host,
    $user,
    $password,
    $database
);

if ($conn->connect_error) {
    die("DB Connection Failed ❌ " .
        $conn->connect_error);
}
session_start();
?>
