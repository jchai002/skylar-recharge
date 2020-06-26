<?php

global $db, $sc, $rc, $ids_by_scent, $is_alias;

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
	$months = $_REQUEST['months'] ?? 4;
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
            <div class="sc-upcoming-container">
                <?php
                    $shipment_index = -1;
                    $sc_shipment_index = -1;
                    foreach($schedule->get() as $shipment_list){
                        $shipment_index++;
                        foreach($shipment_list['addresses'] as $address_id => $upcoming_shipment){
							$last_unskipped_charge = false;


                            $has_ac_followup = false;
							$ac_delivered = false;
							$ac_allow_pushback = true;
							$ac_pushed_up = false;
							$has_sc = false;
                            foreach($upcoming_shipment['items'] as $item){
								if(!empty($item['charge_id']) && !empty($schedule->charges()[$item['charge_id']]) && $schedule->charges()[$item['charge_id']]['status'] == 'QUEUED'){
									$last_unskipped_charge = $schedule->charges()[$item['charge_id']];
								}
								echo "<!-- ";
								var_dump($last_unskipped_charge);
								echo " -->";
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
                                    $sc_shipment_index++;
									$has_sc = true;
                                }
                            }
                            ?>
                            <div class="sc-upcoming-shipment">
                                <div class="sc-box-info">
                                    <span class="sc-box-shiplabel">Shipping Date</span>
									<?php if($has_ac_followup && !$ac_delivered && !$ac_pushed_up){ ?>
                                        <span class="sc-box-date sc-box-date-pending">Pending Sample Delivery</span>
									<?php } else if($has_ac_followup && $ac_allow_pushback){ ?>
                                        <span class="sc-box-date ac-edit-date"><?=date('F j', $shipment_list['ship_date_time']) ?> <img src="{{ 'icon-chevron-down.svg' | file_url }}" /></span>
                                    <?php } else if($has_ac_followup){ ?>
                                        <span class="sc-box-date"><?=date('F j', $shipment_list['ship_date_time']) ?></span>
                                    <?php } else if($shipment_index == 0 || ($has_sc && $sc_shipment_index == 0)){ ?>
                                        <span class="sc-box-date sc-edit-date"><?=date('F j', $shipment_list['ship_date_time']) ?> <img src="{{ 'icon-chevron-down.svg' | file_url }}" /></span>
                                    <?php } else { ?>
                                        <span class="sc-box-date"><?=date('F j', $shipment_list['ship_date_time']) ?></span>
                                    <?php } ?>
                                </div>
                                <?php foreach($upcoming_shipment['items'] as $item){
									$monthly_scent = sc_get_monthly_scent($db, $shipment_list['ship_date_time'], is_admin_address($item['address_id']));
                                    if(!empty($monthly_scent)){
                                        $box_swap_image = 'data-swap-image="{{ all_products["'.$monthly_scent['handle'].'"].metafields.scent_club.swap_icon | file_img_url: \'30x30\' }}"';
                                        $box_swap_text = 'data-swap-text="'.$monthly_scent['variant_title'].'"';
									} else {
                                        $box_swap_image = 'data-swap-image="{{ \'sc-logo.svg\' | file_url }}"';
										$box_swap_text = 'data-swap-text="Monthly Scent"';
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
										<?=$box_swap_text?>
                                        data-month-text="<?=date('F', $upcoming_shipment['ship_date_time'])?>"
                                        data-subscription-id="<?=$item['subscription_id']?>"
                                        <?= !empty($item['charge_id']) ? 'data-charge-id="'.$item['charge_id'].'"' : '' ?>
                                        data-type="<?=$item['type']?>"
                                        data-types="<?=implode($item['types'])?>"
                                        <?= is_scent_club_any(get_product($db, $item['shopify_product_id'])) ? 'data-sc' : ''?>
                                        data-sc-type="<?= is_scent_club(get_product($db, $item['shopify_product_id'])) ? 'default' : ''?><?= is_scent_club_swap(get_product($db, $item['shopify_product_id'])) ? 'swap' : ''?><?= is_scent_club_month(get_product($db, $item['shopify_product_id'])) ? 'monthly' : ''?><?= !is_scent_club_any(get_product($db, $item['shopify_product_id'])) ? 'none' : ''?>"
                                        <?= is_ac_followup_lineitem($item) ? 'data-ac' : '' ?>
										<?= is_ac_pushed_back($item) ? 'data-ac-pushed-back' : '' ?>
										<?= is_ac_delivered($item) ? 'data-ac-delivered' : '' ?>
                                        data-ship-time="<?=$upcoming_shipment['ship_date_time']?>"
                                    >
                                        <?php if(!empty($item['skipped']) && !empty($item['charge_id'])){ ?>
                                            <a class="sc-unskip-link" href="#" onclick="$(this).addClass('disabled'); AccountController.unskip_charge(<?=$item['subscription_id']?>, <?=$item['charge_id']?>, '<?=$item['type']?>'); return false;"><span>Unskip Box</span></a>
                                        <?php } else if(!empty($item['skipped'])){ ?>
                                            <a class="sc-unskip-link" href="#" onclick="$(this).addClass('disabled'); AccountController.unskip_charge(<?=$item['subscription_id']?>, 0, '<?=$item['type']?>'); return false;"><span>Unskip Box</span></a>
                                        <?php } else if(is_ac_followup_lineitem($item)){ ?>
                                            <a class="ac-item-corner-link ac-cancel-link" href="#"><span>Cancel My Trial</span></a>
										<?php } else if(is_scent_club_month(get_product($db, $item['shopify_product_id'])) && !empty($item['properties']['_swap'])){ ?>
                                            <a class="sc-skip-link-club" href="#"><span>Skip Box</span></a>
										<?php } else if(is_scent_club_swap(get_product($db, $item['shopify_product_id'])) && !empty($item['properties']['_swap'])){ ?>
                                            <a class="sc-skip-link-club" href="#"><span>Skip Box</span></a>
										<?php } else if($item['type'] == 'onetime' || in_array('onetime', $item['types'])){ ?>
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
													<?php } else if(is_scent_club_gift(get_product($db, $item['shopify_product_id']))){ ?>
                                                        <div class="sc-item-title">Scent Club Gift</div>
                                                        <div class="sc-item-subtitle"><?= $item['index'] + 1 ?> of <?= $item['expire_after_specific_number_of_charges'] ?? "{{ box_product.variants.first.title }}" ?></div>
                                                        <div><a class="sc-swap-link" href="#"><img src="{{ 'icon-swap.svg' | file_url }}" alt="Swap scent icon" /> <span>Swap Scent</span></a></div>
                                                    <?php } else if(is_scent_club(get_product($db, $item['shopify_product_id']))){ ?>
                                                        <div class="sc-item-title">Skylar Scent Club</div>
                                                        <div class="sc-item-subtitle"></div>
													<?php } else if(is_scent_club_month(get_product($db, $item['shopify_product_id']))){ ?>
                                                        <div class="sc-item-title">Skylar Scent Club</div>
                                                        <div class="sc-item-subtitle">{{ box_product.variants.first.title }}</div>
                                                        <?php if(!empty($item['properties']['_swap']) || !empty($item['swap'])){ ?>
                                                            <div><a class="sc-swap-link" href="#"><img src="{{ 'icon-swap.svg' | file_url }}" alt="Swap scent icon" /> <span>Swap Scent</span></a></div>
                                                        <?php } ?>
                                                    <?php } else if(is_scent_club_swap(get_product($db, $item['shopify_product_id']))){ ?>
                                                        <div class="sc-item-title"><?=$item['product_title']?></div>
                                                        <div class="sc-item-subtitle"><?=$item['variant_title']?></div>
                                                        <div><a class="sc-swap-link" href="#"><img src="{{ 'icon-swap.svg' | file_url }}" alt="Swap scent icon" /> <span>Swap Scent</span></a></div>
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
                                                    <div class="sc-item-detail-label">Delivery</div>
                                                    <div class="sc-item-detail-value">
                                                        <?php if(
                                                            empty($item['order_interval_frequency'])
                                                            || (!empty($item['status']) && $item['status'] == 'ONETIME')
                                                            || !empty($item['onetime'])
                                                        ){ ?>
                                                            Once
                                                        <?php } else if($item['order_interval_frequency'] == '1'){ ?>
                                                            Every <?=$item['order_interval_unit']?>
                                                        <?php } else { ?>
                                                            Every <?=$item['order_interval_frequency']?> <?=$item['order_interval_unit']?>s
                                                        <?php } ?>
                                                    </div>
                                                </div>
                                                <div>
                                                    <div class="sc-item-detail-label">Quantity</div>
                                                    <div class="sc-item-detail-value"><?=$item['quantity'] ?> </div>
                                                </div>
                                                <div>
                                                    <div class="sc-item-detail-label">Total</div>
                                                    <div class="sc-item-detail-value">$<?=price_without_trailing_zeroes($item['price']) ?> </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php if(is_ac_followup_lineitem($item)){ ?>
                                            <div class="ac-choose-button">
                                                <img class="ac-swap-icon" src="{{ 'swapscent-black.svg' | file_url }}" alt="swap icon" />
                                                <span>Change My Scent</span>
                                                <div class="ac-choose-plus">+</div>
                                                <div class="ac-choose-minus">-</div>
                                            </div>
                                            <form class="ac-choose-container">
                                                <input type="hidden" name="subscription_id" value="<?=$item['subscription_id']?>" />
                                                <div class="ac-choose-title">You can change the full-size bottle by choosing any of the options below.</div>
                                                <div class="ac-scent-options">
                                                    <?php foreach($ids_by_scent as $handle => $scent_ids){
                                                        ?>
                                                        {% assign ac_choose_product = all_products['<?=$handle?>'] %}
                                                        <label class="ac-scent-option">
                                                            <input type="radio" name="variant_id" value="{{ ac_choose_product.variants.first.id }}" <?= $item['shopify_product_id'] == $scent_ids['product'] ? 'checked ' : '' ?>/>
                                                            <div class="ac-scent-image">
                                                                <img class="lazyload lazypreload ac-check-image" data-srcset="{{ 'ac-checkmark.png' | file_img_url: '52x52' }} 1x, {{ 'ac-checkmark.png' | file_img_url: '104x104' }} 2x" alt="checked" />
                                                                <img class="lazyload lazypreload" alt="<?=$handle?> product image" data-srcset="{{ ac_choose_product | img_url: '270x270' }} 1x, {{ ac_choose_product | img_url: '540x540' }} 2x" />
                                                            </div>
                                                            <div class="ac-scent-title">{{ ac_choose_product.title }}</div>
                                                            <div class="ac-scent-desc">{{ ac_choose_product.metafields.skylar.scent_tags }}</div>
                                                        </label>
                                                    <?php } ?>
                                                </div>
                                            </form>
                                         <?php } ?>
                                        <div class="clearfix"></div>
                                    </div>
                                <?php } ?>
								<?php if(!$has_ac_followup || $has_sc){ ?>
									<div class="portal-item-actions">
										<div class="action_button add-and-save">Add and save!</div>
									</div>
								<?php } ?>
                                <?php if($shipment_index == 0 && !$has_ac_followup){ ?>
                                    <div class="sc-box-discounts<?= !empty($all_skipped) ? ' sc-box-skipped' : '' ?>">
                                        <?php foreach($upcoming_shipment['discounts'] as $discount){ ?>
                                                <div class="sc-box-discount">
                                                <?php if(strpos($discount['code'], 'ST-10-') === 0){ ?>
                                                    <div class="sc-discount-title">Discount</div>
                                                <?php } else if($discount['code'] === 'CSVOS'){ ?>
                                                    <div class="sc-discount-title">Discount</div>
                                                <?php } else { ?>
                                                    <div class="sc-discount-title"><?=$discount['code']?> <a href="#" class="remove-discount-link">(remove)</a>:</div>
                                                <?php } ?>
													<?php if($discount['type'] == 'percentage'){ ?>
                                                        <div class="sc-discount-value"><?=$discount['amount']?>% (-$<?=price_without_trailing_zeroes($discount['applied_amount'])?>)</div>
													<?php } else { ?>
                                                        <div class="sc-discount-value">-$<?=price_without_trailing_zeroes($discount['applied_amount']) ?></div>
													<?php } ?>
                                                </div>
                                        <?php } ?>
                                        <div class="sc-discount-link" onclick="$('.sc-add-discount').show();$(this).hide();">Got a promo code?</div>
                                        <form class="sc-add-discount" style="display: none;">
                                            <div><input type="text" name="discount_code" /></div>
                                            <div><input type="submit" value="Apply" class="action_button inverted" /></div>
                                            <?php if(!empty($last_unskipped_charge)){ ?>
                                                <input type="hidden" name="address_id" value="<?=$address_id?>" />
                                                <input type="hidden" name="charge_id" value="<?=$last_unskipped_charge['id']?>" />
                                            <?php } ?>
                                        </form>
                                    </div>
									<?php if(!empty($last_unskipped_charge)){ ?>
                                        <!-- <?php print_r($last_unskipped_charge) ?> -->
                                        <?php if(!empty($last_unskipped_charge['shipping_lines']) && !empty($last_unskipped_charge['shipping_lines'][0]) && $last_unskipped_charge['shipping_lines'][0]['price'] > 0){
                                            $shipping_line = $last_unskipped_charge['shipping_lines'][0];
                                            ?>
                                            <div class="sc-box-shipping<?= !empty($all_skipped) ? ' sc-box-skipped' : '' ?>">
                                                <div class="sc-shipping-title"><?=$shipping_line['title']?></div>
                                                <div class="sc-shipping-value">$<?=price_without_trailing_zeroes($shipping_line['price'])?></div>
                                            </div>
                                        <?php
                                        }
                                        if(!empty($last_unskipped_charge)){ ?>
                                            <div class="sc-box-shipping<?= !empty($all_skipped) ? ' sc-box-skipped' : '' ?>">
                                                <div class="sc-shipping-title">Tax</div>
                                                <div class="sc-shipping-value">$<?=price_without_trailing_zeroes($last_unskipped_charge['total_tax'])?></div>
                                            </div>
                                        <?php } ?>
                                        <div class="sc-box-total<?= !empty($all_skipped) ? ' sc-box-skipped' : '' ?>">
                                            Grand Total: $<?= price_without_trailing_zeroes($last_unskipped_charge['total_price']) ?>
                                        </div>
                                    <?php } else { ?>
                                        <div class="sc-box-total<?= !empty($all_skipped) ? ' sc-box-skipped' : '' ?>">
                                            Grand Total: $<?= price_without_trailing_zeroes(array_sum(array_column($upcoming_shipment['items'], 'price'))) ?>
                                        </div>
                                    <?php } ?>
                                <?php } ?>
                            </div>
                        <?php
                        }
                        if($next_section_shown){
                            continue;
                        }
                        if($shipment_index < $next_section_index){
                            continue;
                        }
                        if(count($schedule->get()) == 1){
                            continue;
                        }
                        if(empty($sc_main_sub)){
                            continue;
                        }
        				$next_section_shown = true;
                        ?>
                    </div>
                </div>
                <div class="sc-spacer"></div>
                <div class="sc-hr"></div>
                <div class="sc-portal-innercontainer sc-schedule-container">
                    <div class="sc-portal-title">Your Upcoming Skylar Box<?= count($schedule->get()) > 2 ? 'es' : '' ?></div>
                    <div class="sc-portal-box-list">
                <?php } ?>
            </div>
            <div class="sc-load-more" data-months="<?=$months?>">
                <?php if(count($schedule->subscriptions()) > 0){ ?>
                    <a href="#" class="action_button" onclick="AccountController.load_schedule(<?=$months+3?>); return false;">View More</a>
                <?php } ?>
            </div>
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
    <div id="sc-cancel-confirm-modal" class="sc-confirm-modal">
        <div>
            <div class="sc-modal-title">Why would you like to remove this item?</div>
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
    <div id="sc-cancel-item-confirm-modal" class="sc-confirm-modal">
        <div>
            <div class="sc-modal-title">Why would you like to remove this item?</div>
            <form id="sc-cancel-item-reason-form" class="skip-reason-form">
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
                        <input type="radio" name="skip_reason" value="other">
                        <span class="radio-visual"></span>
                        <span>Other Reason</span>
                    </label>
                    <textarea name="other_reason" title="Other Reason"></textarea>
                </div>
                <div class="sc-skip-options">
                    <a class="action_button skip-confirm-button disabled" onclick="if($(this).hasClass('disabled')){return false;} $(this).addClass('disabled'); AccountController.remove_sub(AccountController.selected_box_item.data('subscription-id'), AccountController.get_skip_reason()).then(function(){AccountController.reload()}); return false;">Remove Item</a>
                    <a class="action_button inverted" onclick="$.featherlight.close(); return false;">Go Back</a>
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
                <a class="action_button portal-skip-other-link">Yes, Remove</a>
                <a class="action_button inverted" onclick="$.featherlight.close(); return false;">Cancel</a>
            </div>
        </div>
    </div>
    <div id="portal-remove-other-confirm-modal">
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
{% render 'sc-portal-modals' %}
<script class="portal-data" type="application/json"><?=json_encode(['subscriptions'=>array_merge($schedule->subscriptions(), $schedule->onetimes())])?></script>
{{ 'featherlight.js' | asset_url | script_tag }}
{{ 'featherlight.css' | asset_url | stylesheet_tag }}
{{ 'sc-portal.js' | asset_url | script_tag }}
<script>
    $(document).ready(function(){
        /*
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
         */
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

        $('.remove-discount-link').unbind().click(function(e){
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
        $('.sc-add-discount').unbind().submit(function(e){
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


        $('.portal-skip-other-link').unbind().click(function(e){
            e.preventDefault();
            $(this).addClass('disabled');
            $.featherlight.close();
            if(AccountController.selected_box_item.data('types').split(',').indexOf('onetime')){
                $.featherlight($('#sc-cancel-item-confirm-modal'));
            } else {
                $.featherlight($('#sc-cancel-confirm-modal'));
            }
        });
        $('.sc-upcoming-shipment .add-and-save').unbind().click(function(e){
            AccountController.selected_box_item = $(this).closest('.sc-upcoming-shipment').find('.sc-box-item').eq(0);
            AccountController.show_add_and_save();
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
            // $('.sc-skip-image img').attr('src', AccountController.selected_box_item.data('master-image'));
            // var text = AccountController.selected_box_item.data('month-text')+' '+AccountController.selected_box_item.find('.sc-item-title').text().trim().replace('Monthly ', '');
            // $('#sc-remove-confirm-modal .sc-modal-subtitle').html(text);
            $.featherlight.close();
            $.featherlight($('#portal-remove-other-confirm-modal'), {
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