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
		'date_max' => date('Y-m-d', get_month_by_offset(2)),
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
$cookie_session = '.eJw1j8FqwzAQRH-l7LkHW_Glhl6KTFFhZSKcmt1LaBvXsmSF4CTYVsi_Vy2UOQxzGObNDfbfU3e2UF6ma_cI--EA5Q0ePqEElpijHEeWL46isfp1u6Crch12Gx3MWEsz1LJfKWBOjlZuVabbamFpPEqfcWMHdrhQW80ov2Zs_AYbKnT7lroq14I9xd2c8kDBDBjZajl6cgeXNi0JtWLsCxQqcSSPv-ozat69Fqrghn3dassBn-Ge2E_dFD6O3fHy_-Z67qa_RyCeCiHg_gMUDU8G.ERXzlw.h7lppIZOi5JWxTujHGMNSPgo3Mw';
$last_charge_id = 0;
$starttime = microtime(true);
foreach($charges as $rownum => $charge){

	$charge_id = $charge['id'];

	echo $charge_id.", ".$charge['address_id'].": ";

//	die();

	$ch = curl_init("https://maven-and-muse.shopifysubscriptions.com/charge/$charge_id/regenerate_charge/");
	curl_setopt_array($ch, [
		CURLOPT_RETURNTRANSFER =>  true,
		CURLOPT_HTTPHEADER => ["cookie: session=$cookie_session"],
//		CURLOPT_HTTPHEADER => ["cookie: remember_token=$cookie_token"],
	]);
	$res = curl_exec($ch);

	print_r($res);

	if($rownum % 20 == 0){
		echo "Row: $rownum. Time: ".(microtime(true)-$starttime)." seconds | ";
		echo "Pace: ".($rownum / (microtime(true)-$starttime))." rows/sec".PHP_EOL;
	}
}