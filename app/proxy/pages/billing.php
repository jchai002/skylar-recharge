<?php
use \Stripe\Stripe, \Stripe\Token;
global $rc, $db;
$main_sub = sc_get_main_subscription($db, $rc, [
	'shopify_customer_id' => $_REQUEST['c'],
]);
$res = $rc->get('/addresses/'.$main_sub['address_id']);
$address = $res['address'];

$res = $rc->get('/customers/'.$main_sub['customer_id']);
$customer = $res['customer'];

$cc_info = [];
if($customer['processor_type'] == 'stripe'){
	Stripe::setApiKey("sk_test_4eC39HqLyjWDarjtT1zdp7dc");
	$cc_info = Token::retrieve($customer['stripe_customer_token']);
}
?>
{% assign portal_page = 'billing' %}
{{ 'sc-portal.scss.css' | asset_url | stylesheet_tag }}
<div class="sc-portal-page sc-portal-{{ portal_page }} sc-portal-container">
	{% include 'sc-member-nav' %}
	<div class="sc-portal-content">
		<div class="sc-portal-innercontainer">
			<div class="sc-portal-title">Billing Information</div>
			<div class="sc-portal-subtitle">Edit your shipping address or payment method</div>
			<div class="sc-portal-tile" data-id="<?=$address['id']?>">
				<div class="sc-box-title">Shipping Address</div>
				<div class="sc-box-detail"><?=$address['first_name']?> <?=$address['last_name']?></div>
				<div class="sc-box-detail"><?=$address['address1']?></div>
				<?php if(!empty($address['address2'])){ ?>
				<div class="sc-box-detail"><?=$address['address2']?></div>
				<?php } ?>
				<div class="sc-box-detail"><?=$address['city']?>, <?=$address['province']?> <?=$address['zip']?></div>
			</div>
			<div class="sc-box-actions">
				<a href="#" class="sc-edit-address">Edit</a>
			</div>

			<div class="sc-portal-tile">
				<div class="sc-box-title">Payment Method</div>
			<?php if($customer['processor_type'] == 'stripe'){ ?>
				print_r($cc_info);
			<?php } else if($customer['processor_type'] == 'paypal'){ ?>
			<?php } else { ?>
			<?php } ?>
			</div>
		</div>
	</div>
</div>
{{ 'sc-portal.js' | asset_url | script_tag }}