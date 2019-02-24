<?php
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
	\Stripe\Stripe::setApiKey($_ENV['STRIPE_API_KEY']);
	$customer = \Stripe\Customer::retrieve($customer['stripe_customer_token']);
	if(!empty($customer->default_source)){
		foreach($customer->sources->data as $source){
			if($source->id == $customer->default_source){
				$cc_info = $source;
				break;
			}
		}
	}
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
				<div class="sc-tile-title">Shipping Address</div>
				<div class="sc-tile-detail"><?=$address['first_name']?> <?=$address['last_name']?></div>
				<div class="sc-tile-detail"><?=$address['address1']?></div>
				<?php if(!empty($address['address2'])){ ?>
				<div class="sc-tile-detail"><?=$address['address2']?></div>
				<?php } ?>
				<div class="sc-tile-detail"><?=$address['city']?>, <?=$address['province']?> <?=$address['zip']?></div>
			</div>
			<div class="sc-tile-actions">
				<a href="#" class="sc-edit-address">Edit</a>
			</div>

			<div class="sc-portal-tile">
				<div class="sc-tile-title">Payment Method</div>
			<?php if(!empty($cc_info)){ ?>
				<?=$cc_info->brand?>: *<?=$cc_info->last4?> <?=$cc_info->exp_month?>/<?=$cc_info->exp_year?>
			<?php } else if($customer['processor_type'] == 'paypal'){ ?>
				<a href="https://paypal.com" target="_blank">Paypal</a>
			<?php } else { ?>
				Card Not Stored
			<?php } ?>
			</div>
			<div class="sc-tile-actions">
				<?php if(!empty($cc_info)){?>
					<a href="#" class="sc-edit-card">Edit Card</a>
				<?php } else { ?>
					<a href="#" class="sc-add-card">Add Card</a>
				<?php } ?>
			</div>
		</div>
	</div>
</div>
{{ 'sc-portal.js' | asset_url | script_tag }}