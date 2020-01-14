<?php
require_once(__DIR__.'/../includes/config.php');
require_once(__DIR__.'/../includes/class.ShopifyClient.php');
require_once(__DIR__.'/../includes/class.RechargeClient.php');

$rc = new RechargeClient();
$sc = new ShopifyPrivateClient();

// Load blacklist
$blacklist_emails = [];

$now = time();
if(!is_business_day($now)){
	die("Don't send emails on off days".PHP_EOL);
}

// Need to get the range of dates to warn
// Should be all orders from after the 2nd business day, on or before the 3rd business day
// e.g. on Wednesdays we warn Sat, Sun, Mon orders
$biz_days_counted = 0;
$time_counter = $now;
while($biz_days_counted < 3){
	$time_counter = strtotime('tomorrow', $time_counter);
	if(is_business_day($time_counter)){
		$biz_days_counted++;
		if($biz_days_counted == 2){
			// Dates are exclusive for RC so start the day before
			$start_date = date('Y-m-d', $time_counter);
		}
		if($biz_days_counted == 3){
			// Dates are exclusive for RC so end the day after
			$end_date = date('Y-m-d', strtotime('tomorrow', $time_counter));
			break;
		}
	}
}

$page = 0;
echo "$start_date to $end_date".PHP_EOL;
$stmt = $db->prepare("SELECT 1 FROM emails_sent WHERE (email='SUB_3DAY_WARNING' OR email='SUB_3DAY_WARNING_SC' OR email='SUB_3DAY_WARNING_AC') AND DATE(date_created) = '".date('Y-m-d', $now)."' AND recipient=?");
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
		$is_autocharge = false;
		foreach($charge['line_items'] as $item){
			$is_scent_club = is_scent_club_any(get_product($db, $item['shopify_product_id']));
			$is_autocharge = is_ac_followup_lineitem($item);
		}
		$stmt->execute([
			$charge['email'],
		]);
		if($stmt->rowCount() > 0){
			echo "Already sent, skipping: ".$charge['email']." address id: ".$charge['address_id'].PHP_EOL;
			continue;
		}
		if($is_autocharge){
			$email_type = 'sub_3day_warning_ac';
		} else if($is_scent_club){
			$email_type = 'sub_3day_warning_sc';
		} else {
			$email_type = 'sub_3day_warning';
		}
		$data = base64_encode(json_encode([
			'token' => "KvQM7Q",
			'event' => 'Sent Transactional Email',
			'customer_properties' => [
				'$email' => $charge['email'],
			],
			'properties' => [
				'email_type' => $email_type,
				'first_name' => $charge['first_name'],
			]
		]));
		$ch = curl_init("https://a.klaviyo.com/api/track?data=$data");
		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
		]);
		$res = json_decode(curl_exec($ch));
		$stmt_insert->execute([
			'email' => strtoupper($email_type),
			'recipient' => $charge['email'],
			'date_created' => date('Y-m-d H:i:s', $now),
		]);
		log_event($db, 'EMAIL', $charge['email'], 'SUB_3DAY_WARNING'.($is_scent_club ? '_SC' : ''), json_encode($res), json_encode($charge), 'CRON');
		echo "Sent email to: ".$charge['email']." address id: ".$charge['address_id'].PHP_EOL;
	}
	sleep(1);
} while(count($charges) >= 250);