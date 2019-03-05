<?php
global $rc, $db;
$main_sub = sc_get_main_subscription($db, $rc, [
	'shopify_customer_id' => $_REQUEST['c'],
]);
$address = [];
$customer = [];
$cc_info = [];
if(!empty($main_sub)){
	$res = $rc->get('/addresses/'.$main_sub['address_id']);
	$address = $res['address'];

	$res = $rc->get('/customers/'.$main_sub['customer_id']);
	$customer = $res['customer'];

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
}
?>
{% assign portal_page = 'billing' %}
{{ 'sc-portal.scss.css' | asset_url | stylesheet_tag }}
<div class="sc-portal-page sc-portal-{{ portal_page }} sc-portal-container">
	{% include 'sc-member-nav' %}
	<div class="sc-portal-content">
		<div class="sc-portal-innercontainer">
			<div class="sc-portal-title">Billing &amp; Shipping Information</div>
			<div class="sc-portal-subtitle">Edit your shipping address or payment method</div>
			<div class="sc-portal-tile">
				<div class="sc-tile-title">Shipping Address</div>
				<?php if(empty($address)){ ?>
					<div class="sc-tile-detail">No Address Stored</div>
				<?php } else { ?>
					<div class="sc-tile-detail"><?=$address['first_name']?> <?=$address['last_name']?></div>
					<div class="sc-tile-detail"><?=$address['address1']?></div>
					<?php if(!empty($address['address2'])){ ?>
						<div class="sc-tile-detail"><?=$address['address2']?></div>
					<?php } ?>
					<div class="sc-tile-detail"><?=$address['city']?>, <?=$address['province']?> <?=$address['zip']?></div>
				<?php } ?>
			</div>
			<div class="sc-tile-actions">
				<a href="#" class="sc-edit-address" onclick="$('#sc-edit-address').data('mmenu').open(); return false;">Edit</a>
			</div>

			<div class="sc-portal-tile">
				<div class="sc-tile-title">Payment Method</div>
			<?php if(!empty($cc_info)){ ?>
				<div class="sc-tile-detail"><?=$cc_info->brand?>: *<?=$cc_info->last4?> <?=str_pad($cc_info->exp_month, 2, '0', STR_PAD_LEFT)?>/<?=substr($cc_info->exp_year, 2, 4)?></div>
			<?php } else if(empty($customer)){ ?>
				<div class="sc-tile-detail">Card Not Stored</div>
			<?php } else if($customer['processor_type'] == 'paypal'){ ?>
				<div class="sc-tile-detail"><a href="https://paypal.com" target="_blank">Paypal</a></div>
			<?php } else { ?>
				<div class="sc-tile-detail">Card Not Stored</div>
			<?php } ?>
			</div>
			<div class="sc-tile-actions">
				<?php if(!empty($cc_info)){?>
					<a href="#" class="sc-edit-card" onclick="$('#sc-add-card').data('mmenu').open(); return false;">Edit Card</a>
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
						<input id="sc-address-first-name" name="first_name" value="<?=empty($address) ? '' : $address['first_name']?>" />
					</div>
					<div class="sc-input-group">
						<label for="sc-address-last-name">Last Name</label>
						<input id="sc-address-last-name" name="last_name" value="<?=empty($address) ? '' : $address['last_name']?>" />
					</div>
				</div>
				<div class="sc-input-row">
					<div class="sc-input-group">
						<label for="sc-address-address1">Address</label>
						<input id="sc-address-address1" name="address1" value="<?=empty($address) ? '' : $address['address1']?>" />
					</div>
				</div>
				<div class="sc-input-row">
					<div class="sc-input-group">
						<label for="sc-address-address2">Apt, Suite, etc</label>
						<input id="sc-address-address2" name="address2" value="<?=empty($address) ? '' : $address['address2']?>" />
					</div>
					<div class="sc-input-group">
						<label for="sc-address-zip">Zip Code</label>
						<input id="sc-address-zip" name="zip" value="<?=empty($address) ? '' : $address['zip']?>" />
					</div>
				</div>
				<div class="sc-input-row">
					<div class="sc-input-group">
						<label for="sc-address-city">City</label>
						<input id="sc-address-city" name="city" value="<?=empty($address) ? '' : $address['city']?>" />
					</div>
					<div class="sc-input-group">
						<label for="sc-address-state">State</label>
						<input id="sc-address-state" name="province" value="<?=empty($address) ? '' : $address['province']?>" />
					</div>
				</div>
				<div class="sc-input-row">
					<div class="sc-input-group">
						<input type="submit" class="save-button action_button" value="Save Address">
					</div>
				</div>
			</form>
		</div>
	</div>
	<div id="sc-add-card">
		<div>
			<div class="sc-mmenu-header">
				<div class="sc-mmenu-close-icon"><img class="lazyload lazypreload" data-src="{{ 'icon-close.svg' | file_url }}"></div>
				<div class="sc-mmenu-title"><?=empty($cc_info) ? 'Add' : 'Edit'?> Card</div>
			</div>
			<form class="sc-mmenu-form">
				<div class="sc-input-row">
					<div class="sc-input-group">
						<label for="sc-card-number">Card Number</label>
						<div id="sc-card-number"></div>
					</div>
				</div>
				<div class="sc-input-row">
					<div class="sc-input-group">
						<label for="sc-card-expiration">MM/YY</label>
						<div id="sc-card-expiration"></div>
					</div>
					<div class="sc-input-group">
						<label for="sc-card-cvc">CVC</label>
						<div id="sc-card-cvc"></div>
					</div>
				</div>
				<div class="sc-input-row">
					<div class="sc-input-group">
						<input type="submit" class="save-button action_button" value="Save Card">
					</div>
				</div>
			</form>
		</div>
	</div>
</div>
{{ 'sc-portal.js' | asset_url | script_tag }}
<script>
    $(document).ready(function(){
        optional_scripts.onload('mmenu', function(){
            $('#sc-add-card').mmenu({
                offCanvas: { position: 'right', zposition : "front", pageSelector: "#content_wrapper" },
                classes: "mm-white",
            });
            $('#sc-edit-address').mmenu({
                offCanvas: { position: 'right', zposition : "front", pageSelector: "#content_wrapper" },
                classes: "mm-white",
            });

            $('#sc-edit-address form').submit(function(e){
                e.preventDefault();
                ScentClub.update_address($(this).serializeJSON());
			});
		});
        optional_scripts.onload('stripe', function(){
            var elementStyle = {
                base: {
                    fontFamily: 'sofia-pro,Helvetica,Arial,sans-serif',
                    fontSize: '17px',
                    fontWeight: '300',
                    padding: '14px',
                }
			};
            window.elements = window.stripe.elements({});
            ScentClub.cardNumber = window.elements.create('cardNumber', {
                'style': elementStyle,
			});
            ScentClub.cardNumber.mount('#sc-card-number');
            ScentClub.cardExpiry = window.elements.create('cardExpiry', {
                'style': elementStyle,
			});
            ScentClub.cardExpiry.mount('#sc-card-expiration');
            ScentClub.cardExpiry = window.elements.create('cardCvc', {
                'style': elementStyle,
			});
            ScentClub.cardExpiry.mount('#sc-card-cvc');
            $('#sc-add-card form').submit(function(e){
                e.preventDefault();
                var tokenRes = window.stripe.createToken(ScentClub.cardNumber);
                tokenRes.then(function(response){
                    console.log(response);
                    if(response.token){
                        ScentClub.assign_token(response.token.id);
					} else {
                        if(response.error.message){
                            alert(response.error.message);
						} else {
                            alert(response.error);
						}
					}
				});
			});
        });
	});
</script>