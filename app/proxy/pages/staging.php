<?php

global $rc;
$res = $rc->get('/subscriptions', [
	'shopify_customer_id' => $_REQUEST['c'],
	'status' => 'ACTIVE',
]);
$subscriptions = [];
$onetimes = [];
$orders = [];
$charges = [];
$customer = [];
if(!empty($res['subscriptions'])){
	$subscriptions = $res['subscriptions'];
	$rc_customer_id = $subscriptions[0]['customer_id'];
} else {
	$res = $rc->get('/customers', [
		'shopify_customer_id' => $_REQUEST['c'],
	]);
	if(!empty($res['customers'])){
		$customer = $res['customers'][0];
		if(!empty($customer)){
			$rc_customer_id = $customer['id'];
		}
	}
}
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
		if(empty($onetime['status']) || $onetime['status'] == 'ONETIME'){
			$onetimes[] = $onetime;
		}
	}
	echo "<!-- ";
	print_r($res);
	echo " -->";
}
global $db;
$months = empty($more) ? 3 : $more;
$upcoming_shipments = generate_subscription_schedule($db, $orders, $subscriptions, $onetimes, $charges, strtotime(date('Y-m-t',strtotime("+$months months"))));
$products_by_id = [];
$stmt = $db->prepare("SELECT * FROM products WHERE shopify_id=?");
$upcoming_box = false;
foreach($upcoming_shipments as $upcoming_shipment){
	foreach($upcoming_shipment['items'] as $item){
		if(!array_key_exists($item['shopify_product_id'], $products_by_id)){
			$stmt->execute([$item['shopify_product_id']]);
			$products_by_id[$item['shopify_product_id']] = $stmt->fetch();
			if(empty($upcoming_box) && is_scent_club_any($products_by_id[$item['shopify_product_id']])){
				$upcoming_box = $upcoming_shipment;
			}
		}
	}
}

