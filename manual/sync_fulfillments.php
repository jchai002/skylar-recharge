<?php
require_once(__DIR__.'/../includes/config.php');

$sc = new ShopifyClient();

$page_size = 1000;
$page = 0;



do {
    $page++;
    $stmt = $db->query("SELECT DISTINCT o.shopify_id as shopify_order_id FROM order_line_items oli
LEFT JOIN orders o ON o.id=oli.order_id
WHERE oli.fulfillment_id IS NULL #LIMIT ".$page*$page_size.",".$page_size);
    $order_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach($order_ids as $order_id){
        $res = $sc->get('/admin/orders/'.$order_id.'/fulfillments.json');
        if($sc->callsLeft() < 5){
            sleep(1);
        }
        if(empty($res)){
            continue;
        }
        foreach($res as $fulfillment){
            echo insert_update_fulfillment($db, $fulfillment).PHP_EOL;
        }
    }
} while(count($order_ids) >= $page_size);