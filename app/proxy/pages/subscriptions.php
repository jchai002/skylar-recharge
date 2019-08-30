<?php

global $db, $sc, $rc;

$customer = get_customer($db, $_REQUEST['c'], $sc);
$stmt = $db->prepare("SELECT recharge_id FROM rc_customers WHERE id=?");
$stmt->execute([$customer['id']]);
if($stmt->rowCount() > 1){
	$rc_customer_id = $stmt->fetchColumn();
} else {
	$res = $rc->get('/customers', [
		'shopify_customer_id' => intval($_REQUEST['c']),
	]);
	if(!empty($res['customers'])){
		$rc_customer_id = $res['customers'][0]['id'];
	}
}

if(!empty($rc_customer_id)){
	$months = empty($more) ? 4 : $more;
	$schedule = new SubscriptionSchedule($db, $rc, $rc_customer_id, strtotime(date('Y-m-t',strtotime("+$months months"))));
}

if(empty($rc_customer_id) || empty($schedule->get())){
	require_once(__DIR__.'/no-subscriptions.php');
	exit;
}

$sc_main_sub = sc_get_main_subscription($db, $rc, [
	'customer_id' => $rc_customer_id,
	'status' => 'ACTIVE',
]);

// figure out when to put the add to box section
$next_section_index = 0;
$i = -1;
foreach($schedule->get() as $shipment_list){
	$i++;
	if($shipment_list['has_ac_followup']){
		$next_section_index = $i;
	}
}
$next_section_shown = false;

