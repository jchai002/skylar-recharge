<?php
require_once(__DIR__.'/../includes/config.php');
require_once(__DIR__.'/../includes/class.ShopifyClient.php');
require_once(__DIR__.'/../includes/class.RechargeClient.php');

$rc = new RechargeClient();
$sc = new ShopifyPrivateClient();

$now = strtotime('tomorrow');
//$now = time();

$day_of_week = date('N', $now);
$start_date = date('Y-m-d', strtotime('+2 days', $now));
if($day_of_week == 5){ // Friday
	$end_date = date('Y-m-d', strtotime('+6 days', $now));
} else {
	$end_date = date('Y-m-d', strtotime('+4 days', $now));
}

$page = 0;
$page++;
echo "$start_date to $end_date".PHP_EOL;
do {
	$res = $rc->get('/charges', [
		'date_min' => $start_date,
		'date_max' => $end_date,
		'status' => 'QUEUED',
		'limit' => 250,
		'page' => $page,
		'address_id' => '29806558',
	]);
	$charges = $res['charges'];
	foreach($charges as $charge){
		$is_scent_club = false;
		foreach($charge['line_items'] as $item){
			$is_scent_club = is_scent_club_any(get_product($db, $item['shopify_product_id']));
			if($is_scent_club){
				break;
			}
		}
		$data = base64_encode(json_encode([
			'token' => "KvQM7Q",
			'event' => 'Sent Transactional Email',
			'customer_properties' => [
				'$email' => $charge['email'],
			],
			'properties' => [
				'email_type' => empty($is_scent_club) ? 'sub_3day_warning' : 'sub_3day_warning_sc',
				'first_name' => $charge['first_name'],
			]
		]));
		$ch = curl_init("https://a.klaviyo.com/api/track?data=$data");
		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
		]);
		$res = json_decode(curl_exec($ch));
		log_event($db, 'EMAIL', $charge['email'], 'SUB_3DAY_WARNING', json_encode($res), json_encode($charge), 'CRON');
		echo "Sent email to: ".$charge['email']." address id: ".$charge['address_id'].PHP_EOL;
	}
} while(count($charges) >= 250);