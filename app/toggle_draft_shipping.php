<?php
require_once(__DIR__.'/../includes/config.php');
require_once(__DIR__.'/../includes/class.ShopifyClient.php');

if(empty($_REQUEST['id'])){
	die('Missing order ID');
}

$sc = new ShopifyClient();
$draft_order_id = $_REQUEST['id'];

$draft_order = $sc->get('/admin/draft_orders/'.$draft_order_id.'.json');
$free_override_active = false;
foreach($draft_order['line_items'] as $line_item){
	foreach($line_item['properties'] as $property){
		if($property['name'] == '_freeship_override'){
			$free_override_active = true;
			break 2;
		}
	}
}

foreach($draft_order['line_items'] as $index => $line_item){
	if($free_override_active){
		$draft_order['line_items'][$index]['properties'] = array_filter($line_item['properties'],function($property){
			return $property['name'] != '_freeship_override';
		});
	} else {
		$draft_order['line_items'][$index]['properties'][] = [
			'name' => '_freeship_override',
			'value' => '1',
		];
	}
}

$res = $sc->put('/admin/draft_orders/'.$draft_order_id.'.json', ['draft_order' => $draft_order]);
?>
<html>
<head></head>
<body>
<script src="https://cdn.shopify.com/s/assets/external/app.js"></script>
<script>
    ShopifyApp.init({
        apiKey: '<?=$_ENV['SHOPIFY_APP_KEY']?>',
    });
    ShopifyApp.redirect('/admin/draft_orders/<?=$draft_order_id?>');
</script>
</body>
</html>