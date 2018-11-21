<?php
require_once(__DIR__.'/../includes/config.php');

$start_date = date('Y-m-d H:i:00', strtotime('-7 hours'));
$end_date = date('Y-m-d H:i:00', strtotime('-1 hour'));

$normalize_factor = 6;

echo "Start: $start_date End: $end_date".PHP_EOL;

$stmt = $db->query("SELECT COUNT(*) AS count, SUM(total_price) as revenue FROM orders WHERE created_at BETWEEN '$start_date' AND '$end_date';");

$baseline = array_map(function($val) use($normalize_factor) {
	return $val/$normalize_factor;
},$stmt->fetch(PDO::FETCH_ASSOC));

echo "Baseline: ".$baseline['count']." | ".$baseline['revenue'].PHP_EOL;

$start_date = date('Y-m-d H:i:00', strtotime('-1 hour'));
$end_date = date('Y-m-d H:i:00');

$stmt = $db->query("SELECT COUNT(*) AS count, SUM(total_price) as revenue FROM orders WHERE created_at BETWEEN '$start_date' AND '$end_date';");

$last_hour = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Last hour: ".$last_hour['count']." | ".$last_hour['revenue'].PHP_EOL;

$change = [
	'count' => $last_hour['count'] - $baseline['count'],
	'revenue' => $last_hour['revenue'] - $baseline['revenue'],
];

echo "Change: ".$change['count']." | ".$change['revenue'].PHP_EOL;

$percent_change = [
	'count' => divide($change['count'],$baseline['count'])*100,
	'revenue' => divide($change['revenue'],$baseline['revenue'])*100,
];

echo "Percent Change: ".$percent_change['count']." | ".$percent_change['revenue'].PHP_EOL;