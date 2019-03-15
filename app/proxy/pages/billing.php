<?php
global $rc, $db;
$main_sub = sc_get_main_subscription($db, $rc, [
	'shopify_customer_id' => $_REQUEST['c'],
]);
$customer = [];
$res = $rc->get('/customers/', [
	'shopify_customer_id' => $_REQUEST['c'],
]);
if(!empty($res['customers'])){
	$customer = $res['customers'][0];
}

$address = [];
$cc_info = [];
if(!empty($main_sub)){
	$res = $rc->get('/addresses/'.$main_sub['address_id']);
	$address = $res['address'];

	try {
		if($customer['processor_type'] == 'stripe'){
			\Stripe\Stripe::setApiKey($_ENV['STRIPE_API_KEY']);
			$stripe_customer = \Stripe\Customer::retrieve($customer['stripe_customer_token']);
			if(!empty($stripe_customer->default_source)){
				foreach($stripe_customer->sources->data as $source){
					if($source->id == $stripe_customer->default_source){
						$cc_info = $source;
						break;
					}
				}
			}
		}
	} catch(\Stripe\Error\InvalidRequest $e){
		$cc_info = [];
	}
}
sc_conditional_billing($rc, $_REQUEST['c']);
$countries = [
	'United States',
	'Australia',
	'Canada',
	'France',
	'United Kingdom',
];
$states = [
	'AL' => 'Alabama',
	'AK' => 'Alaska',
	'AZ' => 'Arizona',
	'AR' => 'Arkansas',
	'CA' => 'California',
	'CO' => 'Colorado',
	'CT' => 'Connecticut',
	'DE' => 'Delaware',
	'DC' => 'District of Columbia',
	'FL' => 'Florida',
	'GA' => 'Georgia',
	'HI' => 'Hawaii',
	'ID' => 'Idaho',
	'IL' => 'Illinois',
	'IN' => 'Indiana',
	'IA' => 'Iowa',
	'KS' => 'Kansas',
	'KY' => 'Kentucky',
	'LA' => 'Louisiana',
	'ME' => 'Maine',
	'MD' => 'Maryland',
	'MA' => 'Massachusetts',
	'MI' => 'Michigan',
	'MN' => 'Minnesota',
	'MS' => 'Mississippi',
	'MO' => 'Missouri',
	'MT' => 'Montana',
	'NE' => 'Nebraska',
	'NV' => 'Nevada',
	'NH' => 'New Hampshire',
	'NJ' => 'New Jersey',
	'NM' => 'New Mexico',
	'NY' => 'New York',
	'NC' => 'North Carolina',
	'ND' => 'North Dakota',
	'OH' => 'Ohio',
	'OK' => 'Oklahoma',
	'OR' => 'Oregon',
	'PA' => 'Pennsylvania',
	'RI' => 'Rhode Island',
	'SC' => 'South Carolina',
	'SD' => 'South Dakota',
	'TN' => 'Tennessee',
	'TX' => 'Texas',
	'UT' => 'Utah',
	'VT' => 'Vermont',
	'VA' => 'Virginia',
	'WA' => 'Washington',
	'WV' => 'West Virginia',
	'WI' => 'Wisconsin',
	'WY' => 'Wyoming',
];
?>
<!--
<?php
print_r($customer);
?>
-->
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
			<br />
			<div class="sc-tile-title">Billing Address</div>
			<?php if(empty($customer['billing_address1'])){ ?>
				<div class="sc-tile-detail">No Address Stored</div>
			<?php } else { ?>
				<div class="sc-tile-detail"><?=$customer['first_name']?> <?=$customer['last_name']?></div>
				<div class="sc-tile-detail"><?=$customer['billing_address1']?></div>
				<?php if(!empty($customer['billing_address2'])){ ?>
					<div class="sc-tile-detail"><?=$customer['billing_address2']?></div>
				<?php } ?>
				<div class="sc-tile-detail"><?=$customer['billing_city']?>, <?=$customer['billing_province']?> <?=$customer['billing_zip']?></div>
			<?php } ?>
			</div>
			<div class="sc-tile-actions">
				<?php if(!empty($cc_info)){?>
					<a href="#" class="sc-edit-card" onclick="$('#sc-add-card').data('mmenu').open(); return false;">Edit</a>
				<?php } else { ?>
					<a href="#" class="sc-add-card" onclick="$('#sc-add-card').data('mmenu').open(); return false;">Add</a>
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
						<input required id="sc-address-first-name" name="first_name" value="<?=empty($address) ? '' : $address['first_name']?>" />
					</div>
					<div class="sc-input-group">
						<label for="sc-address-last-name">Last Name</label>
						<input required id="sc-address-last-name" name="last_name" value="<?=empty($address) ? '' : $address['last_name']?>" />
					</div>
				</div>
				<div class="sc-input-row">
					<div class="sc-input-group">
						<label for="sc-address-address1">Address</label>
						<input required id="sc-address-address1" name="address1" value="<?=empty($address) ? '' : $address['address1']?>" />
					</div>
				</div>
				<div class="sc-input-row">
					<div class="sc-input-group">
						<label for="sc-address-address2">Apt, Suite, etc</label>
						<input id="sc-address-address2" name="address2" value="<?=empty($address) ? '' : $address['address2']?>" />
					</div>
					<div class="sc-input-group">
						<label for="sc-address-phone">Phone</label>
						<input id="sc-address-phone" name="address_phone" value="<?=empty($address) ? '' : $address['phone']?>" />
					</div>
				</div>
				<div class="sc-input-row">
					<div class="sc-input-group">
						<label for="sc-address-country">Country</label>
						<select id="sc-address-country" name="country">
							<?php foreach($countries as $country){ ?>
								<option value="<?=$country?>"<?= !empty($address) && $address['country'] == $country ? ' selected' : ''?>><?=$country?></option>
							<?php } ?>
						</select>
					</div>
					<div class="sc-input-group">
						<label for="sc-address-zip">Zip Code</label>
						<input required id="sc-address-zip" name="zip" value="<?=empty($address) ? '' : $address['zip']?>" />
					</div>
				</div>
				<div class="sc-input-row">
					<div class="sc-input-group">
						<label for="sc-address-city">City</label>
						<input required id="sc-address-city" name="city" value="<?=empty($address) ? '' : $address['city']?>" />
					</div>
					<div class="sc-input-group" id="sc-address-states-us">
						<label for="sc-address-state">State</label>
						<select id="sc-address-state" name="state">
							<?php foreach($states as $state){ ?>
								<option value="<?=$state?>"<?= !empty($address) && $address['province'] == $state ? ' selected' : ''?>><?=$state?></option>
							<?php } ?>
						</select>
					</div>
					<div class="sc-input-group" id="sc-address-states-other" style="display: none;">
						<label for="sc-address-province">Province</label>
						<input required id="sc-address-province" name="province" value="<?=empty($address) ? '' : $address['province']?>" />
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
				<div class="sc-input-error"></div>
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
						<label for="sc-billing-first-name">First Name</label>
						<input required id="sc-billing-first-name" name="billing_first_name" value="<?=empty($customer['first_name']) ? '' : $customer['first_name']?>" />
					</div>
					<div class="sc-input-group">
						<label for="sc-billing-last-name">Last Name</label>
						<input required id="sc-billing-last-name" name="billing_last_name" value="<?=empty($customer['last_name']) ? '' : $customer['last_name']?>" />
					</div>
				</div>
				<div class="sc-input-row">
					<div class="sc-input-group">
						<label for="sc-billing-address1">Address</label>
						<input required id="sc-billing-address1" name="billing_address1" value="<?=empty($customer['billing_address1']) ? '' : $customer['billing_address1']?>" />
					</div>
				</div>
				<div class="sc-input-row">
					<div class="sc-input-group">
						<label for="sc-billing-address2">Apt, Suite, etc</label>
						<input id="sc-billing-address2" name="billing_address2" value="<?=empty($customer['billing_address2']) ? '' : $customer['billing_address2']?>" />
					</div>
					<div class="sc-input-group">
						<label for="sc-billing-phone">Phone</label>
						<input id="sc-billing-phone" name="billing_phone" value="<?=empty($customer['billing_phone']) ? '' : $customer['billing_phone']?>" />
					</div>
				</div>
				<div class="sc-input-row">
					<div class="sc-input-group">
						<label for="sc-billing-city">City</label>
						<input required id="sc-billing-city" name="billing_city" value="<?=empty($customer['billing_city']) ? '' : $customer['billing_city']?>" />
					</div>
					<div class="sc-input-group">
						<label for="sc-billing-state">State/Province</label>
						<input required id="sc-billing-state" name="billing_province" value="<?=empty($customer['billing_province']) ? '' : $customer['billing_province']?>" />
					</div>
				</div>
				<div class="sc-input-row">
					<div class="sc-input-group">
						<label for="sc-billing-zip">Zip Code</label>
						<input required id="sc-billing-zip" name="billing_zip" value="<?=empty($customer['billing_zip']) ? '' : $customer['billing_zip']?>" />
					</div>
					<div class="sc-input-group">
						<label for="sc-billing-country">Country</label>
						<input required id="sc-billing-country" name="billing_country" value="<?=empty($customer['billing_country']) ? 'United States' : $customer['billing_country']?>" />
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
        $('#sc-address-country').change(function(){
            if($(this).val() == 'United States'){
                $('#sc-address-states-us').show();
                $('#sc-address-states-other').hide();
                $('#sc-address-province').prop('required', false);
			} else {
                $('#sc-address-states-us').hide();
                $('#sc-address-states-other').show();
                $('#sc-address-province').prop('required', true);
			}
		});
        optional_scripts.onload('mmenu', function(){
            $('#sc-add-card').mmenu({
                offCanvas: { position: 'right', zposition : "front", pageSelector: "#content_wrapper" },
                classes: "mm-white",
                keyboardNavigation: {
                    enable: true,
                }
            });
            $('#sc-edit-address').mmenu({
                offCanvas: { position: 'right', zposition : "front", pageSelector: "#content_wrapper" },
                classes: "mm-white",
                keyboardNavigation: {
                    enable: true,
                }
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
                disabled: false,
			});
            ScentClub.cardNumber.mount('#sc-card-number');
            ScentClub.cardExpiry = window.elements.create('cardExpiry', {
                'style': elementStyle,
                disabled: false,
			});
            ScentClub.cardExpiry.mount('#sc-card-expiration');
            ScentClub.cardCvc = window.elements.create('cardCvc', {
                'style': elementStyle,
				disabled: false,
			});
            ScentClub.cardCvc.mount('#sc-card-cvc');
            $('#sc-add-card form').submit(function(e){
                $('.loader').fadeIn();
                $('.sc-input-error').html('');
                e.preventDefault();
                var tokenRes = window.stripe.createToken(ScentClub.cardNumber);
                // window.setInterval(function(){
                    // window.ScentClub.cardNumber.update({disabled: false});
                    // window.ScentClub.cardExpiry.update({disabled: false});
                    // window.ScentClub.cardCvc.update({disabled: false});
                // }, 1000);
                tokenRes.then(function(response){
                    console.log(response);
                    if(response.token){
                        ScentClub.assign_token(response.token.id);
					} else {
                        window.ScentClub.cardNumber.update({disabled: false});
                        window.ScentClub.cardExpiry.update({disabled: false});
                        window.ScentClub.cardCvc.update({disabled: false});
                        // ScentClub.cardNumber.unmount().mount('#sc-card-number');
                        // ScentClub.cardExpiry.unmount().mount('#sc-card-expiration');
                        // ScentClub.cardCvc.unmount().mount('#sc-card-cvc');
                        $('.loader').fadeOut();
                        if(response.error.message){
                            $('.sc-input-error').html(response.error.message);
						} else {
                            $('.sc-input-error').html(response.error);
						}
					}
				});
			});
        });
	});
</script>