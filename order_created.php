<?php

require_once('includes/config.php');
require_once('includes/class.RechargeClient.php');

echo getcwd();

$rc = new RechargeClient();

$headers = getallheaders();
$shop_url = null;
if(!empty($headers['X-Shopify-Shop-Domain'])){
	$shop_url = $headers['X-Shopify-Shop-Domain'];
}
if(empty($shop_url)){
	$shop_url = 'maven-and-muse.myshopify.com';
}

$data = file_get_contents('php://input');
$cache_file = 'last_order_created.txt';
if(empty($data)){
	if(file_exists($cache_file)){
		$data = file_get_contents($cache_file);
	}
} else {
	file_put_contents($cache_file, $data);
}
if(empty($data)){
	die("No cache file");
}
$order = json_decode($data, true);

// Check if order:
// - Has the right line item
// - Has the cart attribute

$subscription_product_ids = [];

$has_subscription_line_item = false;
foreach($order['line_items'] as $line_item){

}