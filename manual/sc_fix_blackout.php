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

$f = fopen(__DIR__.'/paid_charges.csv', 'r');

$headers = fgetcsv($f);

$last_charge_id = 0;
$rownum = 0;
$starttime = microtime(true);
while($row = fgetcsv($f)){
	$rownum++;
	$row = array_combine($headers, $row);

	// Check if the charge is scent club
	if($row['item sku'] != 857243008252){
		continue;
	}
	if($last_charge_id == $row['recharge charge id']){
		continue;
	}
	$last_charge_id = $row['recharge charge id'];
	echo $row['recharge purchase id'].": ".sc_calculate_next_charge_date($db, $rc, $row['recharge purchase id']).PHP_EOL;
	if($rownum % 20 == 0){
		echo "Row: $rownum. Time: ".(microtime(true)-$starttime)." seconds | ";
		echo "Pace: ".($rownum / (microtime(true)-$starttime))." rows/sec".PHP_EOL;
	}
}