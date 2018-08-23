<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('includes/class.RechargeClient.php');

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
if(empty($data)){
	$data = file_get_contents('last_order_created.txt');
} else {
	file_put_contents('last_order_created.txt', $data);
}
$order = json_decode($data);

// Check if order:
// - Has the right line item
// - Has the cart attribute

$subscription_product_ids = [];

$has_subscription_line_item = false;
foreach($order['line_items'] as $line_item){

}