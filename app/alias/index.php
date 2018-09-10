<?php
require_once('../../includes/config.php');
require_once('../../includes/class.ShopifyClient.php');

$sc = new ShopifyClient();

$order = $sc->get('/admin/orders/'.$_REQUEST['id'].'.json');

var_dump($order);
var_dump($order['default_address']['customer_id']);
die();

header("Location: https://skylar.com/account?c=".$order['customer_id']);