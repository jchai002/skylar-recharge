<?php
require_once(__DIR__.'/../includes/config.php');

$normalize_factor = 1;

$start_date = date('Y-m-d H:i:00', strtotime('-'.(1+$normalize_factor).' hours'));
$end_date = date('Y-m-d H:i:00', strtotime('-1 hour'));


$stmt = $db->query("SELECT COUNT(*) AS count, SUM(total_price) as revenue FROM orders WHERE created_at BETWEEN '$start_date' AND '$end_date';");

$baseline = array_map(function($val) use($normalize_factor) {
	return $val/$normalize_factor;
},$stmt->fetch(PDO::FETCH_ASSOC));

echo "Start: $start_date End: $end_date".PHP_EOL;
echo "Baseline: ".$baseline['count']." | ".$baseline['revenue'].PHP_EOL;

$start_date = date('Y-m-d H:i:00', strtotime('-1 hour'));
$end_date = date('Y-m-d H:i:00');

$stmt = $db->query("SELECT COUNT(*) AS count, SUM(total_price) as revenue FROM orders WHERE created_at BETWEEN '$start_date' AND '$end_date';");

$last_hour = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Start: $start_date End: $end_date".PHP_EOL;
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

$percent_change['count'] = -41.234235;

if($percent_change['count'] < -40 || $percent_change['revenue'] < -40){
	$to = implode(', ',[
		'tim@timnolansolutions.com',
	//	'sarah@skylar.com',
	//	'cat@skylar.com',
	]);
	$msg = "Order count has changed by " . number_format($percent_change['count'],2) . "% over the last hour.
Revenue has changed by " . number_format($percent_change['revenue'],2) . "% over the last hour.";
	$headers = [
		'From' => 'alerts@skylar.com',
		'Reply-To' => 'tim@timnolansolutions.com',
		'X-Mailer' => 'PHP/' . phpversion(),
	];

	echo "Sending Alert: ".$msg.PHP_EOL;

	mail($to, "ALERT: Sales Decline", $msg, implode("\r\n",$headers));
}

