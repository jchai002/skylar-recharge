<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if(strpos(getcwd(), 'production') !== false){
    define('ENV_DIR', 'skylar-recharge-production');
} else {
    define('ENV_DIR', 'skylar-recharge-staging');
}

// Variants that are allowed to create subscriptions, eventually we won't use this but it's a good safeguard for now
$subscription_variant_ids = ['5672401895455'];
$ids_by_scent = [
	'arrow'  => ['variant' => 31022048003,   'product' => 8985085187],
	'capri'  => ['variant' => 5541512970271, 'product' => 443364081695],
	'coral'  => ['variant' => 26812012355,   'product' => 8215300931],
	'isle'   => ['variant' => 31022109635,   'product' => 8985117187],
	'meadow' => ['variant' => 26812085955,   'product' => 8215317379],
];

if (!function_exists('getallheaders')){ 
    function getallheaders(){ 
        $headers = []; 
       foreach ($_SERVER as $name => $value){ 
           if (substr($name, 0, 5) == 'HTTP_'){ 
               $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value; 
           } 
       } 
       return $headers; 
    } 
}
// Might be better to just group them by address ID
function group_subscriptions($subscriptions, $addresses){
	$subscription_groups = [];
	foreach($subscriptions as $subscription){
		if(!in_array($subscription['status'], ['ACTIVE', 'ONETIME']) || empty($subscription['next_charge_scheduled_at'])){
			continue;
		}
		$next_charge_time = strtotime($subscription['next_charge_scheduled_at']);
		$next_charge_date = date('m/d/Y', $next_charge_time);
		$frequency = $subscription['status'] == 'ONETIME' ? '' : $subscription['order_interval_frequency'].$subscription['order_interval_unit'];
		$group_key = $subscription['status'].$next_charge_date.$frequency.$subscription['address_id'];
		if(!array_key_exists($group_key, $subscription_groups)){
			$subscription_groups[$group_key] = [
				'subscriptions' => [],
				'status' => $subscription['status'],
				'frequency' => $frequency,
				'order_interval_frequency' => $subscription['order_interval_frequency'],
				'onetime' => $subscription['status'] == 'ONETIME',
				'next_charge_date' => $next_charge_date,
				'next_charge_time' => $next_charge_time,
				'address_id' => $subscription['address_id'],
				'address' => $addresses[$subscription['address_id']],
			];
		}
		$subscription_groups[$group_key]['items'][] = [
			'id' => $subscription['id'],
			'product_id' => $subscription['shopify_product_id'],
			'variant_id' => $subscription['shopify_variant_id'],
			'quantity' => $subscription['quantity'],
			'product_title' => $subscription['product_title'],
			'variant_title' => $subscription['variant_title'],
			'title' => trim($subscription['product_title'] . ' ' .$subscription['variant_title']),
			'price' => $subscription['price'],
		];
	}

// Dynamic title generation, counts, totals
	foreach($subscription_groups as $group_key => $subscription_group){
		if(count($subscription_group['items']) == 1 && !empty($subscription_group['items'][0]['product_title'])){
			$subscription_group['title'] = trim($subscription_group['items'][0]['product_title'].' '.$subscription_group['items'][0]['variant_title']);
			$subscription_group['title'] .= $subscription_group['onetime'] ? ' Order' : ' Auto Renewal';
		} else {
			$subscription_group['title'] = $subscription_group['onetime'] ? 'Scheduled Order' : 'Scent Auto Renewal';
		}
		$subscription_group['id'] = $subscription_group['ids'] = implode(',',array_column($subscription_group['items'], 'id'));
		$subscription_group['total_quantity'] = array_sum(array_column($subscription_group['items'], 'quantity'));
		$subscription_group['total_price'] = number_format(array_sum(array_column($subscription_group['items'], 'price')), 2);
		$subscription_groups[$group_key] = $subscription_group;
	}

	uasort($subscription_groups, function($a, $b){
		if($a['next_charge_time'] == $b['next_charge_time']){
			return 0;
		}
		return $a['next_charge_time'] > $b['next_charge_time'] ? 1 : -1;
	});
	return array_values($subscription_groups);
}