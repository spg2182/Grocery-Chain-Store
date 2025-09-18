<?php
include 'includes/db.php';
header('Content-Type: text/plain; charset=utf-8');
$mobile = $_GET['customer_mobile'] ?? '';
if ($mobile && preg_match('/^09[0-9]{9}$/', $mobile)) {
    $stmt = $pdo->prepare("SELECT full_name FROM customers WHERE mobile = ?");
    $stmt->execute([$mobile]);
    echo $stmt->fetchColumn() ?: '';
}