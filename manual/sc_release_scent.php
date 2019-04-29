<?php
require_once(__DIR__.'/../includes/config.php');
require_once(__DIR__.'/../includes/class.ShopifyClient.php');
require_once(__DIR__.'/../includes/class.RechargeClient.php');

$rc = new RechargeClient();
$sc = new ShopifyPrivateClient();

$page = 0;
$scent = null;

$start_date = date('Y-m-t');
$end_date = date('Y-m', strtotime('+2 months')).'-01';

$charges = [];

$start_time = microtime(true);
do {
	// Load upcoming queued charges for may
	$res = $rc->get('/charges', [
		'date_min' => $start_date,
		'date_max' => $end_date,
		'status' => 'QUEUED',
		'limit' => 250,
		'page' => $page,
		'address_id' => '29919072',
	]);
	array_push($charges, $res['charges']);
	echo "Adding ".count($res['charges'])." to array - total: ".count($charges).PHP_EOL;
	echo "Rate: ".(count($charges) / (microtime(true) - $start_time))." charges/s".PHP_EOL;
} while(count($res['charges']) == 250);


$start_time = microtime(true);
echo "Starting updates".PHP_EOL;
foreach($charges as $index=>$charge){
	// Check if the charge is scent club
	$scent_club_item = false;
	foreach($charge['line_items'] as $line_item){
		if(is_scent_club(get_product($db, $line_item['shopify_product_id']))){
			echo "Swapping on address ".$charge['address_id'];
			sc_swap_to_monthly($db, $rc, $charge['address_id'], strtotime($charge['scheduled_at']));
			break;
		}
	}
	if($index % 20 == 0){
		echo "Updated: ".$index." Rate: ".($index / (microtime(true) - $start_time))." charges/s".PHP_EOL;
	}
}