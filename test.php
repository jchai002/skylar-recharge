<?php
require_once(__DIR__.'/includes/config.php');

$sc = new ShopifyClient();

$res = $sc->get('/admin/orders/1217702199383/fulfillments.json');

foreach($res as $fulfillment){
    echo insert_update_fulfillment($db, $fulfillment).PHP_EOL;
}