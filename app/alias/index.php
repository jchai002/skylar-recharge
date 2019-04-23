<?php
require_once('../../includes/config.php');
require_once('../../includes/class.ShopifyClient.php');

$sc = new ShopifyClient();

if($_REQUEST['type'] == 'order'){
	$order = $sc->get('/admin/orders/'.$_REQUEST['id'].'.json');
	$customer_id = $order['customer_id'];
} else {
	$customer_id = intval($_REQUEST['id']);
}

header("Location: https://skylar.com/account?c=".$customer_id);