$recommended_products = sc_get_profile_products(sc_get_profile_data($db, $rc, $_REQUEST['c']));
sc_conditional_billing($rc, $_REQUEST['c']);
?>
<!--
<?php print_r($upcoming_box); ?>
-->
{% assign portal_page = 'my_box' %}
{{ 'sc-portal.scss.css' | asset_url | stylesheet_tag }}
<?php if(!empty($upcoming_box['charge'])){ ?>
	{% assign add_to_box_charge_id = '<?=$upcoming_box['charge']['id']?>' %}
<?php } ?>
<div class="sc-portal-page sc-portal-{{ portal_page }} sc-portal-container">
	{% include 'sc-member-nav' %}
	<div class="sc-portal-content">
		<?php if(empty($upcoming_box)){ ?>
			<div class="sc-portal-innercontainer">
				<div class="sc-portal-title">You Aren't A Member!</div>
				<div>
					<a href="/pages/scent-club">Click Here To Learn More</a>
				</div>
			</div>
		<?php } else { ?>
			<div class="sc-portal-innercontainer">
				<div class="sc-portal-title">Your Next Skylar Box</div>
				<div class="sc-portal-subtitle">The next box that you'll be charged for</div>
				<div class="sc-portal-nextbox">
					<?php foreach($upcoming_box['items'] as $item){ ?>
						{% assign box_product = all_products['<?=$products_by_id[$item['shopify_product_id']]['handle']?>'] %}
						{% assign picked_variant_id = <?=$item['shopify_variant_id']?> | plus: 0 %}
						{% assign box_variant = box_product.variants.first %}
						{% for svariant in box_product.variants %}
						{% if svariant.id == picked_variant_id %}
						{% assign box_variant = svariant %}
						{% endif %}
						{% endfor %}
						<div class="sc-box-item<?= !empty($item['skipped']) ? ' sc-box-skipped' : '' ?>"
							 data-address-id="<?=$item['address_id']?>"
							 data-variant-id="<?=empty($item['shopify_variant_id']) ? '{{ box_product.variants.first.id }}' : $item['shopify_variant_id']?>"
							 data-date="<?= date('Y-m-d', $upcoming_shipment['ship_date_time'])?>"
							 <?php if(is_scent_club($products_by_id[$item['shopify_product_id']])){ ?>
								 data-master-image="{{ 'sc-logo.svg' | file_url }}"
							 <?php } else { ?>
								 data-master-image="{% if box_variant.image %}{{ box_variant | img_url: 'master' }}{% else %}{{ box_product | img_url: 'master' }}{% endif %}"
							 <?php } ?>
							 data-month-text="<?=date('F', $upcoming_shipment['ship_date_time'])?>"
							 data-subscription-id="<?=$item['subscription_id']?>"
							<?= !empty($item['charge']) ? 'data-charge-id="'.$item['charge']['id'].'"' : '' ?>
							 data-type="<?=$item['type']?>"
							<?= is_scent_club($products_by_id[$item['shopify_product_id']]) ? 'data-sc' : ''?>
							 data-sc-type="<?= is_scent_club($products_by_id[$item['shopify_product_id']]) ? 'default' : ''?><?= is_scent_club_swap($products_by_id[$item['shopify_product_id']]) ? 'swap' : ''?><?= is_scent_club_month($products_by_id[$item['shopify_product_id']]) ? 'monthly' : ''?><?= !is_scent_club_any($products_by_id[$item['shopify_product_id']]) ? 'none' : ''?>"
						>

							<?php if(!empty($item['skipped']) && !empty($item['charge'])){ ?>
								<a class="sc-unskip-link" href="#" onclick="$(this).addClass('disabled'); ScentClub.unskip_charge(<?=$item['subscription_id']?>, <?=$item['charge']['id']?>, '<?=$item['type']?>'); return false;"><span>Unskip Box</span></a>
							<?php } else if(!empty($item['skipped'])){ ?>
								<a class="sc-unskip-link" href="#" onclick="$(this).addClass('disabled'); ScentClub.unskip_charge(<?=$item['subscription_id']?>, <?=$item['charge']['id']?>, '<?=$item['type']?>'); return false;"><span>Unskip Box</span></a>
							<?php } else if(is_scent_club_month($products_by_id[$item['shopify_product_id']])){ ?>
								<a class="sc-skip-link-club" href="#"><span>Skip Box</span></a>
							<?php } else if(is_scent_club_swap($products_by_id[$item['shopify_product_id']])){ ?>
								<a class="sc-skip-link-club" href="#"><span>Skip Box</span></a>
							<?php } else if($item['type'] == 'onetime'){ ?>
								<a class="sc-remove-link" href="#"><span>Remove Item</span></a>
							<?php } else if(!empty($item['charge'])){ ?>
								<a class="sc-skip-link<?=is_scent_club_any($products_by_id[$item['shopify_product_id']]) ? '-club' : '' ?>" href="#"><span>Skip Box</span></a>
							<?php } ?>
							<div class="sc-item-summary">
								<div class="sc-item-image">
									<?php if(is_scent_club($products_by_id[$item['shopify_product_id']])){ ?>
										<img class="lazyload" data-src="{{ 'sc-logo.svg' | file_url }}" height="100" width="100" />
									<?php } else { ?>
										{% if box_variant.image %}
										<img class="lazyload" data-srcset="{{ box_variant | img_url: '100x100' }} 1x, {{ box_variant | img_url: '200x200' }} 2x" />
										{% else %}
										<img class="lazyload" data-srcset="{{ box_product | img_url: '100x100' }} 1x, {{ box_product | img_url: '200x200' }} 2x" />
										{% endif %}
									<?php } ?>
								</div>
								<div>
									<?php if(is_scent_club_month($products_by_id[$item['shopify_product_id']])){ ?>
										<div class="sc-item-title">Skylar Scent Club</div>
										<div class="sc-item-subtitle">{{ box_product.variants.first.title }}</div>
										<div><a class="sc-swap-link" href="#"><img src="{{ 'icon-swap.svg' | file_url }}" /> <span>Swap Scent</span></a></div>
									<?php } else if(is_scent_club($products_by_id[$item['shopify_product_id']])){ ?>
										<div class="sc-item-title">Skylar Scent Club</div>
										<div class="sc-item-subtitle"></div>
									<?php } else if(is_scent_club_swap($products_by_id[$item['shopify_product_id']])){ ?>
										<div class="sc-item-title"><?=$item['product_title']?></div>
										<div class="sc-item-subtitle"><?=$item['variant_title']?></div>
										<div><a class="sc-swap-link" href="#"><img src="{{ 'icon-swap.svg' | file_url }}" /> <span>Swap Scent</span></a></div>
									<?php } else { ?>
										<div class="sc-item-title"><?= empty($item['product_title']) ? $item['title'] : $item['product_title']?></div>
										{% if box_variant.title != 'Default Title' %}<div class="sc-item-subtitle">{{ box_variant.title }}</div>{% endif %}
										<?php if($item['type'] != 'onetime'){ ?>
											<a class="sc-unsub-link" href="#"><span>Remove</span></a>
										<?php } ?>
									<?php } ?>
								</div>
							</div>
							<div class="sc-item-details">
								<div>
									<div class="sc-item-detail-label">Total</div>
									<div class="sc-item-detail-value">$<?=price_without_trailing_zeroes($item['price']) ?> </div>
								</div>
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
								<?php /* if(!empty($item['next_charge_scheduled_at'])){ ?>
										<div>
											<div class="sc-item-detail-label">Next Charge</div>
											<div class="sc-item-detail-value"><?=date('F j, Y', strtotime($item['next_charge_scheduled_at']))?></div>
										</div>
									<?php } else if(!empty($item['skipped'])){ ?>
										<div>
											<div class="sc-item-detail-label">Next Charge</div>
											<div class="sc-item-detail-value">Skipped</div>
										</div>
									<?php } else { ?>

									<?php } */?>
							</div>
						</div>
					<?php } ?>
					<div class="sc-box-discounts">
						<?php foreach($upcoming_box['discounts'] as $discount){ ?>
							<div class="sc-box-discount">
								<div class="sc-discount-title"><?=$discount['code']?> <a href="#" class="remove-discount-link">(remove)</a>:</div>
								<?php if($discount['type'] == 'percentage'){ ?>
									<div class="sc-discount-value"><?=$discount['amount']?>% ($<?=price_without_trailing_zeroes($discount['amount']*array_sum(array_column($upcoming_box['items'], 'price'))/100)?>)</div>
								<?php } else { ?>
									<div class="sc-discount-value">$<?=price_without_trailing_zeroes($discount['amount']) ?></div>
								<?php } ?>
							</div>
						<?php } ?>
						<div class="sc-discount-link" onclick="$('.sc-add-discount').show();$(this).hide();">Got a promo code?</div>
						<form class="sc-add-discount" style="display: none;">
							<div><input type="text" name="discount_code" /></div>
							<div><input type="submit" value="Apply" class="action_button inverted" /></div>
							<?php if(!empty($upcoming_box['charge'])){ ?>
								<input type="hidden" name="address_id" value="<?=$upcoming_box['charge']['address_id']?>" />
								<input type="hidden" name="charge_id" value="<?=$upcoming_box['charge']['id']?>" />
							<?php } ?>
						</form>
					</div>
					<?php if(!empty($upcoming_box['charge'])){ ?>
						<?php if(!empty($upcoming_box['charge']['shipping_lines'])){
							foreach($upcoming_box['charge']['shipping_lines'] as $shipping_line){
								if(empty($shipping_line['price']) || $shipping_line['price'] == 0){
									continue;
								}
								?>
								<div class="sc-box-shipping">
									<div class="sc-shipping-title"><?=$shipping_line['title']?></div>
									<div class="sc-shipping-value">$<?=price_without_trailing_zeroes($shipping_line['price'])?></div>
								</div>
							<?php }
						} ?>
						<div class="sc-box-total">
							Grand Total: $<?= price_without_trailing_zeroes($upcoming_box['charge']['total_price']) ?>
						</div>
					<?php } else { ?>
						<div class="sc-box-total">
							Grand Total: $<?= price_without_trailing_zeroes(array_sum(array_column($upcoming_box['items'], 'price'))) ?>
						</div>
					<?php } ?>
				</div>
			</div>
			<div class="sc-spacer"></div>
			<div class="sc-section-title">Add items to your Next Skylar box</div>
			<div class="sc-product-sections-container">
				<div class="sc-section-menu">
					<a href="#recommendations" class="active">Profile</a>
					<a href="#layering">Layering</a>
					<a href="#best-sellers">Best Sellers</a>
					<a href="#essentials">The Essentials</a>
				</div>
				<div class="sc-product-section" id="recommendations">
					<div class="sc-section-title">Recommendations based on <strong>Your Profile</strong></div>
					<div class="sc-product-carousel">
						<?php foreach($recommended_products as $product){ ?>
							{% assign recommended_handles = '<?=$product?>' | split: '|' %}
							{% include 'sc-product-tile' %}
						<?php } ?>
					</div>
				</div>
				<div class="sc-product-section hidden" id="layering">
					<div class="sc-section-title">Recommendations based on <strong>Layering</strong></div>
					<div class="sc-product-carousel">
						<?php foreach([
										  'isle::Full Size|rollie:12235492327511:Rollie',
										  'meadow::Full Size|rollie:12235492393047:Rollie',
										  'capri::Full Size|rollie:12235492425815:Rollie',
									  ] as $product){ ?>
							{% assign recommended_handles = '<?=$product?>' | split: '|' %}
							{% include 'sc-product-tile' %}
						<?php } ?>
					</div>
				</div>
				<div class="sc-product-section hidden" id="best-sellers">
					<div class="sc-section-title">Recommendations based on <strong>Best Sellers</strong></div>
					<div class="sc-product-carousel">
						<?php foreach([
										  'isle::Full Size|rollie:12235492327511:Rollie',
										  'rollie:12235409129559:Arrow|rollie:12235492425815:Capri|rollie:12235492360279:Coral|rollie:12235492327511:Isle|rollie:12235492393047:Meadow|rollie:12588614484055:Willow',
										  'scent-experience',
									  ] as $product){ ?>
							{% assign recommended_handles = '<?=$product?>' | split: '|' %}
							{% include 'sc-product-tile' %}
						<?php } ?>
					</div>
				</div>
				<div class="sc-product-section hidden" id="essentials">
					<div class="sc-section-title">Recommendations based on <strong>The Essentials</strong></div>
					<div class="sc-product-carousel">
						<?php foreach([
										  'sample-palette',
										  'scent-collection',
										  'rollie-collection',
									  ] as $product){ ?>
							{% assign recommended_handles = '<?=$product?>' | split: '|' %}
							{% include 'sc-product-tile' %}
						<?php } ?>
					</div>
				</div>
			</div>
			<div class="sc-hr"></div>
			<div class="sc-portal-innercontainer sc-schedule-conainer">
				<div class="sc-portal-title">Your Upcoming Skylar Box<?= count($upcoming_shipments) > 1 ? 'es' : '' ?></div>
				<div class="sc-portal-box-list">
					<?php $index = -1;
					foreach($upcoming_shipments as $upcoming_shipment){
						$index++;
						?>
						<div class="sc-upcoming-shipment">
							<div class="sc-box-info">
								<span class="sc-box-shiplabel">Shipping Date</span>
								<?php if($index == 0){ ?>
									<span class="sc-box-date sc-edit-date"><?=date('F j', $upcoming_shipment['ship_date_time']) ?> <img src="{{ 'icon-chevron-down.svg' | file_url }}" /></span>
								<?php } else { ?>
									<span class="sc-box-date"><?=date('F j', $upcoming_shipment['ship_date_time']) ?></span>
								<?php } ?>
							</div>
							<?php foreach($upcoming_shipment['items'] as $item){ ?>
								{% assign box_product = all_products['<?=$products_by_id[$item['shopify_product_id']]['handle']?>'] %}
								{% assign picked_variant_id = <?=$item['shopify_variant_id']?> | plus: 0 %}
								{% assign box_variant = box_product.variants.first %}
								{% for svariant in box_product.variants %}
								{% if svariant.id == picked_variant_id %}
								{% assign box_variant = svariant %}
								{% endif %}
								{% endfor %}
								<div class="sc-box-item<?= !empty($item['skipped']) ? ' sc-box-skipped' : '' ?>"
									 data-address-id="<?=$item['address_id']?>"
									 data-variant-id="<?=empty($item['shopify_variant_id']) ? '{{ box_product.variants.first.id }}' : $item['shopify_variant_id']?>"
									 data-date="<?= date('Y-m-d', $upcoming_shipment['ship_date_time'])?>"
									 data-master-image="{% if box_variant.image %}{{ box_variant | img_url: 'master' }}{% else %}{{ box_product | img_url: 'master' }}{% endif %}"
									 data-month-text="<?=date('F', $upcoming_shipment['ship_date_time'])?>"
									 data-subscription-id="<?=$item['subscription_id']?>"
									<?= !empty($item['charge']) ? 'data-charge-id="'.$item['charge']['id'].'"' : '' ?>
									 data-type="<?=$item['type']?>"
									<?= is_scent_club($products_by_id[$item['shopify_product_id']]) ? 'data-sc' : ''?>
									 data-sc-type="<?= is_scent_club($products_by_id[$item['shopify_product_id']]) ? 'default' : ''?><?= is_scent_club_swap($products_by_id[$item['shopify_product_id']]) ? 'swap' : ''?><?= is_scent_club_month($products_by_id[$item['shopify_product_id']]) ? 'monthly' : ''?><?= !is_scent_club_any($products_by_id[$item['shopify_product_id']]) ? 'none' : ''?>"
								>

									<?php if(!empty($item['skipped']) && !empty($item['charge'])){ ?>
										<a class="sc-unskip-link" href="#" onclick="$(this).addClass('disabled'); ScentClub.unskip_charge(<?=$item['subscription_id']?>, <?=$item['charge']['id']?>, '<?=$item['type']?>'); return false;"><span>Unskip Box</span></a>
									<?php } else if(!empty($item['skipped'])){ ?>
										<a class="sc-unskip-link" href="#" onclick="$(this).addClass('disabled'); ScentClub.unskip_charge(<?=$item['subscription_id']?>, <?=$item['charge']['id']?>, '<?=$item['type']?>'); return false;"><span>Unskip Box</span></a>
									<?php } else if(is_scent_club_month($products_by_id[$item['shopify_product_id']])){ ?>
										<a class="sc-skip-link-club" href="#"><span>Skip Box</span></a>
									<?php } else if(is_scent_club_swap($products_by_id[$item['shopify_product_id']])){ ?>
										<a class="sc-skip-link-club" href="#"><span>Skip Box</span></a>
									<?php } else if($item['type'] == 'onetime'){ ?>
										<a class="sc-remove-link" href="#"><span>Remove Item</span></a>
									<?php } else if(!empty($item['charge'])){ ?>
										<a class="sc-skip-link<?=is_scent_club_any($products_by_id[$item['shopify_product_id']]) ? '-club' : '' ?>" href="#"><span>Skip Box</span></a>
									<?php } ?>
									<div class="sc-item-summary">
										<div class="sc-item-image">
											<?php if(is_scent_club($products_by_id[$item['shopify_product_id']])){ ?>
												<img class="lazyload" data-src="{{ 'sc-logo.svg' | file_url }}" height="100" width="100" />
											<?php } else { ?>
												{% if box_variant.image %}
												<img class="lazyload" data-srcset="{{ box_variant | img_url: '100x100' }} 1x, {{ box_variant | img_url: '200x200' }} 2x" />
												{% else %}
												<img class="lazyload" data-srcset="{{ box_product | img_url: '100x100' }} 1x, {{ box_product | img_url: '200x200' }} 2x" />
												{% endif %}
											<?php } ?>
										</div>
										<div>
											<?php if(is_scent_club_month($products_by_id[$item['shopify_product_id']])){ ?>
												<div class="sc-item-title">Skylar Scent Club</div>
												<div class="sc-item-subtitle">{{ box_product.variants.first.title }}</div>
												<div><a class="sc-swap-link" href="#"><img src="{{ 'icon-swap.svg' | file_url }}" /> <span>Swap Scent</span></a></div>
											<?php } else if(is_scent_club($products_by_id[$item['shopify_product_id']])){ ?>
												<div class="sc-item-title">Skylar Scent Club</div>
												<div class="sc-item-subtitle"></div>
											<?php } else if(is_scent_club_swap($products_by_id[$item['shopify_product_id']])){ ?>
												<div class="sc-item-title"><?=$item['product_title']?></div>
												<div class="sc-item-subtitle"><?=$item['variant_title']?></div>
												<div><a class="sc-swap-link" href="#"><img src="{{ 'icon-swap.svg' | file_url }}" /> <span>Swap Scent</span></a></div>
											<?php } else { ?>
												<div class="sc-item-title"><?= empty($item['product_title']) ? $item['title'] : $item['product_title']?></div>
												{% if box_variant.title != 'Default Title' %}<div class="sc-item-subtitle">{{ box_variant.title }}</div>{% endif %}
												<?php if($item['type'] != 'onetime'){ ?>
													<a class="sc-unsub-link" href="#"><span>Remove</span></a>
												<?php } ?>
											<?php } ?>
										</div>
									</div>
									<div class="sc-item-details">
										<div>
											<div class="sc-item-detail-label">Total</div>
											<div class="sc-item-detail-value">$<?=price_without_trailing_zeroes($item['price']) ?> </div>
										</div>
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
										<?php /* if(!empty($item['next_charge_scheduled_at'])){ ?>
										<div>
											<div class="sc-item-detail-label">Next Charge</div>
											<div class="sc-item-detail-value"><?=date('F j, Y', strtotime($item['next_charge_scheduled_at']))?></div>
										</div>
									<?php } else if(!empty($item['skipped'])){ ?>
										<div>
											<div class="sc-item-detail-label">Next Charge</div>
											<div class="sc-item-detail-value">Skipped</div>
										</div>
									<?php } else { ?>

									<?php } */?>
									</div>
								</div>
							<?php } ?>
						</div>
					<?php } ?>
				</div>
				<div class="sc-load-more" data-months="<?=$months?>">
					<a href="#" class="action_button" onclick="ScentClub.load_schedule(<?=$months+3?>); return false;">View More</a>
				</div>
			</div>
		<?php } ?>
	</div>
