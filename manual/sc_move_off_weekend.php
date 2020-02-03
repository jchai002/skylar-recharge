<?php
require_once(__DIR__.'/../includes/config.php');

$page = 0;
$scent = null;

$start_date = date('Y-m-t');
$charge_date = date('Y-m', get_next_month()).'-01';
$charge_time = offset_date_skip_weekend(strtotime($charge_date));
if($charge_time == strtotime($charge_date)){
	die('1st is not a weekend');
}
$charge_date = date('Y-m-d', $charge_time);

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
			if(
				is_scent_club(get_product($db, $line_item['shopify_product_id']))
				|| is_scent_club_month(get_product($db, $line_item['shopify_product_id']))
			){
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
$start_time = microtime(true);
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
	if($index % 20 == 0 && $index > 0){
		echo "Updated: ".$index."/".count($charges)." Rate: ".($index / (microtime(true) - $start_time))." orders/s".PHP_EOL;
	}
}