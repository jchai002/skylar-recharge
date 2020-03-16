<?php

global $db, $sc, $rc;

$tracking_code = 'carrier';
$query_values = [
	'tracking_numbers' => '',
	'order_number' => '',
	'service' => 'unknown',
	'dzip' => '',
	'order_date' => '',
	'ship_date' => '',
];
$narvar_codes = [
	'PriorityDdpDelcon' => ['service' => '', 'carrier' => ''],
	'DHL WW Express' => ['service' => '', 'carrier' => 'DHL'],
	'UPS Standard to Canada' => ['service' => 'UG', 'carrier' => 'UPS'],
	'Standard Scent Club' => ['service' => 'MI', 'carrier' => 'UPS'],
	'Standard Weight-based' => ['service' => 'MI', 'carrier' => 'UPS'],
	'Free Standard Shipping (3-7 business days)' => ['service' => 'MI', 'carrier' => 'UPS'],
	'Standard Shipping (3-7 business days)' => ['service' => 'MI', 'carrier' => 'UPS'],
	'US Next Day' => ['service' => 'E1', 'carrier' => 'UPS'],
	'US 2 Day' => ['service' => 'E2', 'carrier' => 'UPS'],
	'Next Day Shipping (1 business day)' => ['service' => 'E1', 'carrier' => 'UPS'],
	'2-Day Shipping (2 business days)' => ['service' => 'E2', 'carrier' => 'UPS'],
	'AKHI Legacy Shipping' => ['service' => 'FC', 'carrier' => 'USPS'],
];
$narvar_carrier_codes = [
	'UPS Ground' => 'UPS',
	'Fedex Ground' => 'Fedex',
];

$base_url = "https://skylar.narvar.com/skylar/tracking/";

if(!empty($shopify_order_id)){
	$order = $sc->get('orders/'.intval($shopify_order_id).'.json');
	//$fulfillments = $sc->get('orders/'.intval($shopify_order_id).'/fulfillments.json');
	$fulfillments = $order['fulfillments'];

	if(!empty($shopify_line_item_id)){
		$fulfillments = array_filter($fulfillments, function($fulfillment) use($shopify_line_item_id) {
			return in_array($shopify_line_item_id, array_column($fulfillment['line_items'], 'id'));
		});
	}
}

if(!empty($order)){
	$query_values['order_number'] = str_replace('#', '', $order['name']);
	$query_values['order_date'] = $order['created_at'];
	$query_values['dzip'] = $order['shipping_address']['zip'];
//	$query_values['ozip'] = $order['shipping_address']['zip']; // TODO: Set to origin zip once cin7 integration makes it available
	$query_values['service'] = $narvar_codes[$order['shipping_methods'][0]['code']]['service'] ?? 'unknown';
	$tracking_code = $narvar_codes[$order['shipping_methods'][0]['code']]['carrier'] ?? 'carrier';
}
if(!empty($fulfillments)){
	$fulfillment = $fulfillments[0];
	$query_values['tracking_numbers'] = $fulfillment['tracking_number'];
	$query_values['ship_date'] = $fulfillment['created_at'];
	if($order['shipping_methods'][0]['code'] == 'PriorityDdpDelcon'){
		echo $fulfillment['tracking_urls'][0];
		exit;
	}
	print_r($fulfillment);
}

$redirect = "https://skylar.narvar.com/skylar/tracking/{{ fulfillment_tracking_code }}?tracking_numbers={{ fulfillment.tracking_number }}&order_number={{ order.order_name | remove: '#' }}&service={{ shipping_method_code }}{%- comment,&ozip=92704{%- endcomment,&dzip={{ order.shipping_address.zip }}&order_date={{ order.created_at}}&ship_date={{ fulfillment.created_at }}";

$redirect = $base_url.$tracking_code."?".http_build_query($query_values);

echo $redirect;