</div>
<div class="hidden">
	<div id="sc-skip-modal">
		<div class="sc-modal-title">Did you know you can...</div>
		<div class="sc-modal-links">
			<div class="sc-modal-linkbox" onclick="$.featherlight.close();ScentClub.show_date_change();">
				<div><img src="{{ 'calendar.svg' | file_url }}" /></div>
				<div class="sc-linkbox-label">Change Shipping Date</div>
				<div><img src="{{ 'sc-link-arrow.svg' | file_url }}" /></div>
			</div>
			<div class="sc-modal-linkbox" onclick="$.featherlight.close();ScentClub.show_swap();">
				<div><img src="{{ 'swapscent-black.svg' | file_url }}" /></div>
				<div class="sc-linkbox-label">Swap Scents</div>
				<div><img src="{{ 'sc-link-arrow.svg' | file_url }}" /></div>
			</div>
		</div>
		<div class="sc-modal-continue">
			<a href="#" onclick="ScentClub.show_skip_final(); return false;">Continue To Skip</a>
		</div>
	</div>
	<div id="sc-skip-confirm-modal">
		<div class="sc-skip-image sc-desktop">
			<img src="" />
		</div>
		<div>
			<div class="sc-modal-title">Are you sure you want to skip:</div>
			<div class="sc-modal-subtitle"></div>
			<div class="sc-skip-image sc-mobile sc-tablet">
				<img src="" />
			</div>
			<div class="sc-skip-options">
				<a class="action_button" onclick="$(this).addClass('disabled'); $.featherlight.close(); ScentClub.skip_charge(ScentClub.selected_box_item.data('subscription-id'), ScentClub.selected_box_item.data('charge-id'), ScentClub.selected_box_item.data('type')); return false;">Yes, Skip Box</a>
				<a class="action_button inverted" onclick="$.featherlight.close(); return false;">Cancel</a>
			</div>
		</div>
	</div>
	<div id="sc-remove-confirm-modal">
		<div class="sc-skip-image sc-desktop">
			<img src="" />
		</div>
		<div>
			<div class="sc-modal-title">Are you sure you want to remove:</div>
			<div class="sc-modal-subtitle"></div>
			<div class="sc-skip-image sc-mobile sc-tablet">
				<img src="" />
			</div>
			<div class="sc-skip-options">
				<a class="action_button" onclick="$(this).addClass('disabled'); $.featherlight.close(); ScentClub.remove_sub(ScentClub.selected_box_item.data('subscription-id')); return false;">Yes, Remove</a>
				<a class="action_button inverted" onclick="$.featherlight.close(); return false;">Cancel</a>
			</div>
		</div>
	</div>
