<?php

require_once(__DIR__.'/../includes/config.php');

$fh = fopen(__DIR__."/orders_export2.csv", 'r');

$titles = array_map('strtolower',fgetcsv($fh));

print_r($titles);

$last_order = [];
$order_id = 0;

$count = 0;
while($row = fgetcsv($fh)){
	$count++;
	$row = array_combine($titles, $row);
	if(empty($row['subscription id'])){
		continue;
	}
	if($row['shopify product id'] != '1945680871511'){
		continue;
	}
	$subscription_id = $row['subscription id'];
	echo PHP_EOL.$subscription_id.PHP_EOL;
	$res = $rc->put('/subscriptions/'.$subscription_id, [
		'shopify_variant_id' => '19519443370071',
		'price' => '20',
	]);
	print_r($res);
	sleep(1);
}