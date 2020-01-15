<?php
require_once(__DIR__.'/../../includes/config.php');

$scent_club_variant_ids = $db->query("SELECT v.shopify_id AS shopify_variant_id FROM products p
LEFT JOIN variants v ON p.id=v.product_id
WHERE p.type='Scent Club Gift'
AND p.deleted_at IS NULL
AND v.deleted_at IS NULL;")->fetchAll(PDO::FETCH_COLUMN);

print_r($scent_club_variant_ids);

// Load SC gift subscriptions with ship date of two months from now
$min_time = get_month_by_offset(2);
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
			if(strtotime($subscription['next_charge_scheduled_at']) < $min_time){
				continue;
			}
			$subscriptions[] = $subscription;
		}
		echo "Adding ".count($res['subscriptions'])." to array - total: ".count($subscriptions).PHP_EOL;
		echo "Rate: ".(count($subscriptions) / (microtime(true) - $start_time))." subs/s".PHP_EOL;
	} while(count($res['subscriptions']) >= $page_size);
}
echo "Total: ".count($subscriptions).PHP_EOL;

$new_date = date('Y-m-d', offset_date_skip_weekend(get_next_month()));
foreach($subscriptions as $subscription){
	echo "Checking ".$subscription['id'].'... ';
	// Check if _subscription_month property is in the past
	$subscription_month = get_oli_attribute($subscription, '_subscription_month');
	if(empty($subscription_month)){
		$subscription_month = date('Y-m', strtotime($subscription['created_at']));
	}
	if(strtotime($subscription_month.'-01') >= $min_time){
		echo "Skipping, sub was intended for later".PHP_EOL;
		continue;
	}
	// If so, that's a bad skip. Move to next month
	echo "originally ".$subscription_month.", updating to $new_date ... ";
	$res = $rc->post('/subscriptions/'.$subscription['id'].'/set_next_charge_date', [
		'date' => $new_date,
	]);
	if(empty($res['subscription'])){
		print_r($res);
		die("Couldn't update subscription month");
	}
	echo $res['subscription']['next_charge_scheduled_at'].PHP_EOL;
}
