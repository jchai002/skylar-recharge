<?php
require_once(__DIR__.'/../includes/config.php');

$charges = [];
$page_size = 250;
$page = 0;
$shipping_codes = [];
do {
	$page++;
	$res = $rc->get('/charges', [
		'status' => 'QUEUED',
		'limit' => $page_size,
		'page' => $page,
		'date' => '2020-05-01',
	]);
	foreach($res['charges'] as $charge){
		if(empty($charge['shipping_lines'])){
			if(empty($shipping_codes['missing shipping_lines'])){
				$shipping_codes['missing shipping_lines'] = [];
			}
			$shipping_codes['missing shipping_lines'][] = $charge['address_id'];
			continue;
		}
		if(empty($shipping_codes[$charge['shipping_lines'][0]['code']])){
			$shipping_codes[$charge['shipping_lines'][0]['code']] = 0;
		}
		$shipping_codes[$charge['shipping_lines'][0]['code']]++;
		if($charge['shipping_lines'][0]['code'] == 'Standard Weight-based'){
			$charges[] = $charge;
		}
	}
	print_r($shipping_codes);
} while(count($res['charges']) >= $page_size);

$charges = array_slice($charges, 0, 2500);
$num_to_process = count($charges);
echo "Regenerating $num_to_process".PHP_EOL;
$start_time = microtime(true);
foreach($charges as $index => $charge){
	regenerate_charge($charge['id']);
	if($index > 0 && $index % 20 == 0){
		$elapsed_time = microtime(true) - $start_time;
		$charges_per_sec = $index / $elapsed_time;
		$charges_remaining = $num_to_process - $index;
		$time = microtime(true) + ($charges_remaining / $charges_per_sec);
		echo "Updated: ".$index."/".$num_to_process." Rate: ".$charges_per_sec." charges/s, Estimated finish: ".date('Y-m-d H:i:s',$time).PHP_EOL;
	}
}