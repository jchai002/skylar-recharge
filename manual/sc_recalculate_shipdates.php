<?php

require_once(__DIR__.'/../includes/config.php');
require_once(__DIR__.'/../includes/class.ShopifyClient.php');
require_once(__DIR__.'/../includes/class.RechargeClient.php');

$rc = new RechargeClient();

$fh = fopen(__DIR__."/orders_export3.csv", 'r');

$titles = array_map('strtolower',fgetcsv($fh));

//print_r($titles);

$last_order = [];
$order_id = 0;

$max_time = strtotime('may 1 2019');

$count = 0;
while($row = fgetcsv($fh)){
	$count++;
	$row = array_combine($titles, $row);
	if(empty($row['subscription id'])){
		continue;
	}
	if(strtotime($row['charge date']) > $max_time){
		continue;
	}
	if(!is_scent_club(get_product($db, $row['shopify product id']))){
		continue;
	}
	$res = [];
	$res_all = [];
	$res_all[] = $res = $rc->get('/subscriptions/'.$row['subscription id']);
	print_r($res);
	if(empty($res['subscription'])){
		continue;
	}
	$res_all[] = sc_calculate_next_charge_date($db, $rc, $res['subscription']['address_id']);
	print_r($res_all);
	sleep(1);
}