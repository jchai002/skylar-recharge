<?php
if(!empty($_REQUEST['months'])){
	$more = intval($_REQUEST['months']);
}
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
	$customer = $res['customers'][0];
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
$upcoming_shipments = generate_subscription_schedule($db, $orders, $subscriptions, $onetimes, $charges, strtotime(date('Y-m-t',strtotime("+$months months"))));
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
{{ 'sc-portal.scss.css' | asset_url | stylesheet_tag }}
<div class="sc-portal-page sc-portal-{{ portal_page }} sc-portal-container">
	{% include 'sc-member-nav' %}
	<div class="sc-portal-content">
		<?php } ?>
		<div class="sc-portal-innercontainer">
			<?php if(empty($upcoming_shipments)){ ?>
				<div class="sc-portal-innercontainer">
					<div class="sc-portal-title">You Aren't A Member!</div>
					<div>
						<a href="/pages/scent-club">Click Here To Learn More</a>
					</div>
				</div>
			<?php } else { ?>
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
								 data-master-image="{{ box_product.images.first | img_url: 'master' }}"
								 data-month-text="<?=date('F', $upcoming_shipment['ship_date_time'])?>"
								 data-subscription-id="<?=$item['subscription_id']?>"
								<?= !empty($item['charge']) ? 'data-charge-id="'.$item['charge']['id'].'"' : '' ?>
								data-type="<?=$item['type']?>"
								 <?= is_scent_club($products_by_id[$item['shopify_product_id']]) ? 'data-sc' : ''?>
								 data-sc-type="<?= is_scent_club($products_by_id[$item['shopify_product_id']]) ? 'default' : ''?><?= is_scent_club_swap($products_by_id[$item['shopify_product_id']]) ? 'swap' : ''?><?= is_scent_club_month($products_by_id[$item['shopify_product_id']]) ? 'monthly' : ''?><?= !is_scent_club_any($products_by_id[$item['shopify_product_id']]) ? 'none' : ''?>"
							>

								<?php if(!empty($item['skipped'])){ ?>
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
										{% if box_variant.image %}
										<img class="lazyload" data-srcset="{{ box_variant.image | img_url: '100x100' }} 1x, {{ box_variant.image | img_url: '200x200' }} 2x" />
										{% else %}
										<img class="lazyload" data-srcset="{{ product.featured_image | img_url: '100x100' }} 1x, {{ product.featured_image | img_url: '200x200' }} 2x" />
										{% endif %}
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
											<?php if($item['type'] != 'onetime'){ ?>
												<a class="sc-unsub-link" href="#"><span>Remove</span></a>
											<?php } ?>
										<?php } ?>
									</div>
								</div>
								<div class="sc-item-details">
									<?php if($index == 0){ ?>
										<div>
											<div class="sc-item-detail-label">Total</div>
											<div class="sc-item-detail-value">$<?=price_without_trailing_zeroes($item['price']) ?> </div>
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
									<?php if(!empty($item['next_charge_scheduled_at'])){ ?>
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
										
									<?php } ?>
								</div>
							</div>
						<?php } ?>
					</div>
				<?php } ?>
			</div>
			<div class="sc-load-more" data-months="<?=$months?>">
				<a href="#" class="action_button" onclick="ScentClub.load_schedule(<?=$months+3?>); return false;">Load More</a>
			</div>
			<?php } ?>
		</div>
		<?php if(empty($more)){ ?>
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
				<a class="action_button" onclick="$(this).addClass('disabled'); ScentClub.skip_charge(ScentClub.selected_box_item.data('subscription-id'), ScentClub.selected_box_item.data('charge-id'), ScentClub.selected_box_item.data('type')); return false;">Yes, Skip Box</a>
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
        $('.sc-remove-link').unbind().click(function(e){
            e.preventDefault();
            $.ajax({
                url: '/tools/skylar/subscriptions/remove-item',
                data: {
                    id: $(this).closest('.sc-box-item').data('subscription-id'),
                    c: Shopify.queryParams.c,
                },
                success: function(data){
                    if(data.success){
                        ScentClub.load_schedule($('.sc-load-more').data('months'));
                    } else {
                        alert(data.error);
                    }
                }
            })
        });
        $('.sc-unsub-link').unbind().click(function(e){
            e.preventDefault();
            $.ajax({
                url: '/tools/skylar/subscriptions/remove-item',
                data: {
                    id: $(this).closest('.sc-box-item').data('subscription-id'),
                    c: Shopify.queryParams.c,
                },
                success: function(data){
                    if(data.success){
                        ScentClub.load_schedule($('.sc-load-more').data('months'));
                    } else {
                        alert(data.error);
                    }
                }
            })
        });
	}
</script>
<?php } ?>