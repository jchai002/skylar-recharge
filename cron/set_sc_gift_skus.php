<?php
require_once(__DIR__.'/../includes/config.php');

$scent_club_variant_ids = $stmt = $db->query("SELECT v.shopify_id AS shopify_variant_id FROM products p
LEFT JOIN variants v ON p.id=v.product_id
WHERE p.type='Scent Club Gift'
AND p.deleted_at IS NULL
AND v.deleted_at IS NULL;")->fetchAll(PDO::FETCH_COLUMN);

print_r($scent_club_variant_ids);

// Load scent club gift subs
$subscriptions = [];
$start_time = microtime(true);
foreach($scent_club_variant_ids as $variant_id){
	echo "Getting subscriptions for variant ID ".$variant_id.PHP_EOL;
	$page_size = 250;
	$page = 0;
	do {
		$page++;
		$res = $rc->get("/subscriptions", [
			'page' => $page,
			'limit' => $page_size,
			'shopify_variant_id' => $variant_id,
			'status' => 'ACTIVE',
		]);
		foreach($res['subscriptions'] as $subscription){
			$subscriptions[] = $subscription;
		}
		echo "Adding ".count($res['subscriptions'])." to array - total: ".count($subscriptions).PHP_EOL;
		echo "Rate: ".(count($subscriptions) / (microtime(true) - $start_time))." subs/s".PHP_EOL;
	} while(count($res['subscriptions']) >= $page_size);
}
echo "Total: ".count($subscriptions).PHP_EOL;

$starting_point = 0;
$num_to_process = count($subscriptions);
$start_time = microtime(true);
echo "Starting updates $starting_point - ".($starting_point+$num_to_process).PHP_EOL;
foreach($subscriptions as $index=>$subscription){
	$monthly_scent_info = sc_get_monthly_scent($db, strtotime($subscription['next_charge_scheduled_at']), true);
	if(empty($monthly_scent_info)){
		continue;
	}
	if(!$subscription['sku_override'] || $subscription['sku'] != $monthly_scent_info['sku']){
		// Sku doesn't match or isn't overridden, set it
		echo "Updating SKU on sub ".$subscription['id']." address ".$subscription['address_id'].PHP_EOL;
		$res = $rc->put('/subscriptions/'.$subscription['id'], [
			'sku' => $monthly_scent_info['sku'],
		]);
		print_r($res);
	}
}