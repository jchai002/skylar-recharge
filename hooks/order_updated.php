<?php
require_once('../includes/config.php');
require_once('../includes/class.ShopifyClient.php');

$headers = getallheaders();
$shop_url = null;
if(!empty($headers['X-Shopify-Shop-Domain'])){
	$shop_url = $headers['X-Shopify-Shop-Domain'];
}
if(empty($shop_url)){
	$shop_url = 'maven-and-muse.myshopify.com';
}
$sc = new ShopifyClient($shop_url);

if(!empty($_REQUEST['id'])){
	$order = $sc->call('GET', '/admin/orders/'.intval($_REQUEST['id']).'.json');
} else {
	$data = file_get_contents('php://input');
	log_event($db, 'log', $data);
	$order = json_decode($data, true);
}
if(empty($order)){
	die('no data');
}

echo insert_update_order($db, $order);
