<?php
require_once('../../includes/config.php');
require_once('../../includes/class.ShopifyClient.php');

$sc = new ShopifyClient();

$order = $sc->get('/orders/'.$_REQUEST['id'].'.json');

var_dump($sc);
var_dump($_REQUEST);
var_dump($order);
die();

header("Location: https://skylar.com/account?c=".$order['customer_id']);