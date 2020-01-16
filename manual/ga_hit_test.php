<?php
require_once(__DIR__.'/../includes/config.php');

$order = $sc->get('/admin/orders/1703030980695.json');

$analytics = new TheIconic\Tracking\GoogleAnalytics\Analytics();

$ga_client_id = get_order_attribute($order, '_ga_client_id');
if(empty($ga_client_id)){
	$ga_client_id = uuidv4();
}

$analytics
//	->setDebug(true)
	->setProtocolVersion('1')
	->setTrackingId('UA-96604279-1')
	->setUserId($order['customer']['id'])
	->setClientId($ga_client_id)
	->setUserAgentOverride('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/76.0.3809.132 Safari/537.36')
	->setDataSource('test');

if(!empty($order['browser_ip'])){
	$analytics->setIpOverride($order['browser_ip']);
}

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

function uuidv4($data = null){
	$data = $data ?? random_bytes(16);
	$data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
	$data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

	return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}