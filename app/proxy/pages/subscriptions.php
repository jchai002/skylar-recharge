<?php
header('Content-Type: application/liquid');
$upcoming_shipments = [
	[
		'ship_date_time' => strtotime(''),
		'items' => [
			[
				'scent_club_product' => true,
				'handle' => 'scent-club-2019-march',
				'price' => 2500,
				'price_formatted' => '$25',
				'order_interval_frequency' => 1,
				'order_interval_unit' => 'month',
				'next_charge_scheduled_at' => strtotime('next month day 4'),
			],
		],
		'discounts' => [[
			'title' => 'Test',
			'value' => '500',
			'value_formatted' => '$5',
		]],
		'total' => '2000',
		'total_formatted' => '$20',
	],
];
global $rc;
$res = $rc->get('/subscriptions', [
	'shopify_customer_id' => $_REQUEST['c'],
	'status' => 'ACTIVE',
]);
$subscriptions = $res['subscriptions'];
if(!empty($subscriptions)){
	$rc_customer_id = $subscriptions[0]['customer_id'];
} else {
	$res = $rc->get('/customers', [
		'shopify_customer_id' => $_REQUEST['c'],
	]);
	$customer = $res['customers'];
	if(!empty($customer)){
		$rc_customer_id = $customer['id'];
	}
}
if(!empty($rc_customer_id)){
	$res = $rc->get('/orders', [
		'customer_id' => $rc_customer_id,
		'status' => 'QUEUED',
	]);
	$orders = $res['orders'];
} else {
	$orders = [];
}
//print_r($orders);
$upcoming_shipments = generate_subscription_schedule($orders, $subscriptions);

function generate_subscription_schedule($orders, $subscriptions, $max_time = null){
	$schedule = [];

	$max_time = empty($max_time) ? strtotime('+12 months') : $max_time;

	foreach($orders as $order){
		$order_time = strtotime($order['scheduled_at']);
		if($order_time > $max_time){
			continue;
		}
		$date = date('Y-m-d', $order_time);
		if(empty($schedule[$date])){
			$schedule[$date] = [];
		}
		$schedule[$date][] = $order;
	}
	foreach($subscriptions as $subscription){
		$next_charge_time = strtotime($subscription['next_charge_scheduled_at']);

		while($next_charge_time < $max_time){
			$date = date('Y-m-d', $next_charge_time);
			if(empty($schedule[$date])){
				$schedule[$date] = [];
			}
			$schedule[$date][] = $subscription;
			$next_charge_time = strtotime($date.' +'.$subscription['order_interval_frequency'].' '.$subscription['order_interval_unit']);
			if($subscription['order_interval_unit'] == 'month' && !empty($subscription['order_day_of_month'])){
				$next_charge_time = strtotime(date('Y-m-'.$subscription['order_day_of_month'], $next_charge_time));
			} else if($subscription['order_interval_unit'] == 'week' && !empty($subscription['order_day_of_week'])){
				// TODO if needed
			}
		}
	}
	ksort($schedule);
	return $schedule;
}
?>
{% assign portal_page = 'subscriptions' %}
{{ 'sc-portal.scss' | asset_url | stylesheet_tag }}
<div class="sc-portal-page sc-portal-{{ portal_page }} sc-portal-container">
	{% include 'sc-member-nav' %}
	<div class="sc-portal-content">
		<div class="sc-portal-innercontainer">
			<div class="sc-portal-title">Manage Membership</div>
			<div class="sc-portal-subtitle">Update Shipping Date and Frequency</div>
			<div class="sc-portal-box-list">
				<?php foreach($upcoming_shipments as $index=>$upcoming_shipment){ ?>
					<div class="sc-upcoming-shipment">
						<div class="sc-box-info">
							<span class="sc-box-shiplabel">Shipping Date</span>
							<span class="sc-box-date"><?=$upcoming_shipment['ship_date']?></span>
						</div>
						<?php foreach($upcoming_shipment['items'] as $item){ ?>
							{% assign box_product = all_products['<?=$item['handle']?>'] %}
							<div class="sc-box-item">
								<div class="sc-item-summary">
									<div class="sc-item-image">
										<img class="lazyload" data-srcset="{{ box_product.images.first | img_url: 100x100 }} 1x, {{ box_product.images.first | img_url: 200x200 }} 2x" />
									</div>
									<div>
										<?php if($item['scent_club_product']){ ?>
											<div class="sc-item-title">Monthly Scent Club</div>
											<div class="sc-item-subtitle">{{ box_product.variants.first.title }}</div>
											<div class="sc-swap-link"><a href="#">Swap Scent</a></div>
										<?php } else { ?>
											<div class="sc-item-title">{{ box_product.title }}</div>
											<div class="sc-item-subtitle">{{ box_product.variants.first.title }}</div>
										<?php } ?>
									</div>
								</div>
								<div class="sc-item-details">
									<?php if($index == 0){ ?>
										<div>
											<div class="sc-item-detail-label">Total</div>
											<div class="sc-item-detail-value"><?=$item['price_formatted']?></div>
										</div>
									<?php } ?>
									<div>
										<div class="sc-item-detail-label">Delivery</div>
										<div class="sc-item-detail-value">
											<?php if($item['order_interval_frequency'] == '1'){ ?>
												Every <?=$item['order_interval_unit']?>
											<?php } else { ?>
												Every <?=$item['order_interval_frequency']?> <?=$item['order_interval_unit']?>s
											<?php } ?>
										</div>
									</div>
									<div>
										<div class="sc-item-detail-label">Next Charge</div>
										<div class="sc-item-detail-value"><?=date('F j, Y', $item['next_charge_scheduled_at'])?></div>
									</div>
								</div>
							</div>
						<?php } ?>
					</div>
				<?php } ?>
			</div>
			<div class="sc-load-more">
				<a href="#" class="action_button">Load More</a>
			</div>
		</div>
	</div>
</div>
{{ 'sc-portal.js' | asset_url | script_tag }}
<script>
</script>