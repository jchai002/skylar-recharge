<?php
require_once(__DIR__.'/../includes/config.php');

$sc = new ShopifyClient();
$order = $sc->get('/admin/orders/1066783244375.json');

$analytics = new TheIconic\Tracking\GoogleAnalytics\Analytics();

$analytics
//	->setDebug(true)
	->setProtocolVersion('1')
	->setTrackingId('UA-96604279-1')
	->setUserId($order['customer']['id'])
	->setDataSource('test');

$analytics->setTransactionId($order['order_number'])
	->setAffiliation('Skylar Offline')
	->setRevenue($order['total_price_set']['shop_money']['amount'])
	->setTax($order['total_tax_set']['shop_money']['amount'])
	->setShipping($order['total_shipping_price_set']['shop_money']['amount']);
if(!empty($order['discount_codes'])){
	$analytics->setCouponCode($order['discount_codes'][0]['code']);
}

foreach($order['line_items'] as $index=>$item){
	$analytics->addProduct([
		'sku' => $item['sku'],
		'name' => $item['title'],
		'brand' => $item['vendor'],
		'variant' => $item['variant_title'],
		'price' => $item['price'],
		'quantity' => $item['quantity'],
	]);
}
$analytics->setProductActionToPurchase();

$response = $analytics->setEventCategory('ecommerce')
	->setEventAction('offline purchase')
	->setEventLabel($order['order_number'])
	->setNonInteractionHit(1)
	->sendEvent();

var_dump($response);

//print_r($response->getDebugResponse());