$recommended_products = sc_get_profile_products(sc_get_profile_data($db, $rc, $_REQUEST['c']));
sc_conditional_billing($rc, $_REQUEST['c']);
?>
<!--
<?php print_r($schedule->subscriptions()); ?>
$schedule
<?php
echo count($schedule->get()).PHP_EOL;
print_r($schedule->get());
$shipment_list = $schedule->get()[0];
?>
-->
{% assign portal_page = 'schedule' %}
{{ 'sc-portal.scss.css' | asset_url | stylesheet_tag }}
<div class="sc-portal-page sc-portal-{{ portal_page }} sc-portal-container">
    {% include 'sc-member-nav' %}
    <div class="sc-portal-content">
        <div class="sc-portal-innercontainer">
			<div class="sc-portal-title">Your Next Skylar Box</div>
			<div class="sc-portal-subtitle">The next box that you'll be charged for</div>
			<?php
			foreach($shipment_list['addresses'] as $address_id => $upcoming_shipment){
				$has_ac_followup = false;
				$ac_delivered = false;
				$ac_allow_pushback = true;
				$ac_pushed_up = false;
				$has_sc = false;
				foreach($upcoming_shipment['items'] as $item){
					if(is_ac_followup_lineitem($item)){
						$has_ac_followup = true;
						if(is_ac_pushed_back($item)){
							$ac_allow_pushback = false;
						}
						if(is_ac_pushed_up($item)){
							$ac_allow_pushback = false;
							$ac_pushed_up = true;
						}
						if(is_ac_delivered($item)){
							$ac_delivered = true;
						}
					}
					if(is_scent_club_any(get_product($db, $item['shopify_product_id']))){
						$has_sc = true;
					}
					?>
				<div class="sc-upcoming-shipment">
					<div class="sc-box-info">
						<span class="sc-box-shiplabel">Shipping Date</span>
						<?php if($has_ac_followup && !$ac_delivered && !$ac_pushed_up){ ?>
							<span class="sc-box-date sc-box-date-pending">Pending Sample Delivery</span>
						<?php } else if($has_ac_followup && $ac_allow_pushback){ ?>
							<span class="sc-box-date ac-edit-date"><?=date('F j', $upcoming_shipment['ship_date_time']) ?> <img src="{{ 'icon-chevron-down.svg' | file_url }}" /></span>
						<?php } else if($has_ac_followup){ ?>
							<span class="sc-box-date"><?=date('F j', $upcoming_shipment['ship_date_time']) ?></span>
						<?php } else { ?>
							<span class="sc-box-date"><?=date('F j', $upcoming_shipment['ship_date_time']) ?></span>
						<?php } ?>
					</div>
					<?php foreach($upcoming_shipment['items'] as $item){
						if(is_scent_club_swap(get_product($db, $item['shopify_product_id']))){
							$monthly_scent = sc_get_monthly_scent($db, $shipment_list['ship_date_time'], is_admin_address($item['address_id']));
							$box_swap_image = 'data-swap-image="{{ all_products["'.$monthly_scent['handle'].'"].metafields.scent_club.swap_icon | file_img_url: \'30x30\' }}"';
						} else if(is_scent_club_month(get_product($db, $item['shopify_product_id']))) {
							$box_swap_image = 'data-swap-image="{{ box_product.metafields.scent_club.swap_icon | file_img_url: \'30x30\' }}"';
						} else {
							$box_swap_image = 'data-swap-image="{{ \'sc-logo.svg\' | file_url }}"';
						}
						?>
						{% assign box_product = all_products['<?=get_product($db, $item['shopify_product_id'])['handle']?>'] %}
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
							<?php if(is_scent_club(get_product($db, $item['shopify_product_id']))){ ?>
								data-master-image="{{ 'sc-logo.svg' | file_url }}"
							<?php } else { ?>
								data-master-image="{% if box_variant.image %}{{ box_variant | img_url: 'master' }}{% else %}{{ box_product | img_url: 'master' }}{% endif %}"
							<?php } ?>
							<?=$box_swap_image?>
							 data-month-text="<?=date('F', $upcoming_shipment['ship_date_time'])?>"
							 data-subscription-id="<?=$item['subscription_id']?>"
							<?= !empty($item['charge_id']) ? 'data-charge-id="'.$item['charge_id'].'"' : '' ?>
							 data-type="<?=$item['type']?>"
							<?= is_scent_club_any(get_product($db, $item['shopify_product_id'])) ? 'data-sc' : ''?>
							 data-sc-type="<?= is_scent_club(get_product($db, $item['shopify_product_id'])) ? 'default' : ''?><?= is_scent_club_swap(get_product($db, $item['shopify_product_id'])) ? 'swap' : ''?><?= is_scent_club_month(get_product($db, $item['shopify_product_id'])) ? 'monthly' : ''?><?= !is_scent_club_any(get_product($db, $item['shopify_product_id'])) ? 'none' : ''?>"
							<?= is_ac_followup_lineitem($item) ? 'data-ac' : '' ?>
							<?= is_ac_pushed_back($item) ? 'data-ac-pushed-back' : '' ?>
							<?= is_ac_delivered($item) ? 'data-ac-delivered' : '' ?>
						>
							<?php if(!empty($item['skipped']) && !empty($item['charge_id'])){ ?>
                                <a class="sc-unskip-link" href="#" onclick="$(this).addClass('disabled'); AccountController.unskip_charge(<?=$item['subscription_id']?>, <?=$item['charge_id']?>, '<?=$item['type']?>'); return false;"><span>Unskip Box</span></a>
							<?php } else if(!empty($item['skipped'])){ ?>
                                <a class="sc-unskip-link" href="#" onclick="$(this).addClass('disabled'); AccountController.unskip_charge(<?=$item['subscription_id']?>, 0, '<?=$item['type']?>'); return false;"><span>Unskip Box</span></a>
							<?php } else if(is_ac_followup_lineitem($item)){ ?>
                                <a class="ac-item-corner-link ac-cancel-link" href="#"><span>Cancel My Trial</span></a>
							<?php } else if(is_scent_club_month(get_product($db, $item['shopify_product_id']))){ ?>
                                <a class="sc-skip-link-club" href="#"><span>Skip Box</span></a>
							<?php } else if(is_scent_club_swap(get_product($db, $item['shopify_product_id']))){ ?>
                                <a class="sc-skip-link-club" href="#"><span>Skip Box</span></a>
							<?php } else if($item['type'] == 'onetime'){ ?>
                                <a class="sc-remove-link" href="#"><span>Remove Item</span></a>
							<?php } else if(!empty($item['charge_id'])){ ?>
                                <a class="sc-skip-link<?=is_scent_club_any(get_product($db, $item['shopify_product_id'])) ? '-club' : '' ?>" href="#"><span>Skip Box</span></a>
							<?php } ?>
							<div class="sc-item-info">
								<div class="sc-item-summary">
									<div class="sc-item-image">
										<?php if(is_scent_club(get_product($db, $item['shopify_product_id']))){ ?>
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
										<?php if(is_ac_followup_lineitem($item)){ ?>
											<div class="sc-item-title"><?= empty($item['product_title']) ? $item['title'] : $item['product_title']?></div>
											{% if box_variant.title != 'Default Title' %}<div class="sc-item-subtitle">{{ box_variant.title }}</div>{% endif %}
										<?php } else if(is_scent_club_month(get_product($db, $item['shopify_product_id']))){ ?>
											<div class="sc-item-title">Skylar Scent Club</div>
											<div class="sc-item-subtitle">{{ box_product.variants.first.title }}</div>
											<div><a class="sc-swap-link" href="#"><img src="{{ 'icon-swap.svg' | file_url }}" alt="Swap scent icon" /> <span>Swap Scent</span></a></div>
										<?php } else if(is_scent_club(get_product($db, $item['shopify_product_id']))){ ?>
											<div class="sc-item-title">Skylar Scent Club</div>
											<div class="sc-item-subtitle"></div>
										<?php } else if(is_scent_club_swap(get_product($db, $item['shopify_product_id']))){ ?>
											<div class="sc-item-title"><?=$item['product_title']?></div>
											<div class="sc-item-subtitle"><?=$item['variant_title']?></div>
											<div><a class="sc-swap-link" href="#"><img src="{{ 'icon-swap.svg' | file_url }}" alt="Swap scent icon" /> <span>Swap Scent</span></a></div>
										<?php } else { ?>
											<div class="sc-item-title"><?= empty($item['product_title']) ? $item['title'] : $item['product_title']?></div>
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
											<?php if(is_scent_club_any(get_product($db, $item['shopify_product_id']))){ ?>
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
								</div>
							</div>
						</div>
					<?php } ?>
					<?php if(!$has_ac_followup){ ?>
                        <div class="sc-box-discounts<?= !empty($all_skipped) ? ' sc-box-skipped' : '' ?>">
							<?php foreach($upcoming_shipment['discounts'] as $discount){ ?>
                                <div class="sc-box-discount">
                                    <div class="sc-discount-title"><?=$discount['code']?> <a href="#" class="remove-discount-link">(remove)</a>:</div>
									<?php if($discount['type'] == 'percentage'){ ?>
                                        <div class="sc-discount-value"><?=$discount['amount']?>% ($<?=price_without_trailing_zeroes($discount['amount']*array_sum(array_column($upcoming_shipment['items'], 'price'))/100)?>)</div>
									<?php } else { ?>
                                        <div class="sc-discount-value">$<?=price_without_trailing_zeroes($discount['amount']) ?></div>
									<?php } ?>
                                </div>
							<?php } ?>
    						<?php if(!empty($upcoming_shipment['charge_id'])){ ?>
                            <div class="sc-discount-link" onclick="$('.sc-add-discount').show();$(this).hide();">Got a promo code?</div>
                            <form class="sc-add-discount" style="display: none;">
                                <div><input type="text" name="discount_code" /></div>
                                <div><input type="submit" value="Apply" class="action_button inverted" /></div>
                                <input type="hidden" name="address_id" value="<?=$schedule->charges()[$upcoming_shipment['charge_id']]['address_id']?>" />
                                <input type="hidden" name="charge_id" value="<?=$upcoming_shipment['charge_id']?>" />
                            </form>
							<?php } ?>
                        </div>
						<?php if(!empty($upcoming_shipment['charge_id'])){ ?>
							<?php if(!empty($schedule->charges()[$upcoming_shipment['charge_id']]['shipping_lines'])){
								foreach($schedule->charges()[$upcoming_shipment['charge_id']]['shipping_lines'] as $shipping_line){
									if(empty($shipping_line['price']) || $shipping_line['price'] == 0){
										continue;
									}
									?>
                                    <div class="sc-box-shipping<?= !empty($all_skipped) ? ' sc-box-skipped' : '' ?>">
                                        <div class="sc-shipping-title"><?=$shipping_line['title']?></div>
                                        <div class="sc-shipping-value">$<?=price_without_trailing_zeroes($shipping_line['price'])?></div>
                                    </div>
								<?php }
							} ?>
							<?php if(!empty($schedule->charges()[$upcoming_shipment['charge_id']]['total_tax'])){ ?>
                                <div class="sc-box-shipping<?= !empty($all_skipped) ? ' sc-box-skipped' : '' ?>">
                                    <div class="sc-shipping-title">Tax</div>
                                    <div class="sc-shipping-value">$<?=price_without_trailing_zeroes($schedule->charges()[$upcoming_shipment['charge_id']]['total_tax'])?></div>
                                </div>
							<?php } ?>
                            <div class="sc-box-total<?= !empty($all_skipped) ? ' sc-box-skipped' : '' ?>">
                                Grand Total: $<?= price_without_trailing_zeroes($schedule->charges()[$upcoming_shipment['charge_id']]['total_price']) ?>
                            </div>
						<?php } else { ?>
                            <div class="sc-box-total<?= !empty($all_skipped) ? ' sc-box-skipped' : '' ?>">
                                Grand Total: $<?= price_without_trailing_zeroes(array_sum(array_column($upcoming_shipment['items'], 'price'))) ?>
                            </div>
						<?php } ?>
					<?php } ?>
				</div>
				<?php } ?>
			<?php } ?>
        </div>
        <div class="sc-portal-innercontainer">
            <div class="sc-portal-title">Your Subscriptions</div>
            <div class="sc-portal-subtitle">Manage your subscriptions here</div>
			<?php
			$stmt_scent_change_options = $db->prepare("SELECT s.code, s.title, v.shopify_id as shopify_variant_id FROM variant_attributes va
                LEFT JOIN scents s ON va.scent_id=s.id
                LEFT JOIN variants v ON va.variant_id=v.id
                WHERE va.format_id=:format_id AND va.product_type_id=:product_type_id;");
			/*foreach($schedule->subscriptions() as $item){
				echo "<!--";
				// TODO SC will sometimes be a onetime
				$variant = get_variant($db, $item['shopify_variant_id']);
				$scent_change_options = [];
				if(!empty($variant['attributes'])){
					$stmt_scent_change_options->execute([
						'format_id' => $variant['attributes']['format_id'],
						'product_type_id' => $variant['attributes']['product_type_id'],
					]);
					$scent_change_options = $stmt_scent_change_options->fetchAll();
				}
				print_r($item);
				?>
                -->
                {% assign box_product = all_products['<?=get_product($db, $item['shopify_product_id'])['handle']?>'] %}
                {% assign picked_variant_id = <?=$item['shopify_variant_id']?> | plus: 0 %}
                {% assign box_variant = box_product.variants.first %}
                {% for svariant in box_product.variants %}
                {% if svariant.id == picked_variant_id %}
                {% assign box_variant = svariant %}
                {% endif %}
                {% endfor %}
                <div class="portal-item"
                     data-subscription-id="<?=$item['subscription_id']?>"
                     data-ship-time="<?=$item['scheduled_at_time']?>"
					<?php if(is_scent_club(get_product($db, $item['shopify_product_id']))){ ?>
                        data-master-image="{{ 'sc-logo.svg' | file_url }}"
					<?php } else { ?>
                        data-master-image="{% if box_variant.image %}{{ box_variant | img_url: 'master' }}{% else %}{{ box_product | img_url: 'master' }}{% endif %}"
					<?php } ?>
                     data-product-name="<?=$item['product_title']?>"
                >
                    <div class="portal-item-edit">Edit</div>
                    <div class="portal-item-subscribed">Subscribed</div>
                    <div class="portal-item-details">
                        <div class="portal-item-img">
							<?php if(is_scent_club(get_product($db, $item['shopify_product_id']))){ ?>
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
							<?php if(is_ac_followup_lineitem($item)){ ?>
                                <div class="portal-item-detail-label"><?= empty($item['product_title']) ? $item['title'] : $item['product_title']?></div>
                                {% if box_variant.title != 'Default Title' %}<div class="portal-item-detail-value">{{ box_variant.title }}</div>{% endif %}
							<?php } else if(is_scent_club_month(get_product($db, $item['shopify_product_id']))){ ?>
                                <div class="portal-item-detail-label">Skylar Scent Club</div>
                                <div class="portal-item-detail-value">{{ box_product.variants.first.title }}</div>
							<?php } else if(is_scent_club(get_product($db, $item['shopify_product_id']))){ ?>
                                <div class="portal-item-detail-label">Skylar Scent Club</div>
                                <div class="portal-item-detail-value"></div>
							<?php } else if(is_scent_club_swap(get_product($db, $item['shopify_product_id']))){ ?>
                                <div class="portal-item-detail-label"><?=$item['product_title']?></div>
                                <div class="portal-item-detail-value"><?=$item['variant_title']?></div>
							<?php } else { ?>
                                <div class="portal-item-detail-label"><?= empty($item['product_title']) ? $item['title'] : $item['product_title']?></div>
							<?php } ?>
                        </div>
                        <div>
                            <div class="portal-item-detail-label">Next Ship Date</div>
                            <div class="portal-item-detail-value"><?=date('M j', $item['scheduled_at_time'])?></div>
                        </div>
                        <div>
                            <div class="portal-item-detail-label">Frequency</div>
                            <div class="portal-item-detail-value">
								<?php if(is_scent_club_any(get_product($db, $item['shopify_product_id']))){ ?>
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
                            <div class="portal-item-detail-label">Price</div>
                            <div class="portal-item-detail-value">$<?=price_without_trailing_zeroes($item['price']) ?> </div>
                        </div>
                    </div>
					<?php
					$this_sub_onetimes = [];
					foreach($schedule->onetimes() as $onetime){
						if(get_oli_attribute($onetime, '_parent_id')){
							$this_sub_onetimes[] = $onetime;
						}
					}
					if(!empty($this_sub_onetimes)){
						?>
                        <div class="portal-item-onetimes">
                            <div class="sc-portal-title">One-time only</div>
                            <div class="sc-portal-subtitle">These products ship in your next shipment only.</div>
							<?php foreach($this_sub_onetimes as $onetime){ ?>
                                <div class="portal-item-details">
                                    <div class="portal-item-img">
										<?php if(is_scent_club(get_product($db, $item['shopify_product_id']))){ ?>
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
                                        <div class="portal-item-detail-label"><?= empty($item['product_title']) ? $item['title'] : $item['product_title']?></div>
                                        <div class="portal-item-quantity">
                                            <span class="portal-quantity-miuns">-</span>
                                            <span class="portal-quantity-amount"><?=$item['quantity']?></span>
                                            <span class="portal-quantity-plus">+</span>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="portal-item-detail-label">Price</div>
                                        <div class="portal-item-detail-value">$<?=price_without_trailing_zeroes($item['price']) ?> </div>
                                    </div>
                                </div>
							<?php } ?>
                        </div>
					<? } ?>
                    <div class="portal-item-actions">
                        <div class="action_button add-and-save">Add to this box and save!</div>
                    </div>
                    <form class="portal-item-edit-container">
                        <div class="portal-edit-row">
                            <div class="portal-edit-select portal-edit-date">
                                <label class="portal-edit-label" for="edit-date-<?=$item['subscription_id']?>">Shipping Date</label>
                                <div class="portal-edit-control">
                                    <div id="edit-date-<?=$item['subscription_id']?>" class="fake-select show-calendar">
										<?=date('M j', $item['scheduled_at_time'])?>
                                    </div>
                                </div>
                                <div class="calendar<?=is_scent_club_any(get_product($db, $item['shopify_product_id'])) ? ' one-month' : '' ?> floating-calendar hidden"></div>
                            </div>
							<?php if(!is_scent_club_any(get_product($db, $item['shopify_product_id']))){ ?>
                                <div class="portal-edit-select portal-edit-frequency">
                                    <label class="portal-edit-label" for="edit-frequency-<?=$item['subscription_id']?>">Frequency</label>
                                    <div class="portal-edit-control">
                                        <select class="edit-frequency" id="edit-frequency-<?=$item['subscription_id']?>" name="frequency">
											<?php
											$frequencies = [];
											$product = get_product($db, $item['shopify_product_id']);
											if($product['type'] == 'Body Bundle'){
												$frequencies = [
													'onetime' => 'Once',
													'1' => 'Monthly',
													'2' => 'Every other month',
												];
											} else if(strpos($product['type'], 'Body') !== false){
												$frequencies = [
													'1' => 'Monthly',
													'2' => 'Every other month',
												];
											} else {
												$frequencies = [
													'onetime' => 'Once',
													'6' => 'Every 6 months',
													'9' => 'Every 9 months',
												];
											}
											foreach($frequencies as $value => $label){
												?>
                                                <option value="<?=$value?>"<?=$value == ($item['order_interval_frequency'] ?? 'onetime') ? ' selected' : '' ?>><?=$label?></option>
											<?php } ?>
                                        </select>
                                    </div>
                                </div>
							<?php } ?>
                            <div class="portal-edit-links">
                                <a class="portal-edit-cancel" href="#">Cancel</a>
                            </div>
                        </div>
						<?php if(!empty($scent_change_options)){ ?>
                            <div class="portal-edit-divider"></div>
                            <div class="portal-edit-row">
                                <div class="portal-edit-select portal-edit-date">
                                    <div class="portal-edit-label">Change Your Scent</div>
                                    <div class="portal-edit-control">
										<?php foreach($scent_change_options as $scent_change_option){ ?>
                                            <div class="portal-swap-option">
                                                <input type="radio" id="edit-scent-<?=$scent_change_option['shopify_variant_id']?>" class="swap-variant" name="variant" value="<?=$scent_change_option['shopify_variant_id']?>"<?= $scent_change_option['shopify_variant_id'] == $item['shopify_variant_id'] ? ' checked' : '' ?>>
                                                <label for="edit-scent-<?=$scent_change_option['shopify_variant_id']?>">
                                                    <img class="lazyload lazypreload" data-src="{{ 'scent-icon_<?=$scent_change_option['code']?>.png' | file_img_url }}" />
                                                    <div><?=$scent_change_option['title']?></div>
                                                </label>
                                            </div>
										<?php } ?>
                                    </div>
                                </div>
                            </div>
						<?php } ?>
                    </form>
                </div>
			<?php } */?>
        </div>
        <div class="sc-portal-innercontainer">
            <div class="sc-portal-title">Your One-times</div>
            <div class="sc-portal-subtitle">Manage your onetimes here</div>
			<?php
			$stmt_scent_change_options = $db->prepare("SELECT s.code, s.title, v.shopify_id as shopify_variant_id FROM variant_attributes va
                LEFT JOIN scents s ON va.scent_id=s.id
                LEFT JOIN variants v ON va.variant_id=v.id
                WHERE va.format_id=:format_id AND va.product_type_id=:product_type_id;");
			foreach($schedule->onetimes() as $item){
				echo "<!--";
				// TODO SC will sometimes be a onetime
				$variant = get_variant($db, $item['shopify_variant_id']);
				$scent_change_options = [];
				if(!empty($variant['attributes'])){
					$stmt_scent_change_options->execute([
						'format_id' => $variant['attributes']['format_id'],
						'product_type_id' => $variant['attributes']['product_type_id'],
					]);
					$scent_change_options = $stmt_scent_change_options->fetchAll();
				}
				print_r($item);
				?>
                -->
                {% assign box_product = all_products['<?=get_product($db, $item['shopify_product_id'])['handle']?>'] %}
                {% assign picked_variant_id = <?=$item['shopify_variant_id']?> | plus: 0 %}
                {% assign box_variant = box_product.variants.first %}
                {% for svariant in box_product.variants %}
                {% if svariant.id == picked_variant_id %}
                {% assign box_variant = svariant %}
                {% endif %}
                {% endfor %}
                <div class="portal-item"
                     data-subscription-id="<?=$item['subscription_id']?>"
                     data-ship-time="<?=$item['scheduled_at_time']?>"
					<?php if(is_scent_club(get_product($db, $item['shopify_product_id']))){ ?>
                        data-master-image="{{ 'sc-logo.svg' | file_url }}"
					<?php } else { ?>
                        data-master-image="{% if box_variant.image %}{{ box_variant | img_url: 'master' }}{% else %}{{ box_product | img_url: 'master' }}{% endif %}"
					<?php } ?>
                     data-product-name="<?=$item['product_title']?>"
                >
                    <div class="portal-item-edit">Edit</div>
                    <div class="portal-item-details">
                        <div class="portal-item-img">
							<?php if(is_scent_club(get_product($db, $item['shopify_product_id']))){ ?>
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
							<?php if(is_ac_followup_lineitem($item)){ ?>
                                <div class="portal-item-detail-label"><?= empty($item['product_title']) ? $item['title'] : $item['product_title']?></div>
                                {% if box_variant.title != 'Default Title' %}<div class="portal-item-detail-value">{{ box_variant.title }}</div>{% endif %}
							<?php } else if(is_scent_club_month(get_product($db, $item['shopify_product_id']))){ ?>
                                <div class="portal-item-detail-label">Skylar Scent Club</div>
                                <div class="portal-item-detail-value">{{ box_product.variants.first.title }}</div>
							<?php } else if(is_scent_club(get_product($db, $item['shopify_product_id']))){ ?>
                                <div class="portal-item-detail-label">Skylar Scent Club</div>
                                <div class="portal-item-detail-value"></div>
							<?php } else if(is_scent_club_swap(get_product($db, $item['shopify_product_id']))){ ?>
                                <div class="portal-item-detail-label"><?=$item['product_title']?></div>
                                <div class="portal-item-detail-value"><?=$item['variant_title']?></div>
							<?php } else { ?>
                                <div class="portal-item-detail-label"><?= empty($item['product_title']) ? $item['title'] : $item['product_title']?></div>
							<?php } ?>
                        </div>
                        <div>
                            <div class="portal-item-detail-label">Ship Date</div>
                            <div class="portal-item-detail-value"><?=date('M j', $item['scheduled_at_time'])?></div>
                        </div>
                        <div>
                            <div class="portal-item-detail-label">Frequency</div>
                            <div class="portal-item-detail-value">Once</div>
                        </div>
                        <div>
                            <div class="portal-item-detail-label">Price</div>
                            <div class="portal-item-detail-value">$<?=price_without_trailing_zeroes($item['price']) ?> </div>
                        </div>
                    </div>
                    <form class="portal-item-edit-container">
                        <div class="portal-edit-row">
                            <div class="portal-edit-select portal-edit-date">
                                <label class="portal-edit-label" for="edit-date-<?=$item['subscription_id']?>">Shipping Date</label>
                                <div class="portal-edit-control">
                                    <div id="edit-date-<?=$item['subscription_id']?>" class="fake-select show-calendar">
										<?=date('M j', $item['scheduled_at_time'])?>
                                    </div>
                                </div>
                                <div class="calendar<?=is_scent_club_any(get_product($db, $item['shopify_product_id'])) ? ' one-month' : '' ?> floating-calendar hidden"></div>
                            </div>
							<?php if(!is_scent_club_any(get_product($db, $item['shopify_product_id']))){ ?>
                                <div class="portal-edit-select portal-edit-frequency">
                                    <label class="portal-edit-label" for="edit-frequency-<?=$item['subscription_id']?>">Frequency</label>
                                    <div class="portal-edit-control">
                                        <select class="edit-frequency" id="edit-frequency-<?=$item['subscription_id']?>" name="frequency">
											<?php
											$frequencies = [];
											$product = get_product($db, $item['shopify_product_id']);
											if($product['type'] == 'Body Bundle'){
												$frequencies = [
													'onetime' => 'Once',
													'1' => 'Monthly',
													'2' => 'Every other month',
												];
											} else if(strpos($product['type'], 'Body') !== false){
												$frequencies = [
													'1' => 'Monthly',
													'2' => 'Every other month',
												];
											} else {
												$frequencies = [
													'onetime' => 'Once',
													'6' => 'Every 6 months',
													'9' => 'Every 9 months',
												];
											}
											foreach($frequencies as $value => $label){
												?>
                                                <option value="<?=$value?>"<?=$value == ($item['order_interval_frequency'] ?? 'onetime') ? ' selected' : '' ?>><?=$label?></option>
											<?php } ?>
                                        </select>
                                    </div>
                                </div>
							<?php } ?>
                            <div class="portal-edit-links">
                                <a class="portal-edit-cancel" href="#">Cancel</a>
                            </div>
                        </div>
						<?php if(!empty($scent_change_options)){ ?>
                            <div class="portal-edit-divider"></div>
                            <div class="portal-edit-row">
                                <div class="portal-edit-select portal-edit-date">
                                    <div class="portal-edit-label">Change Your Scent</div>
                                    <div class="portal-edit-control">
                                        <?php foreach($scent_change_options as $scent_change_option){ ?>
                                            <div class="portal-swap-option">
                                                <input type="radio" id="edit-scent-<?=$scent_change_option['shopify_variant_id']?>" class="swap-variant" name="variant" value="<?=$scent_change_option['shopify_variant_id']?>"<?= $scent_change_option['shopify_variant_id'] == $item['shopify_variant_id'] ? ' checked' : '' ?>>
                                                <label for="edit-scent-<?=$scent_change_option['shopify_variant_id']?>">
                                                    <img class="lazyload lazypreload" data-src="{{ 'scent-icon_<?=$scent_change_option['code']?>.png' | file_img_url }}" />
                                                    <div><?=$scent_change_option['title']?></div>
                                                </label>
                                            </div>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
						<?php } ?>
                    </form>
                </div>
			<?php } ?>
        </div>
    </div>
</div>
<div class="hidden">
    <div id="ac-move-save-modal" class="sc-save-modal">
        <div class="sc-modal-title">Did you know you can...</div>
        <div class="sc-modal-links">
            <div class="sc-modal-linkbox ac-linkbox-ordernow" onclick="AccountController.ac_move_to_today(AccountController.selected_box_item.data('subscription-id')); $.featherlight.close();">
                <div><img src="{{ 'cart-icon.svg' | file_url }}" class="sc-linkbox-icon" /></div>
                <div class="sc-linkbox-label">Order today and your $20 credit will be applied to your full-size purchase.</div>
                <div><img src="{{ 'sc-link-arrow.svg' | file_url }}" /></div>
            </div>
        </div>
        <div class="sc-modal-continue">
            <a href="#" onclick="AccountController.ac_push_back(AccountController.selected_box_item.data('subscription-id')); $.featherlight.close(); return false;">No thanks, delay my order by a week</a>
        </div>
    </div>
    <div id="ac-cancel-save-delay-modal" class="sc-save-modal">
        <div class="sc-modal-title">Are you sure you want to cancel?</div>
        <div class="sc-modal-links">
            <div class="sc-modal-linkbox ac-linkbox-pushback" onclick="AccountController.ac_push_back(AccountController.selected_box_item.data('subscription-id')); $.featherlight.close();">
                <div><img src="{{ 'delay-icon.svg' | file_url }}" class="sc-linkbox-icon" /></div>
                <div class="sc-linkbox-label">No, I'd Just Like To Delay One Week</div>
                <div><img src="{{ 'sc-link-arrow.svg' | file_url }}" /></div>
            </div>
        </div>
        <div class="sc-modal-continue">
            <a href="#" onclick="AccountController.show_ac_cancel_final_save(); return false;">Yes, Cancel My Trial</a>
        </div>
    </div>
    <div id="ac-cancel-save-final-modal" class="sc-save-modal">
        <div class="sc-modal-title">Are you sure?</div>
        <div class="sc-modal-subtitle">If you cancel, you'll lose your $20 credit.</div>
        <div class="sc-modal-save">
            <a href="#" class="action_button" onclick="$.featherlight.close(); return false;">No, Keep My Credit</a>
        </div>
        <div class="sc-modal-continue">
            <a href="#" onclick="AccountController.show_ac_cancel_reason(); return false;">Yes, Cancel My Trial</a>
        </div>
    </div>
    <div id="ac-cancel-reason-modal" class="sc-confirm-modal">
        <div>
            <div class="sc-modal-title">Why would you like to cancel your trial?</div>
            <form id="ac-cancel-reason-form" class="skip-reason-form">
                <div class="skip-reason-list">
                    <label>
                        <input type="radio" name="skip_reason" value="It's too expensive">
                        <span class="radio-visual"></span>
                        <span>It's too expensive</span>
                    </label>
                    <label>
                        <input type="radio" name="skip_reason" value="I don't like any of the scents">
                        <span class="radio-visual"></span>
                        <span>I don't like any of the scents</span>
                    </label>
                    <label>
                        <input type="radio" name="skip_reason" value="I want to use what I already have">
                        <span class="radio-visual"></span>
                        <span>I want to use what I already have</span>
                    </label>
                    <label>
                        <input type="radio" name="skip_reason" value="I need more time sampling">
                        <span class="radio-visual"></span>
                        <span>I need more time sampling</span>
                    </label>
                    <label>
                        <input type="radio" name="skip_reason" value="I have a sensitivity to the product">
                        <span class="radio-visual"></span>
                        <span>I have a sensitivity to the product</span>
                    </label>
                    <label>
                        <input type="radio" name="skip_reason" value="The scents don't last long">
                        <span class="radio-visual"></span>
                        <span>The scents don't last long</span>
                    </label>
                    <label>
                        <input type="radio" name="skip_reason" value="other">
                        <span class="radio-visual"></span>
                        <span>Other Reason</span>
                    </label>
                    <textarea name="other_reason" title="Other Reason"></textarea>
                </div>
                <div class="sc-skip-options">
                    <a class="action_button skip-confirm-button disabled" onclick="if($(this).hasClass('disabled')){return false;} $(this).addClass('disabled'); AccountController.ac_cancel_followup(AccountController.selected_box_item.data('subscription-id'), AccountController.get_skip_reason($('#ac-cancel-reason-form'))); return false;">Cancel Subscription</a>
                    <a class="action_button inverted" onclick="$.featherlight.close(); return false;">Go Back</a>
                </div>
            </form>
        </div>
    </div>
    <div id="sc-skip-modal">
        <div class="sc-modal-title">Did you know you can...</div>
        <div class="sc-modal-links">
            <div class="sc-modal-linkbox" onclick="$.featherlight.close();AccountController.show_date_change();">
                <div><img src="{{ 'calendar.svg' | file_url }}" /></div>
                <div class="sc-linkbox-label">Change Shipping Date</div>
                <div><img src="{{ 'sc-link-arrow.svg' | file_url }}" /></div>
            </div>
            <div class="sc-modal-linkbox" onclick="$.featherlight.close();AccountController.show_swap();">
                <div><img src="{{ 'swapscent-black.svg' | file_url }}" /></div>
                <div class="sc-linkbox-label">Swap Scents</div>
                <div><img src="{{ 'sc-link-arrow.svg' | file_url }}" /></div>
            </div>
        </div>
        <div class="sc-modal-continue">
            <a href="#" onclick="AccountController.show_skip_reasons(); return false;">Continue To Skip</a>
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
                <a class="action_button" onclick="$(this).addClass('disabled'); $.featherlight.close(); AccountController.skip_charge(AccountController.selected_box_item.data('subscription-id'), AccountController.selected_box_item.data('charge-id'), AccountController.selected_box_item.data('type')); return false;">Yes, Skip Box</a>
                <a class="action_button inverted" onclick="$.featherlight.close(); return false;">Cancel</a>
            </div>
        </div>
    </div>
    <div id="sc-skip-reasons-modal">
        <div>
            <div class="sc-modal-title">Why would you like to skip this scent?</div>
            <form class="skip-reason-form">
                <div class="skip-reason-list">
                    <label>
                        <input type="radio" name="skip_reason" value="I have a sensitivity to an ingredient in the scent.">
                        <span class="radio-visual"></span>
                        <span>I have a sensitivity to an ingredient in the scent.</span>
                    </label>
                    <label>
                        <input type="radio" name="skip_reason" value="I have too many now and would like to use what I currently have.">
                        <span class="radio-visual"></span>
                        <span>I have too many now and would like to use what I currently have.</span>
                    </label>
                    <label>
                        <input type="radio" name="skip_reason" value="I'm not excited about the next scent.">
                        <span class="radio-visual"></span>
                        <span>I'm not excited about the next scent.</span>
                    </label>
                    <label>
                        <input type="radio" name="skip_reason" value="I can't afford to do it every month.">
                        <span class="radio-visual"></span>
                        <span>I can't afford to do it every month.</span>
                    </label>
                    <label>
                        <input type="radio" name="skip_reason" value="other">
                        <span class="radio-visual"></span>
                        <span>Other Reason</span>
                    </label>
                    <textarea name="other_reason" title="Other Reason"></textarea>
                </div>
                <div class="sc-skip-options">
                    <a class="action_button skip-confirm-button disabled" onclick="if($(this).hasClass('disabled')){return false;} $(this).addClass('disabled'); $.featherlight.close(); AccountController.skip_charge(AccountController.selected_box_item.data('subscription-id'), AccountController.selected_box_item.data('charge-id'), AccountController.selected_box_item.data('type'), AccountController.get_skip_reason()); return false;">Skip Box</a>
                    <a class="action_button inverted" onclick="$.featherlight.close(); return false;">Cancel</a>
                </div>
            </form>
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
                <a class="action_button" onclick="$(this).addClass('disabled'); $.featherlight.close(); AccountController.remove_sub(AccountController.selected_box_item.data('subscription-id')).then(function(){AccountController.load_subscriptions();}); return false;">Yes, Remove</a>
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
        $('.skip-reason-form input, .skip-reason-form textarea').change(function(){
            if(!AccountController.get_skip_reason()){
                $('.skip-confirm-button').addClass('disabled');
            } else {
                $('.skip-confirm-button').removeClass('disabled');
            }
        });
        $('.skip-reason-form textarea').on('keyup keydown', function(){
            $('.skip-reason-form input[value=other]').prop('checked', true);
            if(!AccountController.get_skip_reason()){
                $('.skip-confirm-button').addClass('disabled');
            } else {
                $('.skip-confirm-button').removeClass('disabled');
            }
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
    var AccountController = AccountController || {};
    $(document).ready(function(){
        AccountController.selected_box_item = $('.sc-box-item[data-sc]').eq(0);
        if(AccountController.selected_box_item.length < 1){
            return;
        }
        switch(Shopify.queryParams.intent){
            default:
                return;
            case 'swapscent':
                optional_scripts.onload('mmenu', function(){
                    AccountController.show_swap();
                });
                break;
            case 'changedate':
                optional_scripts.onload(['pignose','mmenu'], function(){
                    AccountController.show_date_change();
                });
                break;

        }
    });
    function bind_events(){
        $('.portal-item-edit').unbind().click(function(e){
            $(this).closest('.portal-item').find('.portal-item-edit-container').slideToggle();
        });
        $('.portal-item .add-and-save').unbind().click(function(e){
            AccountController.selected_box_item = $(this).closest('.portal-item');
            AccountController.show_add_and_save();
        });
        $('.portal-item .swap-variant').unbind().change(function(e){
            AccountController.swap_variant($(this).closest('.portal-item').data('subscription-id'), e.target.value);
        });
        $('.portal-item .edit-frequency').unbind().change(function(e){
            AccountController.update_frequency($(this).closest('.portal-item').data('subscription-id'), e.target.value);
        });
        $('.portal-item .show-calendar').unbind().click(function(){
            $(this).closest('.portal-edit-date').find('.calendar').slideToggle();
        });
        $('.portal-item .portal-edit-cancel').unbind().click(function(e){
            e.preventDefault();
            AccountController.selected_box_item = $(this).closest('.portal-item');
            $('.sc-skip-image img').attr('src', AccountController.selected_box_item.data('master-image'));
            $('#sc-remove-confirm-modal .sc-modal-subtitle').html(AccountController.selected_box_item.data('product-name'));
            $.featherlight.close();
            $.featherlight($('#sc-remove-confirm-modal'), {
                variant: 'scent-club',
                afterOpen: $.noop, // Fix dumb app bug
            });
        });

        $('.remove-discount-link').unbind().click(function(e){
            e.preventDefault();
            $('.loader').fadeIn();
            var data = $('.sc-add-discount').serializeJSON();
            $.extend(data, AccountController.get_query_data());
            $.ajax({
                url: '/tools/skylar/subscriptions/update-discount',
                data: data,
                success: function(data){
                    console.log(data);
                    if(!data.success){
                        $('.sc-box-discounts').append('<div class="sc-discount-error">'+data.error+'</div>');
                        $('.loader').fadeOut();
                    } else {
                        AccountController.load_subscriptions();
                    }
                }
            });
        });
        $('.sc-add-discount').unbind().submit(function(e){
            e.preventDefault();
            $('.sc-discount-error').remove();
            var btn = $(this).find('.action_button');
            btn.attr('disabled', 'disabled').addClass('disabled');
            btn.find('span').removeClass("zoomIn").addClass('animated zoomOut');
            var data = $(this).serializeJSON();
            $.extend(data, AccountController.get_query_data());
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
                        AccountController.load_subscriptions();
                    }
                }
            });
        });
        $('.sc-addtobox-form').unbind().submit(function(e){
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
            $.extend(data, AccountController.get_query_data());
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
                        AccountController.load_subscriptions();
                    }
                }
            });
        });

        $('.sc-edit-date').unbind().click(function(e){
            AccountController.selected_box_item = $(this).closest('.sc-upcoming-shipment').find('.sc-box-item').eq(0);
            AccountController.show_date_change();
        });
        $('.sc-swap-link').unbind().click(function(e){
            e.preventDefault();
            AccountController.selected_box_item = $(this).closest('.sc-box-item');
            AccountController.show_swap();
        });
        $('.sc-skip-link-club').unbind().click(function(e){
            e.preventDefault();
            AccountController.selected_box_item = $(this).closest('.sc-box-item');
            $.featherlight($('#sc-skip-modal'), {
                variant: 'scent-club',
                afterOpen: $.noop, // Fix dumb app bug
            });
        });
        $('.sc-skip-link').unbind().click(function(e){
            e.preventDefault();
            AccountController.selected_box_item = $(this).closest('.sc-box-item');
            AccountController.show_skip_final();
        });
        $('.sc-unsub-link, .sc-remove-link').unbind().click(function(e){
            e.preventDefault();
            AccountController.selected_box_item = $(this).closest('.sc-box-item');
            $('.sc-skip-image img').attr('src', AccountController.selected_box_item.data('master-image'));
            var text = AccountController.selected_box_item.data('month-text')+' '+AccountController.selected_box_item.find('.sc-item-title').text().trim().replace('Monthly ', '');
            $('#sc-remove-confirm-modal .sc-modal-subtitle').html(text);
            $.featherlight.close();
            $.featherlight($('#sc-remove-confirm-modal'), {
                variant: 'scent-club',
                afterOpen: $.noop, // Fix dumb app bug
            });
        });
        $('.ac-choose-container').on('change submit', function(e){
            e.preventDefault();
            $([document.documentElement, document.body]).animate({
                scrollTop: AccountController.selected_box_item.closest('.sc-upcoming-shipment').offset().top -70
            }, 1000);
            $(this).slideUp();
            $(this).siblings('.ac-choose-button').find('.ac-choose-plus, .ac-choose-minus').toggle();
            var data = $(this).closest('form').serializeJSON();
            AccountController.ac_swap_scent(data.subscription_id, data.variant_id);
        });
        $('.ac-choose-button').click(function(){
            AccountController.selected_box_item = $(this).closest('.sc-box-item');
            $(this).siblings('.ac-choose-container').slideToggle();
            $(this).find('.ac-choose-plus, .ac-choose-minus').toggle();
        });
        $('.ac-cancel-link').unbind().click(function(e){
            e.preventDefault();
            AccountController.selected_box_item = $(this).closest('.sc-box-item');
            AccountController.show_ac_cancel_save();
        });
        $('.ac-edit-date').unbind().click(function(e){
            AccountController.selected_box_item = $(this).closest('.sc-upcoming-shipment').find('.sc-box-item').eq(0);
            AccountController.show_ac_move_save();
        });
    }
</script>