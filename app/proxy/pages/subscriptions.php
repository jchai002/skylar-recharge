<?php

global $db, $sc, $rc, $is_alias;

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
	$months = $_REQUEST['months'] ?? 12;
	$schedule = new SubscriptionSchedule($db, $rc, $rc_customer_id, strtotime(date('Y-m-t',strtotime("+$months months"))));
	if(!empty($is_alias)){
		$schedule->is_alias = true;
	}
	if(!empty($_REQUEST['hidden_subs'])){
		$schedule->hidden_subscription_ids = $_REQUEST['hidden_subs'];
	}
}

if(empty($rc_customer_id) || empty($schedule->get())){
	require_once(__DIR__.'/no-subscriptions.php');
	exit;
}

$sc_main_sub = sc_get_main_subscription($db, $rc, [
	'customer_id' => $rc_customer_id,
	'status' => 'ACTIVE',
]);
$sc_next_month_scent = sc_get_monthly_scent($db, get_next_month(), true);
if(strtotime($sc_next_month_scent['member_launch']) <= time()){
	$sc_next_month_scent = sc_get_monthly_scent($db, get_month_by_offset(2), true);
}
$discount_code = "ST-10-".$rc_customer_id;
$stmt = $db->query("SELECT 1 FROM orders WHERE discount_code='$discount_code'");
$discount_save_available = $stmt->rowCount() == 0;

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

sc_conditional_billing($rc, $_REQUEST['c']);
?>
<!--
<?php print_r($schedule->charges()); ?>
$schedule
<?php
echo count($schedule->get()).PHP_EOL;
print_r($schedule->get());
$shipment_list = $schedule->get()[0];
$subscriptions_by_date = array_values($schedule->subscriptions());
uasort($subscriptions_by_date, function($a, $b){
	if($a['scheduled_at_time'] == $b['scheduled_at_time']){
		return 0;
	}
	return $a['scheduled_at_time'] > $b['scheduled_at_time'] ? 1 : -1;
});


