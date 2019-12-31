<?php
require_once(__DIR__.'/../includes/config.php');

$start_date = '2020-01-01';
$charge_date = '2020-01-02';


echo "$start_date to $charge_date".PHP_EOL;

$charges = [];
$page = 0;

$start_time = microtime(true);
do {
	$page++;
	// Load upcoming queued charges for may
	$res = $rc->get('/charges', [
		'date_min' => date('Y-m-d', strtotime($start_date)-12*60*60),
		'date_max' => $charge_date,
		'status' => 'QUEUED',
		'limit' => 250,
		'page' => $page,
//		'address_id' => '29806558',
	]);
	foreach($res['charges'] as $charge){
		$charges[] = $charge;
	}
	echo "Adding ".count($res['charges'])." to array - total: ".count($charges).PHP_EOL;
	echo "Rate: ".(count($charges) / (microtime(true) - $start_time))." charges/s".PHP_EOL;
} while(count($res['charges']) == 250);

echo "Total: ".count($charges).PHP_EOL;

$outstream = fopen("moves.csv", 'w');

echo "Starting updates".PHP_EOL;
$start_time = microtime(true);
foreach($charges as $index=>$charge){
	if($index == 0){
		fputcsv($outstream, array_keys($charge));
	}
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
	$output = [];
	foreach($charge as $field=>$value){
		if(is_array($value)){
			$value = json_encode($value);
		}
		$output[$field] = $value;
	}
	fputcsv($outstream, $output);
}