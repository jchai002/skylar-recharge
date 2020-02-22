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

$page = 0;
do {
	$page++;
	$res = $rc->get('/charges', [
		'status' => 'ERROR',
		'date_min' => date('Y-m-d', strtotime('yesterday')),
		'date_max' => date('Y-m-d', get_month_by_offset(3)),
//		'date_min' => date('Y-m-d', get_next_month()),
		'page' => $page,
		'limit' => 250,
	]);
	foreach($res['charges'] as $charge){
		if(
			$charge['error_type'] == 'VARIANT_DOES_NOT_EXIST'
			|| strpos($charge['error'], 'EXCEPTION ON GETTING SHIPPING RATES') !== false
			|| strpos($charge['error'], 'There\'s no shipping rates and store charges shipping') !== false
		){
			echo $charge['id'].PHP_EOL;
			$charges[] = $charge;
			continue;
		}
		if(empty($charge['error_type'])){
			print_r($charge);
			die();
		}
		if($charge['error_type'] != 'VARIANT_DOES_NOT_EXIST'){
			echo $charge['error_type'].PHP_EOL;
			continue;
		}
	}
	echo "Adding ".count($res['charges'])." to array - total: ".count($charges).PHP_EOL;
} while(count($res['charges']) == 250);

//die();

$cookie_token = '29422|7cbe63b01b7aec3588970e96fec94d101db71f8b0f72623bd7d3d2b87095ca3fcc767803605372a39b2ec76d62438ca4fa918f7b2ee735e6af4efb3505169b49';

$last_charge_id = 0;
$starttime = microtime(true);
foreach($charges as $rownum => $charge){

	$charge_id = $charge['id'];

	echo $charge_id.", ".$charge['address_id'].": ";

	print_r(regenerate_charge($charge_id));

	if($rownum % 20 == 0){
		echo "Row: $rownum. Time: ".(microtime(true)-$starttime)." seconds | ";
		echo "Pace: ".($rownum / (microtime(true)-$starttime))." rows/sec".PHP_EOL;
	}
}