</div>
{{ 'featherlight.js' | asset_url | script_tag }}
{{ 'featherlight.css' | asset_url | stylesheet_tag }}
{{ 'sc-portal.js' | asset_url | script_tag }}
<script>
    $(document).ready(function(){
        $('.remove-discount-link').click(function(e){
            e.preventDefault();
            $('.loader').fadeIn();
            var data = $('.sc-add-discount').serializeJSON();
            data.c = Shopify.queryParams.c;
            $.ajax({
                url: '/tools/skylar/subscriptions/update-discount',
                data: data,
                success: function(data){
                    console.log(data);
                    if(!data.success){
                        $('.sc-box-discounts').append('<div class="sc-discount-error">'+data.error+'</div>');
                        $('.loader').fadeOut();
                    } else {
                        location.reload();
                    }
                }
            });
        });
        $('.sc-add-discount').submit(function(e){
            e.preventDefault();
            $('.sc-discount-error').remove();
            var btn = $(this).find('.action_button');
            btn.attr('disabled', 'disabled').addClass('disabled');
            btn.find('span').removeClass("zoomIn").addClass('animated zoomOut');
            var data = $(this).serializeJSON();
            data.c = Shopify.queryParams.c;
            $('.loader').fadeIn();
            $.ajax({
                url: '/tools/skylar/subscriptions/update-discount',
                data: data,
                success: function(data){
                    console.log(data);
                    if(!data.success){
                        $('.sc-box-discounts').append('<div class="sc-discount-error">'+data.error+'</div>');
                        btn.prop('disabled', false).removeClass('disabled');
                        $('.loader').fadeOut();
                    } else {
                        btn.find('span').text({{ 'products.product.add_to_cart_success' | t | json }}).removeClass('zoomOut').addClass('fadeIn');
                        location.reload();
                    }
                }
            });
        });
        $('.sc-addtobox-form').submit(function(e){
            e.preventDefault();
            if($(this).find('.sc-frequency-options').is(':animated')){
                return;
            }
            if(!$(this).find('.sc-frequency-options').is(':visible')){
                $(this).find('.sc-frequency-options').slideDown('fast');
                return;
            }
            var btn = $(this).find('.action_button');
            btn.attr('disabled', 'disabled').addClass('disabled');
            btn.find('span').removeClass("zoomIn").addClass('animated zoomOut');
            var data = $(this).serializeJSON();
            data.product_id = $(this).find('.sc-size-select option:selected').data('product-id');
            data.c = Shopify.queryParams.c;
            $('.loader').fadeIn();
            $.ajax({
                url: '/tools/skylar/subscriptions/add-to-box',
                data: data,
                success: function(data){
                    console.log(data);
                    if(data.error){
                        alert(data.error);
                        btn.prop('disabled', false).removeClass('disabled');
                    } else {
                        btn.find('span').text({{ 'products.product.add_to_cart_success' | t | json }}).removeClass('zoomOut').addClass('fadeIn');
                        location.reload();
                    }
                }
            });
        });
        $('.sc-addtobox-form').hover(function(e){
            if(!$(this).data('id')){
                $(this).find('.sc-frequency-options').stop(true).slideDown('fast');
            }
        }, function(e){
            $(this).find('.sc-frequency-options').stop(true).slideUp();
        });
        $('.sc-section-menu a').click(function(e){
            e.preventDefault();
            $('.sc-section-menu a').removeClass('active');
            $(this).addClass('active');
            $('.sc-product-section').hide();
            $($(this).attr('href')).show()
				.find('.sc-product-carousel').slick('setPosition');
        });
        optional_scripts.onload('slick', function(){
            $('.sc-product-carousel select, .sc-product-carousel button, .sc-product-carousel label').click(function(e){
                e.stopPropagation();
            });
            $('.sc-product-carousel').slick({
                slidesToShow: 3,
                centerPadding: '100px',
                focusOnSelect: true,
                infinite: false,
                arrows: false,
                dots: true,
                responsive: [
                    {
                        breakpoint: 1300,
                        settings: {
                            slidesToShow: 2.2,
                        }
                    },
                    {
                        breakpoint: 1100,
                        settings: {
                            slidesToShow: 1.8,
                        }
                    },
                    {
                        breakpoint: 1000,
                        settings: {
                            slidesToShow: 2.8,
                        }
                    },
                    {
                        breakpoint: 850,
                        settings: {
                            slidesToShow: 2.2,
                        }
                    },
                    {
                        breakpoint: 700,
                        settings: {
                            slidesToShow: 1.7,
                        }
                    },
                    {
                        breakpoint: 450,
                        settings: {
                            slidesToShow: 1.5,
                        }
                    },
                    {
                        breakpoint: 400,
                        settings: {
                            slidesToShow: 1.2,
                        }
                    },
                ],
            });
        });
    });
