<?php
require_once('../../includes/config.php');
require_once('../../includes/class.ShopifyClient.php');

$sc = new ShopifyClient();

if($_REQUEST['type'] == 'order'){
	$order = $sc->get('/admin/orders/'.$_REQUEST['id'].'.json');
	$customer_id = $order['customer']['id'];
} else {
	$customer_id = intval($_REQUEST['id']);
}
//var_dump($order);
setcookie('aliaskey', $_ENV['aliaskey'], 60*60*24, '/', '', true, true);
header("Location: https://skylar.com/tools/skylar/members?c=".$customer_id);