<?php
require_once(__DIR__.'/../../includes/config.php');


echo "SELECT * FROM rc_subscriptions rcs
LEFT JOIN variants v ON rcs.variant_id=v.id
LEFT JOIN rc_addresses rca ON rcs.address_id=rca.id
LEFT JOIN rc_customers rcc ON rca.rc_customer_id=rcc.id
LEFT JOIN customers c ON rcc.customer_id=c.id
LEFT JOIN orders o ON o.customer_id=c.id AND o.created_at >= '2020-02-21'
LEFT JOIN order_line_items oli ON o.id=oli.order_id
WHERE next_charge_scheduled_at = '2020-03-02'
AND rcs.variant_id=88568
AND oli.id IS NOT NULL
AND oli.sku=v.sku
AND rcs.deleted_at IS NULL
;";



function sc_is_address_in_blackout_custom(PDO $db, $address_id){
	$next_month_scent = sc_get_monthly_scent($db, get_next_month());
	if(empty($next_month_scent)){
		return false;
	}
	global $_stmt_cache;
	if(empty($_stmt_cache['blackout_check'])){
		$_stmt_cache['blackout_check'] = $db->prepare("SELECT o.shopify_id AS order_id FROM rc_addresses rca
LEFT JOIN rc_customers rcc ON rca.rc_customer_id=rcc.id
LEFT JOIN customers c ON rcc.customer_id=c.id
LEFT JOIN orders o ON c.id=o.customer_id
WHERE o.tags like '%Scent Club Blackout%'
AND rca.recharge_id = ?");
	}
	$_stmt_cache['blackout_check']->execute([$address_id]);
	if($_stmt_cache['blackout_check']->rowCount() != 0){
		return true;
	}
	return false;
}