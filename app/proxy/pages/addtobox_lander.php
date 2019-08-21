<?php
global $db, $sc, $rc;

// Actually add to box
$variant = get_variant($db, $_REQUEST['v']);
$product = get_product($db, $variant['shopify_product_id']);
$subscription_price = round($variant['price']*.9);
$month = "September";

header('Content-Type: application/liquid');
?>

{% assign portal_page = 'lander-addtobox' %}
{{ 'sc-portal.scss.css' | asset_url | stylesheet_tag }}
<div class="sc-portal-page sc-portal-{{ portal_page }} sc-portal-container sc-portal-lander">
	<div class="sc-lander-title">You added <?=$product['title']?> to your Skylar Box</div>
	<div class="sc-lander-price">Total: <span class="was_price">$<?=$variant['price']?></span> <span class="price">$<?=number_format($subscription_price,2)?></span> <span class="sc-lander-savings">*You save 10%!</span></div>
	<div class="sc-lander-image">
		<img class="lazyload" data-srcset="{{ all_products['<?= $product['handle'] ?>'].featured_image | img_url: '220x280', crop: 'center' }} 1x, {{ all_products['<?= $product['handle'] ?>'].featured_image | img_url: '220x280', crop: 'center', scale: 2 }} 2x" />
	</div>
	<div class="sc-lander-note">
		This item will ship with your <?=$month?> box. <br />Need to make more changes to your box? Log into your account now.
	</div>
	<div class="sc-lander-button">
		<a href="/account" class="action_button">Login to My Account</a>
	</div>
</div>