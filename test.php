<?php
require_once(__DIR__.'/includes/config.php');

$sc = new ShopifyClient();

$shopify_fulfillment = $sc->get('/admin/orders/1375438307415/fulfillments/1253410472023.json');

if(!empty($shopify_fulfillment['tracking_number'])){
	$stmt = $db->prepare("SELECT 1 FROM ep_trackers WHERE tracking_code=?");
	$stmt->execute([$shopify_fulfillment['tracking_number']]);
	if(true || $stmt->rowCount() < 1){
		try {
			$tracker = \EasyPost\Tracker::create([
				'tracking_code' => $shopify_fulfillment['tracking_number'],
				'carrier' => 'UPS Mail Innovations',
			]);
			print_r($tracker);
		} catch(\Throwable $e){
			var_dump($e);
			log_event($db, 'EXCEPTION', json_encode([$e->getLine(), $e->getFile(), $e->getCode(), $e->getMessage(), $e->getTraceAsString()]), 'fulfillment_tracker_create', json_encode($shopify_fulfillment), '', '');
		}
	}
}