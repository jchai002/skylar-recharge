<?php

global $db, $sc, $rc;

if(empty($shopify_order_id)){
	die("Please provide order ID!");
}

$order = $sc->get('orders/'.intval($shopify_order_id).'.json');
//$fulfillments = $sc->get('orders/'.intval($shopify_order_id).'/fulfillments.json');
$fulfillments = $order['fulfillments'];

if(!empty($shopify_line_item_id)){
	$fulfillments = array_filter($fulfillments, function($fulfillment) use($shopify_line_item_id) {
		return in_array($shopify_line_item_id, array_column($fulfillment['line_items'], 'id'));
	});
}

$url_values = [
	'tracking_numbers' => '',
	'order_number' => '',
	'service' => '',
	'dzip' => '',
	'order_date' => '',
	'ship_date' => '',
];

$redirect = "https://skylar.narvar.com/skylar/tracking/{{ fulfillment_tracking_code }}?tracking_numbers={{ fulfillment.tracking_number }}&order_number={{ order.order_name | remove: '#' }}&service={{ shipping_method_code }}{%- comment -%}&ozip=92704{%- endcomment -%}&dzip={{ order.shipping_address.zip }}&order_date={{ order.created_at}}&ship_date={{ fulfillment.created_at }}";