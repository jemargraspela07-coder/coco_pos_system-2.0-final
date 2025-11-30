<?php
session_start();
include '../db.php';

if (!isset($_SESSION['customer'])) {
    echo json_encode(['count' => 0]);
    exit;
}

$user_id = $_SESSION['customer'];

$sql = "SELECT COUNT(*) as ready_count FROM orders WHERE user_id=$user_id AND status='Ready for Pick Up'";
$res = $conn->query($sql);
$row = $res->fetch_assoc();
$count = $row['ready_count'];

echo json_encode(['count' => $count]);
