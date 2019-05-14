<?php
require_once(__DIR__.'/../includes/config.php');
require_once(__DIR__.'/../includes/class.ShopifyClient.php');

if(empty($_REQUEST['id'])){
	die('Missing order ID');
}

$sc = new ShopifyClient();
$draft_order_id = $_REQUEST['id'];

$draft_order = $sc->get('/admin/draft_orders/'.$draft_order_id.'.json');

print_r($draft_order);

foreach($draft_order['line_items'] as $index=>$line_item){
	
}

header("Location: /admin/draft_orders/".$draft_order_id);