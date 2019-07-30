<?php
require_once('../includes/config.php');

$stmt = $db->prepare("SELECT * FROM fulfillments WHERE delivered_at >= ? AND delivery_processed_at IS NULL");
$stmt->execute([date('Y-m-d H:i:s', time()-(60*60*24*7))]);

$stmt_get_order_lines = $db->prepare("SELECT oli.id, p.shopify_id AS product_id FROM order_line_items oli
LEFT JOIN variants v ON oli.variant_id=v.id
LEFT JOIN products p ON v.product_id=p.id
WHERE oli.fulfillment_id=?");

$stmt_get_subscription_id = $db->prepare("SELECT s.recharge_id AS id FROM skylar.ac_orders aco
LEFT JOIN rc_subscriptions s ON aco.followup_subscription_id=s.id
WHERE s.id IS NOT NULL AND s.deleted_at IS NULL
AND aco.order_line_item_id=?");

$stmt_get_attributes = $db->prepare("SELECT o.attributes FROM orders o LEFT JOIN order_line_items oli ON oli.order_id=o.id WHERE oli.fulfillment_id=?");

$stmt_mark_processed = $db->prepare("UPDATE fulfillments SET delivery_processed_at=:now WHERE shopify_id=:id");
foreach($stmt->fetchAll() as $fulfillment){

	$stmt_get_order_lines->execute([$fulfillment['id']]);
	foreach($stmt_get_order_lines->fetchAll() as $line_item){

		$stmt_get_subscription_id->execute([$line_item['id']]);
		if($stmt_get_subscription_id->rowCount() == 0){
			continue;
		}
		// Has AC order, update ship date
		$subscription_id = $stmt_get_subscription_id->fetchColumn();
		$rc->put('/onetimes/'.$subscription_id, [
			'next_charge_scheduled_at' => date('Y-m-d', offset_date_skip_weekend(strtotime('+14 days'))),
			'properties' => [
				'_ac_product' => $line_item['product_id'],
				'_ac_delivered' => 1,
			]
		]);
	}


	$stmt_get_attributes->execute([$fulfillment['id']]);
	$cart_attributes = json_decode($stmt_get_attributes->fetchColumn(), true);

	// Gift message
	if(!empty($cart_attributes['gift_message']) && !empty($cart_attributes['gift_message_email'])){
		$ch = curl_init('https://a.klaviyo.com/api/v2/list/HSQctC/subscribe');

		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => json_encode([
				'api_key' => 'pk_4c31e0386c15cca46c19dac063c013054c',
				'profiles' => [
					[
						'email' => $cart_attributes['gift_message_email'],
						'$source' => 'Gift Message'
					]
				],
			]),
			CURLOPT_HTTPHEADER => [
				'api-key: pk_4c31e0386c15cca46c19dac063c013054c',
				'Content-Type: application/json',
			],
		]);
		$res = curl_exec($ch);
		$stmt = $db->prepare("INSERT INTO event_log (category, action, value, value2, note) VALUES ('KLAVIYO', 'SUBSCRIBE', :email, :list, :response)");
		$stmt->execute([
			'email' => $cart_attributes['gift_message_email'],
			'list' => 'HSQctC',
			'response' => $res,
		]);
		$res = json_decode($res, true);
		var_dump($res);

		$ch = curl_init("https://a.klaviyo.com/api/v1/email-template/HrK7rW/send");
		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => [
				'api_key' => 'pk_4c31e0386c15cca46c19dac063c013054c',
				'from_email' => 'hello@skylar.com',
				'from_name' => 'Skylar',
				'subject' => 'Your Gift Has Arrived!',
				'to' => json_encode([
					['email' => $cart_attributes['gift_message_email']],
					['email' => 'tim@timnolansolutions.com'],
				]),
				'context' => json_encode([
					'gift_message' => $cart_attributes['gift_message'],
				]),
			]
		]);
		$res = curl_exec($ch);
		$stmt = $db->prepare("INSERT INTO event_log (category, action, value, value2, note) VALUES ('KLAVIYO', 'EMAIL_SENT', :email, :message, :response)");
		$stmt->execute([
			'email' => $cart_attributes['gift_message_email'],
			'message' => $cart_attributes['gift_message'],
			'response' => $res,
		]);
		$res = json_decode($res, true);
		var_dump($res);
	}


	$stmt_mark_processed->execute([
		'now' => date('Y-m-d H:i:s'),
		'id' => $fulfillment['id'],
	]);
}