</script>
<script>
	$(document).ready(function(){
		ScentClub.selected_box_item = $('.sc-box-item[data-sc]').eq(0);
		if(ScentClub.selected_box_item.length < 1){
			return;
		}
		switch(Shopify.queryParams.intent){
			default:
				return;
			case 'swapscent':
				ScentClub.show_swap();
				break;
			case 'changedate':
				ScentClub.show_date_change();
				break;

		}
	});
	function bind_events(){
		$('.sc-edit-date').unbind().click(function(e){
			ScentClub.selected_box_item = $(this).closest('.sc-upcoming-shipment').find('.sc-box-item').eq(0);
			ScentClub.show_date_change();
		});
		$('.sc-swap-link').unbind().click(function(e){
			e.preventDefault();
			ScentClub.selected_box_item = $(this).closest('.sc-box-item');
			ScentClub.show_swap();
		});
		$('.sc-skip-link-club').unbind().click(function(e){
			e.preventDefault();
			ScentClub.selected_box_item = $(this).closest('.sc-box-item');
			$.featherlight($('#sc-skip-modal'), {
				variant: 'scent-club',
				afterOpen: $.noop, // Fix dumb app bug
			});
		});
		$('.sc-skip-link').unbind().click(function(e){
			e.preventDefault();
			ScentClub.selected_box_item = $(this).closest('.sc-box-item');
			ScentClub.show_skip_final();
		});
		$('.sc-unsub-link, .sc-remove-link').unbind().click(function(e){
			e.preventDefault();
			ScentClub.selected_box_item = $(this).closest('.sc-box-item');
			$('.sc-skip-image img').attr('src', ScentClub.selected_box_item.data('master-image'));
			var text = ScentClub.selected_box_item.data('month-text')+' '+ScentClub.selected_box_item.find('.sc-item-title').text().trim().replace('Monthly ', '');
			$('#sc-remove-confirm-modal .sc-modal-subtitle').html(text);
			$.featherlight.close();
			$.featherlight($('#sc-remove-confirm-modal'), {
				variant: 'scent-club',
				afterOpen: $.noop, // Fix dumb app bug
			});
		});
	}
</script>