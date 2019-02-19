<?php
$upcoming_shipments = [
	[
		'ship_date_time' => strtotime(''),
		'items' => [
			[
				'handle' => 'scent-club-2019-march',
				'price' => 2500,
				'order_interval_frequency' => 1,
				'order_interval_unit' => 'month',
				'next_charge_scheduled_at' => strtotime('next month day 4'),
			],
		],
		'discounts' => [[
			'title' => 'Test',
			'value' => '500',
		]],
		'total' => '2000',
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
global $db;
$upcoming_shipments = generate_subscription_schedule($orders, $subscriptions, strtotime(date('Y-m-t',strtotime('+3 months'))));
$products_by_id = [];
$stmt = $db->prepare("SELECT * FROM products WHERE shopify_product_id=?");
foreach($upcoming_shipments as $upcoming_shipment){
	foreach($upcoming_shipment['items'] as $item){
		if(!array_key_exists($item['shopify_product_id'], $products_by_id)){
			$stmt->execute([$item['shopify_product_id']]);
			$products_by_id[$item['shopify_product_id']] = $stmt->fetch();
		}
	}
}
?>
<!-- <?print_r($upcoming_shipments);?> -->
{% assign portal_page = 'subscriptions' %}
{{ 'sc-portal.scss' | asset_url | stylesheet_tag }}
<div class="sc-portal-page sc-portal-{{ portal_page }} sc-portal-container">
	{% include 'sc-member-nav' %}
	<div class="sc-portal-content">
		<div class="sc-portal-innercontainer">
			<div class="sc-portal-title">Manage Membership</div>
			<div class="sc-portal-subtitle">Update Shipping Date and Frequency</div>
			<div class="sc-portal-box-list">
				<?php $index = -1;
				foreach($upcoming_shipments as $upcoming_shipment){
					$index++;
					?>
					<div class="sc-upcoming-shipment">
						<div class="sc-box-info">
							<span class="sc-box-shiplabel">Shipping Date</span>
							<span class="sc-box-date"><?=date('F j', $upcoming_shipment['ship_date_time']) ?></span>
						</div>
						<?php foreach($upcoming_shipment['items'] as $item){ ?>
							{% assign box_product = all_products['<?=$products_by_id[$item['shopify_product_id']]['handle']?>'] %}
							<div class="sc-box-item">
								<div class="sc-item-summary">
									<div class="sc-item-image">
										<img class="lazyload" data-srcset="{{ box_product.images.first | img_url: 100x100 }} 1x, {{ box_product.images.first | img_url: 200x200 }} 2x" />
									</div>
									<div>
										<?php if(is_scent_club($products_by_id[$item['shopify_product_id']])){ ?>
											<div class="sc-item-title">Monthly Scent Club</div>
											<div class="sc-item-subtitle">{{ box_product.variants.first.title }}</div>
											<div class="sc-swap-link"><a href="#">Swap Scent</a></div>
										<?php } else { ?>
											<div class="sc-item-title"><?=$item['product_title']?></div>
											<div class="sc-item-subtitle"><?=$item['variant_title']?></div>
										<?php } ?>
									</div>
								</div>
								<div class="sc-item-details">
									<?php if($index == 0){ ?>
										<div>
											<div class="sc-item-detail-label">Total</div>
											<div class="sc-item-detail-value">${{ <?=$item['price'] ?> | money_without_trailing_zeroes }}</div>
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
										<div class="sc-item-detail-value"><?=date('F j, Y', strtotime($item['next_charge_scheduled_at']))?></div>
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