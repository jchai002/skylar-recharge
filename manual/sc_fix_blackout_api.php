<?php
require_once(__DIR__.'/../includes/config.php');
require_once(__DIR__.'/../includes/class.ShopifyClient.php');
require_once(__DIR__.'/../includes/class.RechargeClient.php');

$rc = new RechargeClient();
$sc = new ShopifyPrivateClient();

$page = 0;
$scent = null;

$start_date = date('Y-m-t');
$end_date = date('Y-m', get_next_month(get_next_month())).'-01';
$end_date = '2019-06-03';

echo "$start_date to $end_date".PHP_EOL;

$charges = [];

$start_time = microtime(true);
do {
	$page++;
	// Load upcoming queued charges for may
	$res = $rc->get('/charges', [
		'date_min' => $start_date,
		'date_max' => $end_date,
		'status' => 'QUEUED',
		'limit' => 250,
		'page' => $page,
//		'address_id' => '32968759',
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

$processed_addresses = [];
$start_time = microtime(true);
echo "Starting updates".PHP_EOL;
foreach($charges as $index=>$charge){
	if(in_array($charge['address_id'], $processed_addresses)){
		continue;
	}
	$processed_addresses[] = $charge['address_id'];
	if(!sc_is_address_in_blackout($db, $rc, $charge['address_id'])){
		echo "Address not in blackout: ".$charge['address_id'].PHP_EOL;
		continue;
	}
	echo "Moving charge ".$charge['id']." address ".$charge['address_id']." ";
	sc_delete_month_onetime($db, $rc, $charge['address_id'], get_next_month());
	echo sc_calculate_next_charge_date($db, $rc, $charge['address_id']).PHP_EOL;
}