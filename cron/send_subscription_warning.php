<?php
require_once(__DIR__.'/../includes/config.php');
require_once(__DIR__.'/../includes/class.ShopifyClient.php');
require_once(__DIR__.'/../includes/class.RechargeClient.php');

$rc = new RechargeClient();
$sc = new ShopifyPrivateClient();

// Load blacklist
$blacklist_emails = [];
/*
$f = fopen(__DIR__.'/email_blacklist.csv', 'r');
$headers = fgetcsv($f);
while($row = fgetcsv($f)){
	$blacklist_emails[] = $row[0];
}
$blacklist_emails = array_values(array_unique($blacklist_emails));
*/

//$now = strtotime('tomorrow');

/** Schedule:
 * Monday: Thursday
 * Tuesday: Friday
 * Wednesday: Sat, Sun, Mon
 * Thursday: Tuesday
 * Friday: Wednesday
 */

$now = time();

$day_of_week = date('N', $now);
$start_date = date('Y-m-d', strtotime('+2 days', $now));
if($day_of_week == 3){ // Wednesday
	$end_date = date('Y-m-d', strtotime('+6 days', $now));
} elseif($day_of_week == 4 || $day_of_week == 5) { // Thursday and Friday
	$start_date = date('Y-m-d', strtotime('+4 days', $now));
	$end_date = date('Y-m-d', strtotime('+6 days', $now));
} elseif($day_of_week >= 6) {
	die("No weekend emails");
} else {
	$end_date = date('Y-m-d', strtotime('+4 days', $now));
}

$page = 0;
echo "$start_date to $end_date".PHP_EOL;
$stmt = $db->prepare("SELECT 1 FROM emails_sent WHERE (email='SUB_3DAY_WARNING' OR email='SUB_3DAY_WARNING_SC') AND DATE(date_created) = '".date('Y-m-d', $now)."' AND recipient=?");
$stmt_insert = $db->prepare("INSERT INTO emails_sent (email, recipient, date_created) VALUES (:email, :recipient, :date_created)");
do {
	$page++;
	$res = $rc->get('/charges', [
		'date_min' => $start_date,
		'date_max' => $end_date,
		'status' => 'QUEUED',
		'limit' => 250,
		'page' => $page,
//		'address_id' => '29919072',
	]);
	$charges = $res['charges'];
	foreach($charges as $charge){
		if(in_array($charge['email'], $blacklist_emails)){
			echo "Blacklisted, skipping: ".$charge['email']." address id: ".$charge['address_id'].PHP_EOL;
			continue;
		}
		$is_scent_club = false;
		foreach($charge['line_items'] as $item){
			$is_scent_club = is_scent_club_any(get_product($db, $item['shopify_product_id']));
			if($is_scent_club){
				break;
			}
		}
		$stmt->execute([
			$charge['email'],
		]);
		if($stmt->rowCount() > 0){
			echo "Already sent, skipping: ".$charge['email']." address id: ".$charge['address_id'].PHP_EOL;
			continue;
		}
		$data = base64_encode(json_encode([
			'token' => "KvQM7Q",
			'event' => 'Sent Transactional Email',
			'customer_properties' => [
				'$email' => $charge['email'],
			],
			'properties' => [
				'email_type' => $is_scent_club ? 'sub_3day_warning_sc' : 'sub_3day_warning',
				'first_name' => $charge['first_name'],
			]
		]));
		$ch = curl_init("https://a.klaviyo.com/api/track?data=$data");
		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
		]);
		$res = json_decode(curl_exec($ch));
		$stmt_insert->execute([
			'email' => $is_scent_club ? 'SUB_3DAY_WARNING_SC' : 'SUB_3DAY_WARNING',
			'recipient' => $charge['email'],
			'date_created' => date('Y-m-d H:i:s', $now),
		]);
		log_event($db, 'EMAIL', $charge['email'], 'SUB_3DAY_WARNING'.($is_scent_club ? '_SC' : ''), json_encode($res), json_encode($charge), 'CRON');
		echo "Sent email to: ".$charge['email']." address id: ".$charge['address_id'].PHP_EOL;
	}
	sleep(1);
} while(count($charges) >= 250);