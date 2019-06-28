<?php
require_once(__DIR__.'/includes/config.php');

$sc = new ShopifyClient();

$res = $sc->get('/admin/orders/1089611956311/fulfillments.json');

print_r($res);

foreach($res as $fulfillment){
    echo insert_update_fulfillment($db, $fulfillment).PHP_EOL;
}