<?php
require_once(__DIR__.'/../includes/config.php');


$variant_map = [
	30725266440279 => 30995105480791,
	30725267882071 => 30995105513559,
	30725267914839 => 30995105546327,
	30995105480791 => 30995105480791, // Ship nows
	30995105513559 => 30995105513559,
	30995105546327 => 30995105546327,
];

// Load scent club gift subs
$subscriptions = [];
$start_time = microtime(true);
foreach($variant_map as $variant_id => $map_to_variant_id){
	if($map_to_variant_id == $variant_id){
		continue;
	}
	echo "Getting subscriptions for variant ID ".$variant_id.PHP_EOL;
	$page_size = 250;
	$page = 0;
	do {
		$page++;
		$res = $rc->get("/subscriptions", [
			'page' => $page,
			'limit' => $page_size,
			'shopify_variant_id' => $variant_id,
			'status' => 'ACTIVE',
		]);
		foreach($res['subscriptions'] as $subscription){
			$subscriptions[] = $subscription;
		}
		echo "Adding ".count($res['subscriptions'])." to array - total: ".count($subscriptions).PHP_EOL;
		echo "Rate: ".(count($subscriptions) / (microtime(true) - $start_time))." subs/s".PHP_EOL;
	} while(count($res['subscriptions']) >= $page_size);
}
echo "Total: ".count($subscriptions).PHP_EOL;

$starting_point = 0;
$num_to_process = count($subscriptions);
$start_time = microtime(true);
echo "Starting updates $starting_point - ".($starting_point+$num_to_process).PHP_EOL;
foreach($subscriptions as $index=>$subscription){
	echo "Updating variant id on sub ".$subscription['id']." address ".$subscription['address_id']."... ";
//	die();
	$res = $rc->put('/subscriptions/'.$subscription['id'], [
		'shopify_variant_id' => $variant_map[$subscription['shopify_variant_id']],
		'product_title' => 'Scent Club Gift',
		'variant_title' => '',
	]);
	echo $res['subscription']['shopify_variant_id'].PHP_EOL;
}








die();
$addresses = [
//	38612535,
	38882272,
	39008687,
	39091471,
	39098536,
	39111369,
	39116562,
	39126307,
	39128794,
	39132294,
	39139294,
	39160977,
	39185239,
	39210749,
	39248251,
	39252897,
	39254299,
	39271075,
	39289545,
	39290009,
	39292744,
	39274038,
	39332520,
	39341511,
	39360966,
	39367261,
	39370509,
];

// Create a onetime for them
foreach($addresses as $address_id){

	echo $address_id.PHP_EOL;

	$res = $rc->get('/orders', ['address_id' => $address_id]);
	if(empty($res['orders'])){
		die("No orders found");
	}
	$variant_id = null;
	foreach($res['orders'] as $order){
		foreach($order['line_items'] as $line_item){
			if(is_scent_club_gift(get_product($db, $line_item['shopify_product_id']))){
				$variant_id = $line_item['shopify_variant_id'];
			}
		}
	}
	if(empty($variant_id)){
		die('No variant id found');
	}
	echo $variant_id.PHP_EOL;
	echo $variant_map[$variant_id].PHP_EOL;

	$res = $rc->post('/addresses/'.$address_id.'/onetimes', [
		'address_id' => $address_id,
		'next_charge_scheduled_at' => date('Y-m-d'),
		'shopify_variant_id' => $variant_map[$variant_id],
		'product_title' => 'Scent Club Gift',
		'title' => 'Scent Club Gift',
		'variant_title' => '',
		'price' => '0',
		'quantity' => 1,
	]);
	if(empty($res['onetime'])){
		print_r($res);
		die("couldn't create onetime");
	}
	echo "Created ".$res['onetime']['id'].PHP_EOL;
//	die();
}