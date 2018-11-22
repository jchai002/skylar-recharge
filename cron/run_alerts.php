<?php
require_once(__DIR__.'/../includes/config.php');

$alert_id = 1;

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

$message_threshold = date('Y-m-d H:i:s', strtotime('-30 minutes'));
$stmt = $db->query("SELECT 1 FROM alert_logs WHERE alert_id=$alert_id AND message_sent=1 AND date_created >= '$message_threshold'");
$smother_message = $stmt->rowCount() > 0;

$alert_sent = false;
$msg = null;
if($percent_change['count'] < -60 || $percent_change['revenue'] < -60){
	$to = implode(', ',[
		'tim@timnolansolutions.com',
		'sarah@skylar.com',
		'cat@skylar.com',
	]);
	$msg = "Order count has changed by " . number_format($percent_change['count'],2) . "% over the last hour.
Revenue has changed by " . number_format($percent_change['revenue'],2) . "% over the last hour.";
	$headers = [
		'From' => 'Skylar Alerts <alerts@skylar.com>',
		'Reply-To' => 'tim@timnolansolutions.com',
		'X-Mailer' => 'PHP/' . phpversion(),
	];

	if($smother_message){
		echo "Smothering Alert";
	} else {
		echo "Sending Alert: ".PHP_EOL.$msg.PHP_EOL;

		mail($to, "ALERT: Sales Decline", $msg
	//		,implode("\r\n",$headers)
		);

		$alert_sent = true;
	}
}

$stmt = $db->prepare("INSERT INTO alert_logs (alert_id, message, message_sent, message_smothered, date_created) VALUES ($alert_id, :message, :message_sent, :message_smothered, :date_created)");
$stmt->execute([
	'message' => $msg,
	'message_sent' => $alert_sent ? 1 : 0,
	'message_smothered' => $smother_message ? 1 : 0,
	'date_created' => date('Y-m-d H:i:s'),
]);
print_r($stmt->errorInfo());