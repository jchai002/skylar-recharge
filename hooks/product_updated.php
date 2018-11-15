<?php
require_once('../includes/config.php');
require_once('../includes/class.ShopifyClient.php');

$sc = new ShopifyClient();

if(!empty($_REQUEST['id'])){
	$product = $sc->call('GET', '/admin/products/'.intval($_REQUEST['id']).'.json');
} else {
	$data = file_get_contents('php://input');
	$product = json_decode($data, true);
}
if(empty($product)){
	die('no data');
}

echo insert_update_product($db, $product);