<?php
global $db, $sc, $rc;

// Actually add to box
if(strpos($_REQUEST['c'], '@') !== false){
	$res = $rc->get('/customers', [
		'email' => $_REQUEST['c'],
	]);
} else {
	$res = $rc->get('/customers', [
		'shopify_customer_id' => $_REQUEST['c'],
	]);
}
if(!empty($res['customers'])){
	$customer = $res['customers'][0];
	$res = $rc->get('/charges', [
		'customer_id' => $customer['id'],
		'status' => 'QUEUED',
	]);
	$charges = $res['charges'];
	usort($charges, function ($item1, $item2) {
		if (strtotime($item1['scheduled_at']) == strtotime($item2['scheduled_at'])) return 0;
		return strtotime($item1['scheduled_at']) < strtotime($item2['scheduled_at']) ? -1 : 1;
	});

	$add_to_charge = $charges[0];
	foreach($charges as $charge){
		foreach($charge['line_items'] as $line_item){
			if(is_scent_club_any(get_product($db, $line_item['shopify_product_id']))){
				$add_to_charge = $charge;
				break 2;
			}
		}
	}
}

if(!empty($add_to_charge)){
	$variant = get_variant($db, $_REQUEST['v']);
	$product = get_product($db, $variant['shopify_product_id']);
	$subscription_price = round($variant['price']*.9);
	$month = date('F', strtotime($add_to_charge['scheduled_at']));

	$res = $rc->post('/addresses/'.$add_to_charge['address_id'].'/onetimes', [
		'next_charge_scheduled_at' => $charge['scheduled_at'],
		'price' => $subscription_price,
		'quantity' => 1,
		'shopify_variant_id' => $variant['shopify_id'],
		'product_title' => $product['title'],
		'variant_title' => $variant['title'],
	]);
}
header('Content-Type: application/liquid');
echo "<!-- ".print_r($res, true)." -->";
?>

{% assign portal_page = 'lander-addtobox' %}
{{ 'sc-portal.scss.css' | asset_url | stylesheet_tag }}
<div class="sc-portal-page sc-portal-{{ portal_page }} sc-portal-container sc-portal-lander">
	<?php if(!empty($add_to_charge)){ ?>
	<div class="sc-lander-title">You added <?=$product['title']?> to your Skylar Box.</div>
	<div class="sc-lander-price"><span>Total:</span> <span class="was_price">$<?=$variant['price']?></span> <span class="price">$<?=number_format($subscription_price,2)?></span> <span class="sc-lander-savings">*You save 10%!</span></div>
	<div class="sc-lander-image">
		<img class="lazyload" data-srcset="{{ all_products['<?= $product['handle'] ?>'].featured_image | img_url: '220x280', crop: 'center' }} 1x, {{ all_products['<?= $product['handle'] ?>'].featured_image | img_url: '220x280', crop: 'center', scale: 2 }} 2x" />
	</div>
	<div class="sc-lander-note">
		This item will ship with your <?=$month?> box. <br />Need to make more changes to your box? <br class="sc-mobile" />Log into your account now.
	</div>
	<?php } else { ?>
		<div class="sc-lander-note">
			Sorry, we were unable to locate your account! Please log in to add your item:
		</div>
	<?php } ?>
	<div class="sc-lander-button">
		<a href="/account" class="action_button">Login to My Account</a>
	</div>
</div>
<style>
	.promo_banner {
		display: none;
	}
	.header {
		position: fixed;
	}
</style>