<?php
require_once(__DIR__.'/../includes/config.php');
require_once(__DIR__.'/../includes/class.ShopifyClient.php');
require_once(__DIR__.'/../includes/class.RechargeClient.php');

$rc = new RechargeClient();
$sc = new ShopifyPrivateClient();

$page = 0;
$scent = null;

$start_date = date('Y-m-t');
$charge_date = date('Y-m', strtotime('+1 month')).'-01';
$charge_day_of_week = date('N', strtotime($charge_date));
if($charge_day_of_week == 6){
	$charge_date = date('Y-m', strtotime('+1 month')).'-03';
} elseif($charge_day_of_week == 7){
	$charge_date = date('Y-m', strtotime('+1 month')).'-02';
} else {
	die("1st is not a weekend");
}
$end_date = date('Y-m-d', strtotime('+1 day', strtotime($charge_date)));

echo "$start_date to $charge_date".PHP_EOL;

$charges = [];

$start_time = microtime(true);
do {
	$page++;
	// Load upcoming queued charges for may
	$res = $rc->get('/charges', [
		'date_min' => $start_date,
		'date_max' => $charge_date,
		'status' => 'QUEUED',
		'limit' => 250,
		'page' => $page,
//		'address_id' => '29806558',
	]);
	foreach($res['charges'] as $charge){
		foreach($charge['line_items'] as $line_item){
			if(is_scent_club_any(get_product($db, $line_item['shopify_product_id']))){
				$charges[] = $charge;
				break;
			}
		}
	}
	echo "Adding ".count($res['charges'])." to array - total: ".count($charges).PHP_EOL;
	echo "Rate: ".(count($charges) / (microtime(true) - $start_time))." charges/s".PHP_EOL;
} while(count($res['charges']) == 250);

echo "Total: ".count($charges).PHP_EOL;

$start_time = microtime(true);
echo "Starting updates".PHP_EOL;
foreach($charges as $index=>$charge){
	echo "Moving charge ".$charge['id']." address ".$charge['address_id']." ";
	$res = $rc->post('/charges/'.$charge['id'].'/change_next_charge_date', [
		'next_charge_date' => $charge_date
	]);
	if(empty($res['charge'])){
		echo "Error: ";
		print_r($res['error']);
		continue;
	}
	echo $res['charge']['scheduled_at'].PHP_EOL;
}