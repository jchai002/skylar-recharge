<?php
require_once('../../includes/config.php');

$type = $_REQUEST['type'] ?? 'customer';
if($type == 'order'){
	$order = $sc->get('/admin/orders/'.$_REQUEST['id'].'.json');
	$customer_id = $order['customer']['id'];
} else {
	$customer_id = intval($_REQUEST['id']);
}
//var_dump($order);
header("Location: https://skylar.com/tools/skylar/?c=".$customer_id."&alias=".md5($_ENV['ALIASKEY'].$customer_id));