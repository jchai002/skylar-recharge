<?php
if(!empty($_REQUEST['months'])){
	$more = intval($_REQUEST['months']);
}
global $rc;
// TODO: Load skipped charges too
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
$onetimes = [];
$orders = [];
$charges = [];
if(!empty($rc_customer_id)){
	$res = $rc->get('/orders', [
		'customer_id' => $rc_customer_id,
		'status' => 'QUEUED',
	]);
	$orders = $res['orders'];
	$res = $rc->get('/charges', [
		'customer_id' => $rc_customer_id,
		'date_min' => date('Y-m-d'),
	]);
	$charges = $res['charges'];
	$res = $rc->get('/onetimes', [
		'customer_id' => $rc_customer_id,
	]);
	foreach($res['onetimes'] as $onetime){
		// Fix for api returning non-onetimes
		if($onetime['status'] == 'ONETIME'){
			$onetimes[] = $onetime;
		}
	}
}
global $db;
$months = empty($more) ? 3 : $more;
$upcoming_shipments = generate_subscription_schedule($orders, $subscriptions, $onetimes, $charges, strtotime(date('Y-m-t',strtotime("+$months months"))));
$products_by_id = [];
$stmt = $db->prepare("SELECT * FROM products WHERE shopify_id=?");
foreach($upcoming_shipments as $upcoming_shipment){
	foreach($upcoming_shipment['items'] as $item){
		if(!array_key_exists($item['shopify_product_id'], $products_by_id)){
			$stmt->execute([$item['shopify_product_id']]);
			$products_by_id[$item['shopify_product_id']] = $stmt->fetch();
		}
	}
}
?>
<?php if(!empty($more)){ ?>
{% layout 'raw' %}
<?php } else { ?>
<!-- <?php print_r($upcoming_shipments);?> -->
{% assign portal_page = 'subscriptions' %}
{{ 'sc-portal.scss' | asset_url | stylesheet_tag }}
<div class="sc-portal-page sc-portal-{{ portal_page }} sc-portal-container">
	{% include 'sc-member-nav' %}
	<div class="sc-portal-content">
		<?php } ?>
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
							<div class="sc-box-item<?= !empty($item['skipped']) ? ' sc-box-skipped' : '' ?>"
								 data-address-id="<?=$item['address_id']?>"
								 data-variant-id="<?=empty($item['shopify_variant_id']) ? '{{ box_product.variants.first.id }}' : $item['shopify_variant_id']?>"
								 data-date="<?= date('Y-m-d', $upcoming_shipment['ship_date_time'])?>">
								<div class="sc-item-summary">
									<div class="sc-item-image">
										<img class="lazyload" data-srcset="{{ box_product.images.first | img_url: '100x100' }} 1x, {{ box_product.images.first | img_url: '200x200' }} 2x" />
									</div>
									<div>
										<?php if(is_scent_club_month($products_by_id[$item['shopify_product_id']])){ ?>
											<div class="sc-item-title">Monthly Scent Club</div>
											<div class="sc-item-subtitle">{{ box_product.variants.first.title }}</div>
											<div><a class="sc-swap-link" href="#"><img src="{{ 'icon-swap.svg' | file_url }}" /> <span>Swap Scent</span></a></div>
										<?php } else if(is_scent_club($products_by_id[$item['shopify_product_id']])){ ?>
											<div class="sc-item-title">Monthly Scent Club</div>
											<div class="sc-item-subtitle"></div>
										<?php } else if(is_scent_club_swap($products_by_id[$item['shopify_product_id']])){ ?>
											<div class="sc-item-title"><?=$item['product_title']?></div>
											<div class="sc-item-subtitle"><?=$item['variant_title']?></div>
											<div><a class="sc-swap-link" href="#"><img src="{{ 'icon-swap.svg' | file_url }}" /> <span>Swap Scent</span></a></div>
										<?php } else { ?>
											<div class="sc-item-title"><?= empty($item['product_title']) ? $item['title'] : $item['product_title']?></div>
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
											<?php if(is_scent_club_any($products_by_id[$item['shopify_product_id']])){ ?>
												Every Month
											<?php } else if(empty($item['order_interval_frequency'])){ ?>
												Once
											<?php } else if($item['order_interval_frequency'] == '1'){ ?>
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
				<a href="#" class="action_button" onclick="ScentClub.load_schedule(<?=$months+3?>); return false;">Load More</a>
			</div>
		</div>
		<?php if(empty($more)){ ?>
	</div>
</div>
{{ 'sc-portal.js' | asset_url | script_tag }}
<script>
	function bind_events(){
        $('.sc-swap-link').click(function(e){
            e.preventDefault();
            var box_item = $(this).closest('.sc-box-item');
            $('#sc-member-swap').data('address-id', box_item.data('address-id'));
            $('#sc-member-swap').data('date', box_item.data('date'));
            $('#sc-member-swap .sc-swap-option').removeClass('active').filter('[data-variant-id='+box_item.data('variant-id')+']').addClass('active');
            $('#sc-member-swap').data('mmenu').open();
        });
	}
</script>
<?php } ?>