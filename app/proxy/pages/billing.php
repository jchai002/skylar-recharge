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
				<a href="#" class="sc-edit-address" onclick="$('#sc-edit-address').data('mmenu').open(); return false;">Edit</a>
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
					<a href="#" class="sc-edit-card" onclick="$('#sc-edit-card').data('mmenu').open(); return false;">Edit Card</a>
				<?php } else { ?>
					<a href="#" class="sc-add-card" onclick="$('#sc-add-card').data('mmenu').open(); return false;">Add Card</a>
				<?php } ?>
			</div>
		</div>
	</div>
</div>
<div class="hidden">
	<div id="sc-edit-address">
		<div>
			<div class="sc-mmenu-header">
				<div class="sc-mmenu-close-icon"><img class="lazyload lazypreload" data-src="{{ 'icon-close.svg' | file_url }}"></div>
				<div class="sc-mmenu-title">Edit Address</div>
			</div>
			<form class="sc-mmenu-form">
				<div class="sc-input-row">
					<div class="sc-input-group">
						<label for="sc-address-first-name">First Name</label>
						<input id="sc-address-first-name" value="<?=$address['first_name']?>" />
					</div>
					<div class="sc-input-group">
						<label for="sc-address-last-name">Last Name</label>
						<input id="sc-address-last-name" value="<?=$address['last_name']?>" />
					</div>
				</div>
				<div class="sc-input-row">
					<div class="sc-input-group">
						<label for="sc-address-address1">Address</label>
						<input id="sc-address-address1" value="<?=$address['address1']?>" />
					</div>
				</div>
				<div class="sc-input-row">
					<div class="sc-input-group">
						<label for="sc-address-address2">Apt, Suite, etc</label>
						<input id="sc-address-address2" value="<?=$address['address2']?>" />
					</div>
					<div class="sc-input-group">
						<label for="sc-address-zip">Zip Code</label>
						<input id="sc-address-zip" value="<?=$address['zip']?>" />
					</div>
				</div>
				<div class="sc-input-row">
					<div class="sc-input-group">
						<label for="sc-address-city">City</label>
						<input id="sc-address-city" value="<?=$address['city']?>" />
					</div>
					<div class="sc-input-group">
						<label for="sc-address-state">State</label>
						<input id="sc-address-state" value="<?=$address['province']?>" />
					</div>
				</div>
				<div class="sc-input-row">
					<div class="sc-input-group">
						<div class="save-button action_button">Save Address</div>
					</div>
				</div>
			</form>
		</div>
	</div>
	<div id="sc-add-card">
		<div>
			<div class="sc-mmenu-header">
				<div class="sc-mmenu-close-icon"><img class="lazyload lazypreload" data-src="{{ 'icon-close.svg' | file_url }}"></div>
				<div class="sc-mmenu-title">Add Card</div>
			</div>
			<form class="sc-mmenu-form">
				<div class="sc-input-row">
					<div class="sc-input-group">
						<label for="sc-card-number">Card Number</label>
						<input id="sc-card-number" />
					</div>
				</div>
				<div class="sc-input-row">
					<div class="sc-input-group">
						<label for="sc-card-expiration">MM/YY</label>
						<input id="sc-card-expiration" />
					</div>
					<div class="sc-input-group">
						<label for="sc-card-ccv">CCV</label>
						<input id="sc-card-ccv" />
					</div>
				</div>
				<div class="sc-input-row">
					<div class="sc-input-group">
						<div class="save-button action_button">Save Address</div>
					</div>
				</div>
			</form>
		</div>
	</div>
	<div id="sc-edit-card">
		<div>
			<div class="sc-mmenu-header">
				<div class="sc-mmenu-close-icon"><img class="lazyload lazypreload" data-src="{{ 'icon-close.svg' | file_url }}"></div>
				<div class="sc-mmenu-title">Edit Card</div>
			</div>
			<form class="sc-mmenu-form">
				<div class="sc-input-row">
					<div class="sc-input-group">
						<label for="sc-card-number">Card Number</label>
						<input id="sc-card-number" />
					</div>
				</div>
				<div class="sc-input-row">
					<div class="sc-input-group">
						<label for="sc-card-expiration">MM/YY</label>
						<input id="sc-card-expiration" />
					</div>
					<div class="sc-input-group">
						<label for="sc-card-ccv">CCV</label>
						<input id="sc-card-ccv" />
					</div>
				</div>
				<div class="sc-input-row">
					<div class="sc-input-group">
						<div class="save-button action_button">Save Address</div>
					</div>
				</div>
			</form>
		</div>
	</div>
</div>
{{ 'sc-portal.js' | asset_url | script_tag }}
<script>
    ScentClub.address_id = <?=$address['id']?>;
    $(document).ready(function(){
        optional_scripts.onload('mmenu', function(){
            $('#sc-edit-card').mmenu({
                offCanvas: { position: 'right', zposition : "front", pageSelector: "#content_wrapper" },
                classes: "mm-white",
            });
            $('#sc-add-card').mmenu({
                offCanvas: { position: 'right', zposition : "front", pageSelector: "#content_wrapper" },
                classes: "mm-white",
            });
            $('#sc-edit-address').mmenu({
                offCanvas: { position: 'right', zposition : "front", pageSelector: "#content_wrapper" },
                classes: "mm-white",
            });
		});
        optional_scripts.onload('stripe', function(){
            window.add_card_elements = window.stripe.elements();
        });
	});
</script>