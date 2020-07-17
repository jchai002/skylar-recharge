<?php

global $sc, $db, $rc;


$quantity = intval($_REQUEST['quantity']);

// Check if the new quantity is available
$subscription = get_rc_subscription($db, $subscription_id, $rc, $sc);
$subscription_uri = $subscription['status'] == 'ONETIME' ? 'onetime' : 'subscription';

if($quantity <= 0){
	if(empty($subscription) || $subscription['status'] == 'ONETIME'){
		$res = $rc->delete('/onetimes/'.intval($_REQUEST['id']));
		$stmt = $db->prepare("UPDATE rc_subscriptions SET deleted_at=? WHERE recharge_id=?");
		$stmt->execute([
			date('Y-m-d H:i:s'),
			intval($_REQUEST['id']),
		]);
	} else {
		$res = $this_res = $rc->post('/subscriptions/'.intval($_REQUEST['id']).'/cancel',[
			'cancellation_reason' => $_REQUEST['reason'] ?? 'Item removed from customer account',
			'send_email' => 'false',
			'commit_update' => true,
		]);
		insert_update_rc_subscription($db, $this_res['subscription'], $rc, $sc);
	}
	log_event($db, 'SUBSCRIPTION', $_REQUEST['id'], 'CANCEL', 'Item removed from customer account', 'Cancelled via user account: '.json_encode($res), 'Customer');

	if(!empty($res['error'])){
		echo json_encode([
			'success' => false,
			'res' => $res['error'],
		]);
	} else {
		echo json_encode([
			'success' => true,
			'res' => $res,
		]);
	}
} else {
	$qty_change = $quantity - $subscription['quantity'];
	if($qty_change > 0){
		if(!is_inventory_available($db, $subscription['shopify_variant_id'], $qty_change)){
			die(json_encode([
				'success' => false,
				'error' => "Not enough inventory available",
				'res' => [],
			]));
		}
	}
	$res = $rc->put($subscription_uri.'s/'.intval($subscription_id), [
		'quantity' => $quantity,
	]);

	if(!empty($res[$subscription_uri])){
		insert_update_rc_subscription($db, $res[$subscription_uri], $rc, $sc);
	}
	if(!empty($res['error'])){
		echo json_encode([
			'success' => false,
			'error' => $res['error'],
			'res' => $res,
		]);
	} else {
		echo json_encode([
			'success' => true,
			'res' => $res,
			'quantity' => $res[$subscription_uri]['quantity'],
		]);
	}
}

function is_inventory_available(PDO $db, $shopify_variant_id, $quantity = 1){
	$variant = get_variant($db, $shopify_variant_id);
	$product = get_product($db, $variant['shopify_product_id']);
	if($product['type'] != 'Scent Club Month'){
		return true;
	}
	$stmt = $db->prepare("SELECT v.id, v.shopify_id, v.sku, SUM(csu.available+csu.virtual) AS inventory_quantity, v.title, IFNULL(held_quantity,0) AS held_quantity, SUM(csu.available+csu.virtual)-(IFNULL(held_quantity,0))-20 AS stock_available_unreserved
FROM sc_products scp
LEFT JOIN variants v ON scp.variant_id=v.id
LEFT JOIN products p ON v.product_id = p.id
LEFT JOIN cin_product_options cpo ON v.sku=cpo.sku
LEFT JOIN (
	SELECT scp.variant_id, IFNULL(SUM(quantity), 0) AS held_quantity
	FROM sc_products scp
	LEFT JOIN rc_subscriptions rcs ON rcs.variant_id=scp.variant_id
	WHERE rcs.status IN ('ACTIVE', 'ONETIME')
	AND rcs.deleted_at IS NULL
	AND rcs.next_charge_scheduled_at >= '".date('Y-m-d H:i:s')."'
	GROUP BY scp.variant_id
) rq ON rq.variant_id=scp.variant_id
LEFT JOIN cin_stock_units csu ON cpo.id=csu.cin_product_option_id AND csu.cin_branch_id IN(3, 23755)
WHERE v.shopify_id = ?
;");
	$stmt->execute([$shopify_variant_id]);
	$row = $stmt->fetch();
	if($row['stock_available_unreserved'] < $quantity){
		return false;
	}
	return true;
}