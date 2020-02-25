<?php
require_once(__DIR__.'/../includes/config.php');

$start_time = date('Y-m-d H:i:s', strtotime('-30 minutes'));
$end_time = date('Y-m-d H:i:s', strtotime('-3 hours'));
$stmt = $db->query("SELECT o.id, o.shopify_id FROM orders o
LEFT JOIN order_transaction_sources ots ON o.id=ots.order_id
WHERE o.created_at < '$start_time' AND o.created_at >= '$end_time'
AND o.ga_hit_sent_at IS NULL
AND ots.id IS NULL");
$stmt_get_historical_sources = $db->prepare("SELECT source, medium, campaign, content FROM order_transaction_sources ots
LEFT JOIN orders o ON ots.order_id=o.id
LEFT JOIN customers c ON o.customer_id=c.id
WHERE c.shopify_id=:customer_id
AND o.shopify_id != :order_id
ORDER BY o.created_at DESC;");

$stmt_update_hit = $db->prepare("UPDATE orders SET ga_hit_sent_at = :now WHERE id = :id");

foreach($stmt->fetchAll() as $row){
	// TODO: Draft orders?
	$shopify_order = $sc->get('/admin/orders/'.$row['shopify_id'].'.json');
	$stmt_get_historical_sources->execute([
		'customer_id' => $shopify_order['customer']['id'],
		'order_id' => $shopify_order['id'],
	]);
	$sources = [];
	if($stmt_get_historical_sources->rowCount() > 0){
		foreach($stmt_get_historical_sources->fetchAll() as $source_row){
			$sources = $source_row;
			if($sources['source'] != '(direct)'){
				break;
			}
		}
	}
	$response = send_ga_transaction_hit($shopify_order, $sources, [], true);
	if(empty($response->getDebugResponse()['hitParsingResult'][0]['valid'])){
		echo "ERROR IN HIT!";
		send_alert($db, 10, 'GA Hit Error on order number '.$shopify_order['order_number'], 'Skylar Alert - GA Hit Error');
		log_event($db, 'ANALYTICS', $shopify_order['order_number'], 'HIT_ERROR', $response->getDebugResponse(), '', 'CRON');
		continue;
	}
	print_r($response->getDebugResponse());
	send_ga_transaction_hit($shopify_order, $sources);
	log_event($db, 'ANALYTICS', $shopify_order['order_number'], 'HIT_SENT', $response->getDebugResponse(), '', 'CRON');
	$stmt_update_hit->execute([
		'now' => date('Y-m-d H:i:s'),
		'id' => $row['id'],
	]);
}


function send_ga_transaction_hit($shopify_order, $sources = [], $original_order = [], $debug=false) {
	$analytics = new TheIconic\Tracking\GoogleAnalytics\Analytics();

	$ga_client_id = get_order_attribute($shopify_order, '_ga_client_id');
	if(empty($ga_client_id) && strpos($shopify_order['note'], 'GAClientId:') !== false){
		$note_parts = explode(' ', $shopify_order['note']);
		foreach($note_parts as $note_part){
			if(strpos($note_part, 'GAClientId:') === false){
				continue;
			}
			$ga_client_id = str_replace('GAClientId:', '', $note_part);
			break;
		}
	}
	if(empty($ga_client_id) && !empty($original_order)){
		$ga_client_id = get_order_attribute($original_order, '_ga_client_id');
	}
	if(empty($ga_client_id)){
		$ga_client_id = uuidv4();
	}

	$analytics
//	->setDebug(true)
		->setProtocolVersion('1')
		->setTrackingId('UA-96604279-1')
		->setUserId($shopify_order['customer']['id'])
		->setClientId($ga_client_id)
		->setDataSource('test');

	if(!empty($shopify_order['browser_ip'])){
		$analytics->setIpOverride($shopify_order['browser_ip']);
	} else if(!empty($original_order) && !empty($original_order['browser_ip'])){
		$analytics->setIpOverride($original_order['browser_ip']);
	}

	// Set source
	if(!empty($sources)){
		$analytics
			->setCampaignSource($sources['source'])
			->setCampaignMedium($sources['medium'])
			->setCampaignName($sources['campaign']);
		if(!empty($sources['content'])){
			$analytics->setCampaignContent($sources['content']);
		}
	}

	$time_diff = time()-strtotime($shopify_order['created_at']);

	$analytics->setTransactionId($shopify_order['order_number'])
		->setQueueTime($time_diff*1000)
		->setAffiliation('Skylar Offline')
		->setRevenue($shopify_order['subtotal_price_set']['shop_money']['amount'])
		->setTax($shopify_order['total_tax_set']['shop_money']['amount'])
		->setShipping($shopify_order['total_shipping_price_set']['shop_money']['amount']);
	if(!empty($shopify_order['discount_codes'])){
		$analytics->setCouponCode($shopify_order['discount_codes'][0]['code']);
	}

	foreach($shopify_order['line_items'] as $index=>$item){
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
		->setEventLabel($shopify_order['order_number'])
		->setNonInteractionHit(1)
		->setDebug($debug)
		->sendEvent();
	return $response;
}