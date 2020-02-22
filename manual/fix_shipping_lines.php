<?php
require_once(__DIR__.'/../includes/config.php');
/*
echo "Updating Addresses".PHP_EOL;
$page = 0;
$page_size = 250;
$addresses = [];
do {
	$page++;
	$res = $rc->get('/addresses', [
		'limit' => $page_size,
		'page' => $page,
	]);
	foreach($res['addresses'] as $address){
		$addresses[] = $address;
		echo insert_update_rc_address($db, $address, $rc, $sc).PHP_EOL;
	}
} while(count($res['addresses']) >= $page_size);
*/

$address_ids = $db->query("
SELECT recharge_id FROM rc_addresses
WHERE shipping_lines NOT LIKE '%\"code\":\"Standard Weight-based\"%'
AND shipping_lines NOT LIKE '%\"code\":\"Standard Scent Club\"%'
AND shipping_lines NOT LIKE '%\"code\":\"US 2 Day\"%'
AND shipping_lines NOT LIKE '%\"code\":\"US Next Day\"%'
AND shipping_lines NOT LIKE '%\"code\":\"PASDDP\"%'
AND shipping_lines NOT LIKE '%\"code\":\"DHL WW Express\"%'
AND shipping_lines NOT LIKE '%\"code\":\"USPS FC International\"%'
AND shipping_lines NOT LIKE '%\"code\":\"AKHI Legacy Shipping\"%'
AND shipping_lines != '[]'
AND shipping_lines != 'null'
AND shipping_lines IS NOT null
;")->fetchAll(PDO::FETCH_COLUMN);

foreach($address_ids as $address_id){
	echo "Updating $address_id... ";
	$res = $rc->get("/addresses/$address_id");
	if(empty($res['address'])){
		continue;
	}
	$res = $rc->put("/addresses/$address_id",[
		'shipping_lines_override' => null,
	]);
	if(empty($res['address'])){
		print_r($res);
		die();
	}
	echo insert_update_rc_address($db, $res['address'], $rc, $sc).PHP_EOL;
}