$other_onetimes = [];
foreach($schedule->onetimes() as $item){
	if($item['status'] != 'ONETIME'){
		continue;
	}
	// Don't show scent club swaps (monthly products and swap-ins)
	if(!empty($item['properties']['_swap'])){
		// Change next ship date on parent
		if(!empty($schedule->subscriptions()[$item['properties']['_swap']]) && $item['scheduled_at_time'] < $schedule->subscriptions()[$item['properties']['_swap']]['scheduled_at_time']){
			$schedule->subscriptions()[$item['properties']['_swap']]['scheduled_at_time'] = $item['scheduled_at_time'];
		}
		continue;
	}
	$other_onetimes[] = $item;
}
uasort($other_onetimes, function($a, $b){
	if($a['scheduled_at_time'] == $b['scheduled_at_time']){
		return 0;
	}
	return $a['scheduled_at_time'] > $b['scheduled_at_time'] ? 1 : -1;
});
?>
-->
{% assign portal_page = 'subscriptions' %}
{{ 'sc-portal.scss.css' | asset_url | stylesheet_tag }}
<div class="sc-portal-page sc-portal-{{ portal_page }} sc-portal-container">
    {% include 'sc-member-nav' %}
    <div class="sc-portal-content">
		<?php if(!empty($subscriptions_by_date)){ ?>
            <div class="portal-innercontainer">
                <div class="sc-portal-title">Your Subscriptions <img class="lazyload lazypreload" height="21" data-src="{{ 'subscription-icon.svg' | file_url }}" /></div>
                <div class="sc-portal-subtitle">Manage your subscriptions here</div>
				<?php
				$stmt_scent_change_options = $db->prepare("SELECT s.code, s.title, v.shopify_id as shopify_variant_id FROM variant_attributes va
                LEFT JOIN scents s ON va.scent_id=s.id
                LEFT JOIN variants v ON va.variant_id=v.id
                WHERE va.format_id=:format_id AND va.product_type_id=:product_type_id;");
				foreach($subscriptions_by_date as $item){
					echo "<!--";
					$variant = get_variant($db, $item['shopify_variant_id']);
					$scent_change_options = [];
					if(!empty($variant['attributes'])){
						$stmt_scent_change_options->execute([
							'format_id' => $variant['attributes']['format_id'],
							'product_type_id' => $variant['attributes']['product_type_id'],
						]);
						$scent_change_options = $stmt_scent_change_options->fetchAll();
					}
					print_r($scent_change_options);
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
						<?= is_scent_club_any(get_product($db, $item['shopify_product_id'])) ? 'data-sc' : ''?>
                    >
						<?php if(!is_scent_club_any(get_product($db, $item['shopify_product_id']))){ ?>
                            <div class="portal-item-edit">Edit</div>
						<?php } ?>
                        <div class="portal-item-subscribed">Subscribed <img class="lazyload lazypreload" height="15" data-src="{{ 'subscription-icon.svg' | file_url }}" /></div>
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
						if(false && !empty($this_sub_onetimes)){
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
                                            <div class="portal-item-detail-label"><?= empty($item['product_title']) ? $item['title'] : $item['product_title'] ?></div>
                                            <div class="portal-item-quantity">
                                                <span class="portal-quantity-miuns">-</span>
                                                <span class="portal-quantity-amount"><?=$item['quantity']?></span>
                                                <span class="portal-quantity-plus">+</span>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="portal-item-detail-label">Price</div>
                                            <div class="portal-item-detail-value">$<?= price_without_trailing_zeroes($item['price']) ?></div>
                                        </div>
                                    </div>
								<?php } ?>
                            </div>
						<?php } ?>
                        <div class="portal-item-actions">
                            <div class="action_button add-and-save">Add and save!</div>
                        </div>
                        <form class="portal-item-edit-container" <?=!is_scent_club_any(get_product($db, $item['shopify_product_id'])) ? '' : 'style="display:block;"' ?>>
							<?php if(!is_scent_club_any(get_product($db, $item['shopify_product_id']))){ ?>
                                <div class="portal-edit-row">
									<?php if(get_product($db, $item['shopify_product_id'])['type'] == 'Body Bundle'){ ?>
                                        <div class="portal-edit-select portal-edit-date">
                                            <label class="portal-edit-label" for="edit-date-<?=$item['subscription_id']?>">Shipping Date</label>
                                            <div class="portal-edit-control">
                                                <div id="edit-date-<?=$item['subscription_id']?>" class="fake-select show-calendar">
													<?=date('M j', $item['scheduled_at_time'])?>
                                                </div>
                                            </div>
                                            <div class="calendar<?=is_scent_club_any(get_product($db, $item['shopify_product_id'])) ? ' one-month' : '' ?> floating-calendar hidden"></div>
                                        </div>
									<?php } ?>
									<?php
									$frequencies = [];
									$product = get_product($db, $item['shopify_product_id']);
									echo "<!--".print_r($product['tags'], true)."-->";
									if(in_array('Portal Category: Gift', $product['tags'])){
										$frequencies = [];
									} else if($product['type'] == 'Body Bundle'){
										$frequencies = [
											'1' => 'Monthly',
											'2' => 'Every 2 months',
										];
									} else if(strpos($product['type'], 'Body') !== false){
										$frequencies = [
											'onetime' => 'Once',
											'1' => 'Monthly',
											'2' => 'Every 2 months',
										];
									} else {
										$frequencies = [
											'onetime' => 'Once',
											'6' => 'Every 6 months',
											'9' => 'Every 9 months',
										];
									}
									if(!empty($frequencies)){
										?>
                                        <div class="portal-edit-select portal-edit-frequency">
                                            <label class="portal-edit-label" for="edit-frequency-<?=$item['subscription_id']?>">Frequency</label>
                                            <div class="portal-edit-control">
                                                <select class="edit-frequency" id="edit-frequency-<?=$item['subscription_id']?>" name="frequency">
													<?php foreach($frequencies as $value => $label){ ?>
                                                        <option value="<?=$value?>"<?=$value == ($item['order_interval_frequency'] ?? 'onetime') ? ' selected' : '' ?>><?=$label?></option>
													<?php } ?>
                                                </select>
                                            </div>
                                        </div>
									<?php } ?>
                                    <div class="portal-edit-links">
                                        <a class="portal-edit-cancel<?= is_scent_club_any(get_product($db, $item['shopify_product_id'])) ? '' : '-other' ?>" href="#">Cancel Shipment</a>
                                    </div>
                                </div>
								<?php if(!empty($scent_change_options)){ ?>
                                    <div class="portal-edit-divider"></div>
                                    <div class="portal-edit-row">
                                        <div class="portal-edit-radio portal-edit-scent">
                                            <div class="portal-edit-label">Change Your Scent</div>
                                            <div class="portal-edit-control">
												<?php foreach($scent_change_options as $scent_change_option){ ?>
                                                    <div class="portal-swap-option">
                                                        <input type="radio" id="edit-scent-<?=$scent_change_option['shopify_variant_id']?>" class="swap-variant" name="variant" value="<?=$scent_change_option['shopify_variant_id']?>"<?= $scent_change_option['shopify_variant_id'] == $item['shopify_variant_id'] ? ' checked' : '' ?><?= is_scent_club_month($item['shopify_product_id']) && is_scent_club_month($scent_change_option['shopify_variant_id']) ? ' checked' : '' ?><?= is_scent_club($item['shopify_product_id']) && is_scent_club($scent_change_option['shopify_variant_id']) ? ' checked' : '' ?>>
                                                        <label for="edit-scent-<?=$scent_change_option['shopify_variant_id']?>">
															<?php if(!empty($scent_change_option['icon'])){ ?>
                                                                <img class="lazyload lazypreload" data-src="<?=$scent_change_option['icon']?>" />
															<?php } else { ?>
                                                                <img class="lazyload lazypreload" data-src="{{ 'scent-icon_<?=$scent_change_option['code']?>.png' | file_img_url }}" />
															<?php } ?>
                                                            <div><?=$scent_change_option['title']?></div>
                                                        </label>
                                                    </div>
												<?php } ?>
                                            </div>
                                        </div>
                                    </div>
								<?php } ?>
							<?php } else { ?>
                                <div class="portal-edit-float">
                                    <a class="portal-edit-cancel" href="#">Cancel Shipment</a>
                                </div>
							<?php } ?>
                        </form>
                    </div>
				<?php } ?>
            </div>
		<?php } ?>
		<?php
		if(!empty($other_onetimes)){
			?>
            <div class="portal-innercontainer">
                <div class="sc-portal-title">One-time only <img class="lazyload lazypreload" height="14" data-src="{{ 'one-time-arrow.svg' | asset_url }}" /></div>
                <div class="sc-portal-subtitle">Manage your one-time products here</div>
				<?php
				$stmt_scent_change_options = $db->prepare("SELECT s.code, s.title, v.shopify_id as shopify_variant_id FROM variant_attributes va
                LEFT JOIN scents s ON va.scent_id=s.id
                LEFT JOIN variants v ON va.variant_id=v.id
                WHERE va.format_id=:format_id AND va.product_type_id=:product_type_id;");
				foreach($other_onetimes as $item){
					echo "<!--";
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
						<?php if(!is_scent_club_any(get_product($db, $item['shopify_product_id']))){ ?>
                            <div class="portal-item-edit">Edit</div>
						<?php } ?>
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
                        <form class="portal-item-edit-container" <?=!is_scent_club_any(get_product($db, $item['shopify_product_id'])) ? '' : 'style="display:block;"' ?>>
							<?php if(!is_scent_club_any(get_product($db, $item['shopify_product_id']))){ ?>
                                <div class="portal-edit-row">
									<?php /*
                                <div class="portal-edit-select portal-edit-date">
                                    <label class="portal-edit-label" for="edit-date-<?=$item['subscription_id']?>">Shipping Date</label>
                                    <div class="portal-edit-control">
                                        <div id="edit-date-<?=$item['subscription_id']?>" class="fake-select show-calendar">
                                            <?=date('M j', $item['scheduled_at_time'])?>
                                        </div>
                                    </div>
                                    <div class="calendar<?=is_scent_club_any(get_product($db, $item['shopify_product_id'])) ? ' one-month' : '' ?> floating-calendar hidden"></div>
                                </div>
                                */ ?>

									<?php
									$frequencies = [];
									$product = get_product($db, $item['shopify_product_id']);
									echo "<!--".print_r($product['tags'], true)."-->";
									if(in_array('Portal Category: Gift', $product['tags'])){
										$frequencies = [];
									} else if(is_scent_club_any(get_product($db, $item['shopify_product_id']))){
										$frequencies = [];
									} else if($product['type'] == 'Body Bundle'){
										$frequencies = [
											'1' => 'Monthly',
											'2' => 'Every 2 months',
										];
									} else if(strpos($product['type'], 'Body') !== false){
										$frequencies = [
											'onetime' => 'Once',
											'1' => 'Monthly',
											'2' => 'Every 2 months',
										];
									} else {
										$frequencies = [
											'onetime' => 'Once',
											'6' => 'Every 6 months',
											'9' => 'Every 9 months',
										];
									}
									if(!empty($frequencies)){
										?>
                                        <div class="portal-edit-select portal-edit-frequency">
                                            <label class="portal-edit-label" for="edit-frequency-<?=$item['subscription_id']?>">Frequency</label>
                                            <div class="portal-edit-control">
                                                <select class="edit-frequency" id="edit-frequency-<?=$item['subscription_id']?>" name="frequency">
													<?php foreach($frequencies as $value => $label){ ?>
                                                        <option value="<?=$value?>"<?=$value == 'onetime' ? ' selected' : '' ?>><?=$label?></option>
													<?php } ?>
                                                </select>
                                            </div>
                                        </div>
									<?php } ?>
                                    <div class="portal-edit-links">
                                        <a class="portal-edit-cancel<?= is_scent_club_any(get_product($db, $item['shopify_product_id'])) ? '' : '-other' ?>" href="#">Cancel Shipment</a>
                                    </div>
                                </div>
								<?php if(!empty($scent_change_options)){ ?>
                                    <div class="portal-edit-divider"></div>
                                    <div class="portal-edit-row">
                                        <div class="portal-edit-radio portal-edit-scent">
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
							<?php } else { ?>
                                <div class="portal-edit-float">
                                    <a class="portal-edit-cancel" href="#">Cancel Shipment</a>
                                </div>
							<?php } ?>
                        </form>
                    </div>
				<?php } ?>
            </div>
		<?php } ?>
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
    <div id="portal-remove-other-confirm-modal">
        <div class="portal-modal-title">Are you sure you want to remove this item?</div>
        <div class="portal-modal-subtitle">
            Subscribers get to add exclusive deals to their box. <br />
            If you remove, you won’t be able to get this at an awesome deal.
        </div>
        <div class="portal-skip-options">
            <a class="action_button" onclick="$.featherlight.close(); return false;">Keep This Item</a>
            <a class="portal-skip-other-link">Remove This Item</a>
        </div>
    </div>
    <div id="portal-sc-cancel-save-skip" class="portal-modal-save-skip">
        <div class="portal-modal-title">Did you know you can skip a month?</div>
		<?php if(!empty($sc_next_month_scent)){ ?>
            {% assign scent_product = all_products["<?=$sc_next_month_scent['handle']?>"] %}
            <div class="portal-modal-description">
                <div>Here's a sneak peek of next month's scent</div>
                <div class="sc-swap-option">
                    <img src="{{ scent_product.metafields.scent_club.swap_icon | file_img_url: '30x30' }}" />
                    {% if scent_product.metafields.skylar.scent_tags != blank %}
                    <div class="monthly-scent-name">{{ scent_product.metafields.skylar.scent_tags | replace : ", ", " • " }}</div>
                    {% elsif scent_product.metafields.tag_p_grid != blank %}
                    <div class="monthly-scent-name">{{ scent_product.metafields.tag_p_grid.text | replace : ", ", " • " }}</div>
                    {% endif %}
                </div>
            </div>
		<?php } ?>
        <div class="portal-skip-options">
            <a class="action_button" onclick="$.featherlight.close(); AccountController.skip_charge(AccountController.selected_box_item.data('subscription-id'), AccountController.selected_box_item.data('charge-id'), 'sc-save'); return false;">Skip A Month</a>
            <a class="next-modal">No, I'd still like to cancel</a>
        </div>
    </div>
    <div id="portal-sc-cancel-save-discount" class="portal-modal-save-discount">
        <div class="portal-modal-title">Are you sure?</div>
        <div class="portal-modal-description">
            <div>We'll apply $10 off this months scent.</div>
            <div class="portal-modal-discount-image">{% include 'svg-definitions' with 'heart-dollar' %}</div>
        </div>
        <div class="portal-skip-options">
            <a class="action_button" onclick="$.featherlight.close(); AccountController.apply_save_discount(AccountController.selected_box_item.data('subscription-id')); return false;">Get $10 Off</a>
            <a class="portal-skip-other-link">No, I'd still like to cancel</a>
        </div>
    </div>
    <div id="sc-cancel-confirm-modal" class="sc-confirm-modal">
        <div>
            <div class="portal-modal-title">Why would you like to cancel your subscription?</div>
            <form id="sc-cancel-reason-form" class="skip-reason-form">
                <div class="skip-reason-list">
                    <label>
                        <input type="radio" name="skip_reason" value="I don't like the scent or products">
                        <span class="radio-visual"></span>
                        <span>I don't like the scent or products</span>
                    </label>
                    <label>
                        <input type="radio" name="skip_reason" value="I have too much">
                        <span class="radio-visual"></span>
                        <span>I have too much</span>
                    </label>
                    <label>
                        <input type="radio" name="skip_reason" value="I'm having a sensitivity to the product">
                        <span class="radio-visual"></span>
                        <span>I'm having a sensitivity to the product</span>
                    </label>
                    <label>
                        <input type="radio" name="skip_reason" value="It's too expensive">
                        <span class="radio-visual"></span>
                        <span>It's too expensive</span>
                    </label>
                    <label>
                        <input type="radio" name="skip_reason" value="My order took too long to arrive">
                        <span class="radio-visual"></span>
                        <span>My order took too long to arrive</span>
                    </label>
                    <label>
                        <input type="radio" name="skip_reason" value="I just don't want a subscription">
                        <span class="radio-visual"></span>
                        <span>I just don't want a subscription</span>
                    </label>
                    <label>
                        <input type="radio" name="skip_reason" value="other">
                        <span class="radio-visual"></span>
                        <span>Other Reason</span>
                    </label>
                    <textarea name="other_reason" title="Other Reason"></textarea>
                </div>
                <div class="sc-skip-options">
                    <a class="action_button skip-confirm-button disabled" onclick="if($(this).hasClass('disabled')){return false;} $(this).addClass('disabled'); AccountController.remove_sub(AccountController.selected_box_item.data('subscription-id'), AccountController.get_skip_reason()).then(function(){AccountController.reload()}); return false;">Cancel Subscription</a>
                    <a class="action_button inverted" onclick="$.featherlight.close(); return false;">Go Back</a>
                </div>
            </form>
        </div>
    </div>
</div>
{% render 'sc-portal-modals' %}
<script class="portal-data" type="application/json"><?=json_encode(['subscriptions'=>array_merge($schedule->subscriptions(), $schedule->onetimes())])?></script>
{{ 'featherlight.js' | asset_url | script_tag }}
{{ 'featherlight.css' | asset_url | stylesheet_tag }}
{{ 'sc-portal.js' | asset_url | script_tag }}
<script>
    AccountController.sync_add_and_save_pane(AccountController.portal_data.subscriptions);
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
    function bind_events(){
        $('.portal-item-edit').unbind().click(function(e){
            var container = $(this).closest('.portal-item').find('.portal-item-edit-container');
            if(container.is(':hidden')){
                $(this).html('Close');
            } else {
                $(this).html('Edit');
            }
            var edit_top = container.is(':hidden') ? $(this).closest('.portal-item').innerHeight() + $(this).closest('.portal-item').offset().top : container.offset().top; // Can't get offset of hidden elems
            if(container.is(':hidden')/* && edit_top + 60 > window.scrollY + window.innerHeight*/){
                $([document.documentElement, document.body]).animate({
                    scrollTop: container.closest('.portal-item').offset().top - $('.header:visible').height() - 10,
                });
            }
            container.slideToggle();
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
        $('.portal-item .portal-edit-cancel-other').unbind().click(function(e){
            e.preventDefault();
            AccountController.selected_box_item = $(this).closest('.portal-item');
            $.featherlight.close();
            $.featherlight($('#portal-remove-other-confirm-modal'), {
                variant: 'scent-club',
                afterOpen: $.noop, // Fix dumb app bug
            });
        });
        $('.portal-item .portal-edit-cancel').unbind().click(function(e){
            e.preventDefault();
            AccountController.selected_box_item = $(this).closest('.portal-item');
            $.featherlight.close();
            if(AccountController.selected_box_item.data('sc') === undefined){
                $.featherlight($('#portal-remove-other-confirm-modal'), {
                    variant: 'scent-club',
                    afterOpen: $.noop, // Fix dumb app bug
                });
            } else {
                $.featherlight($('#portal-sc-cancel-save-skip'), {
                    variant: 'scent-club',
                    afterOpen: $.noop,
                });
            }
        });
        $('#portal-sc-cancel-save-skip .next-modal').unbind().click(function(e){
            $.featherlight.close();
			<?php if($discount_save_available){?>
            $.featherlight($('#portal-sc-cancel-save-discount'), {
                variant: 'scent-club',
                afterOpen: $.noop,
            });
			<?php } else { ?>
            $.featherlight.close();
            $.featherlight($('#sc-cancel-confirm-modal'));
			<?php } ?>
        });
        $('.portal-skip-other-link').unbind().click(function(e){
            e.preventDefault();
            $(this).addClass('disabled');
            $.featherlight.close();
            $.featherlight($('#sc-cancel-confirm-modal'));
        });
        optional_scripts.onload('pignose', function(){
            $('.portal-item-edit-container .calendar').each(function(){
                var box = $(this).closest('.portal-item');
                $(this).pignoseCalendar({
                    date: moment(parseInt(box.data('ship-time'))*1000),
                    minDate: moment(),
                    disabledWeekdays: [0, 6],
                    select: function(date){
                        AccountController.update_subscription_date(box.data('subscription-id'), date[0].format('YYYY-MM-DD'));
                        box.find('.calendar').slideUp();
                    },
                });
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

        // Old?
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