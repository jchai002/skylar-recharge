<?php
require_once(__DIR__.'/../includes/config.php');

echo "Updating Addresses".PHP_EOL;
$page = 0;
$addresses = [];
do {
	$page++;
	$res = $rc->get('/addresses', [
		'updated_at_min' => $min_date,
		'limit' => $page_size,
		'page' => $page,
	]);
	foreach($res['addresses'] as $address){
		$addresses[] = $address;
		echo insert_update_rc_address($db, $address, $rc, $sc).PHP_EOL;
	}
} while(count($res) >= $page_size);

die();

$address_ids = $db->query("
SELECT recharge_id FROM rc_addresses
WHERE shipping_lines LIKE '%\"code\":\"Free U.S. standard shipping\"%'
GROUP BY shipping_lines;
")->fetchAll(PDO::FETCH_COLUMN);

foreach($address_ids as $address_id){
	$res = $rc->get("/addresses/$address_id");
	if(empty($res['address'])){
		continue;
	}
	$address = $res['address'];
	print_r($res['address']);
	die();
}