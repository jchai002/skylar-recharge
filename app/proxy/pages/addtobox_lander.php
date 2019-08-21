<?php
global $db, $sc, $rc;

// Actually add to box
$variant = get_variant($db, $_REQUEST['v']);
$product = get_product($db, $variant['product_id']);
$subscription_price = round($variant*.9);

?>

{% assign portal_page = 'lander-addtobox' %}
{{ 'sc-portal.scss.css' | asset_url | stylesheet_tag }}
<div class="sc-portal-page sc-portal-{{ portal_page }} sc-portal-container sc-portal-lander">
	<div class="sc-lander-title">You added <?=$product['title']?> to your Skylar Box</div>
	<div class="sc-lander-price">Total: <span class="was_price"><?=$variant['price']?></span> <span class="price"><?=$subscription_price?></span> <span class="sc-lander-savings">*You save 10%!</span></div>
	<div class="sc-lander-image">
		<img class="lazyload" data-srcset="{{ all_products[''].featured_image | img_url: '280x' }} 1x, {{ all_products[''].featured_image | img_url: '560x' }} 2x" />
	</div>
	<div class="sc-lander-note">
		This item will ship with your September box. <br />Need to make more changes to your box? Log into your account now.
	</div>
	<div class="sc-lander-button">
		<a href="/account" class="action_button">Login to My Account</a>
	</div>
</div>