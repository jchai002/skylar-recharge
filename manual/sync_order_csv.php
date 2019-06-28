<?php
require_once(__DIR__.'/../includes/config.php');

$fh = fopen(__DIR__."/orders_export.csv", 'r');

$titles = array_map('strtolower',fgetcsv($fh));

$order = [];
$order_id = 0;
$sc = new ShopifyClient();

while($row = fgetcsv($fh)){

	$row = array_combine($titles, $row);

	if(!empty($row['id']) && !empty($order)){
		$order_id = insert_update_order($db, $order, $sc);
		print_r($order);
	}
	if(!empty($row['id'])){
		$order = [
			'id' => $row['id'],
			'app_id' => null,
			'cart_token' => null,
			'number' => preg_replace('/\D/','',$row['name']),
			'total_line_items_price' => $row['subtotal'],
			'total_discounts' => $row['discount amount'],
			'total_price' => $row['total'],
			'tags' => $row['tags'],
			'created_at' => $row['created at'],
			'updated_at' => $row['created at'],
			'cancelled_at' => $row['cancelled at'],
			'closed_at' => null,
			'email' => $row['email'],
			'note' => $row['notes'],
			'note_attributes' => null,
			'source_name' => $row['source'],
			'line_items' => [],
		];
	}
	$order['line_items'][] = [

	];

	if($order_id > 5){
//		break;
	}
}
