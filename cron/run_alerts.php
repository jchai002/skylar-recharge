<?php
require_once(__DIR__.'/../includes/config.php');

$start_date = date('Y-m-d H:i:00', strtotime('-7 hours'));
$end_date = date('Y-m-d H:i:00', strtotime('-1 hour'));

$stmt = $db->query("SELECT COUNT(*) AS count, SUM(total_price) as revenue FROM orders WHERE created_at BETWEEN '$start_date' AND '$end_date';");

$baseline = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Baseline: ".$baseline['count']." | ".$baseline['revenue'];

$start_date = date('Y-m-d H:i:00', strtotime('-1 hour'));
$end_date = date('Y-m-d H:i:00');

$stmt = $db->query("SELECT COUNT(*) AS count, SUM(total_price) as revenue FROM orders WHERE created_at BETWEEN '$start_date' AND '$end_date';");

$last_hour = $stmt->fetch(PDO::FETCH_ASSOC);
