<?php
require_once(__DIR__.'/../includes/config.php');

$fh = fopen(__DIR__."/orders_export.csv", 'r');

$titles = array_map('strtolower',fgetcsv($fh));

//print_r($titles);

$last_order = [];
$order_id = 0;

while($row = fgetcsv($fh)){

	$row = array_combine($titles, $row);

	if(empty($row['id'])){
		continue;
	}

	$order = [
		'id' => $row['id'],
		'app_id' => null,
		'cart_token' => null,
		'number' => preg_replace('/\D/','',$row['name']),
		'total_price' => $row['total'],
		'created_at' => $row['created at'],
		'updated_at' => $row['created at'],
	];

	print_r($order);

	$order_id = insert_update_order($db, $order);
//	$order_id++;

	if($order_id > 5){
//		break;
	}
}
