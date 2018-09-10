<?php
require_once('../../includes/config.php');
require_once('../../includes/class.ShopifyClient.php');

$sc = new ShopifyClient();

$order = $sc->get('/admin/orders/'.$_REQUEST['id'].'.json');

header("Location: https://skylar.com/account?c=".$order['customer']['id']);