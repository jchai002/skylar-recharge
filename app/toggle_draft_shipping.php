<?php
require_once(__DIR__.'/../includes/config.php');
require_once(__DIR__.'/../includes/class.ShopifyClient.php');

if(empty($_REQUEST['id'])){
	die('Missing order ID');
}

$sc = new ShopifyClient();
$draft_order_id = $_REQUEST['id'];

$draft_order = $sc->get('/admin/draft_orders/'.$draft_order_id.'.json');

print_r(array_column($draft_order, 'line_items'));

$free_override_active = in_array('_freeship_override', array_column(array_column($draft_order['line_items'], 'properties'), 'name'));

foreach($draft_order['line_items'] as $index=>$line_item){
	
}

header("Location: /admin/draft_orders/".$draft_order_id);