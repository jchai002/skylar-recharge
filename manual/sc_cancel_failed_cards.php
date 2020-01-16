<?php
require_once(__DIR__.'/../includes/config.php');

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
		'status' => 'ERROR',
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
	$res = $rc->get('/customers/'.$charge['customer_id']);
	if($res['customer']['has_valid_payment_method']){
		echo "Skipping address id ".$charge['address_id']." as it has a valid cc ".$res['customer']['hash'].PHP_EOL;
		continue;
	}
	echo "Cancelling address id ".$charge['address_id'].PHP_EOL;
	$res = $rc->get('/subscriptions', [
		'address_id' => $charge['address_id'],
		'status' => 'ACTIVE',
	]);
	foreach($res['subscriptions'] as $subscription){
		echo "Cancelling subscription id ".$subscription['id'].PHP_EOL;
		$this_res = $rc->post('/subscriptions/'.$subscription['id'].'/cancel', [
			'cancellation_reason' => 'Invalid CC never updated'
		]);
		if(!empty($this_res['error'])){
			print_r($this_res);
			sleep(5);
		}
	}
	$res = $rc->get('/onetimes', [
		'address_id' => $charge['address_id'],
	]);
	foreach($res['onetimes'] as $onetime){
		if($onetime['status'] != 'ONETIME'){
			continue;
		}
		echo "Deleting onetime id ".$onetime['id'].PHP_EOL;
		$this_res = $rc->delete('/onetime/'.$onetime['id']);
		if(!empty($this_res['error'])){
			print_r($onetime);
			print_r($this_res);
			sleep(5);
		}
	}
}