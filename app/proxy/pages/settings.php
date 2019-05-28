<?php
global $rc, $db;
$main_sub = sc_get_main_subscription($db, $rc, [
	'shopify_customer_id' => $_REQUEST['c'],
]);

$sc = new ShopifyClient();
$customer = $sc->get('/admin/customers/'.intval($_REQUEST['c']).'.json');

sc_conditional_billing($rc, $_REQUEST['c']);
?>
{% assign portal_page = 'settings' %}
{{ 'sc-portal.scss.css' | asset_url | stylesheet_tag }}
<div class="sc-portal-page sc-portal-{{ portal_page }} sc-portal-container">
	{% include 'sc-member-nav' %}
	<div class="sc-portal-content">
		<div class="sc-portal-innercontainer">
			<div class="sc-portal-title">Settings Preferences</div>
			<div class="sc-portal-subtitle">Edit your email or password</div>
			<div class="sc-portal-tile">
				<div class="sc-tile-title">Email address</div>
				<div class="sc-tile-detail"><?=$customer['email']?></div>
			</div>
			<div class="sc-tile-actions">
				<a href="#" class="sc-edit-email" onclick="$('#sc-edit-email').data('mmenu').open(); return false;">Edit</a>
			</div>

			<div class="sc-portal-tile">
				<div class="sc-tile-title">Password</div>
				<div class="sc-tile-detail">**********</div>
			</div>
			<div class="sc-tile-actions">
				<a href="#" class="sc-edit-password" onclick="$('#sc-edit-password').data('mmenu').open(); return false;">Change Password</a>
			</div>
			<?php if(!empty($main_sub)){ ?>
			<div class="sc-portal-minisection sc-tile-actions">
				<a href="#cancel_sub" class="sc-cancel-link">Cancel Subscription</a>
			</div>
			<?php } ?>
		</div>
	</div>
</div>
<div class="hidden">
	<div id="sc-edit-email">
		<div>
			<div class="sc-mmenu-header">
				<div class="sc-mmenu-close-icon"><img class="lazyload lazypreload" data-src="{{ 'icon-close.svg' | file_url }}"></div>
				<div class="sc-mmenu-title">Edit Email</div>
			</div>
			<form class="sc-mmenu-form">
				<div class="sc-input-row">
					<div class="sc-input-group">
						<label for="sc-change-email">Email</label>
						<input id="sc-change-email" name="email" type="email" value="<?=$customer['email']?>" />
					</div>
				</div>
				<div class="sc-input-row">
					<div class="sc-input-group">
						<input type="submit" class="save-button action_button" value="Save Email">
					</div>
				</div>
			</form>
		</div>
	</div>
	<div id="sc-edit-password">
		<div>
			<div class="sc-mmenu-header">
				<div class="sc-mmenu-close-icon"><img class="lazyload lazypreload" data-src="{{ 'icon-close.svg' | file_url }}"></div>
				<div class="sc-mmenu-title">Change Password</div>
			</div>
			<form class="sc-mmenu-form">
				<div class="sc-input-row">
					<div class="sc-input-group">
						<label for="sc-change-password">New Password</label>
						<input id="sc-change-password" name="password" type="password" />
					</div>
				</div>
				<div class="sc-input-row">
					<div class="sc-input-group">
						<label for="sc-change-password-confirm">Confirm New Password</label>
						<input id="sc-change-password-confirm" name="password_confirmation" type="password" />
					</div>
				</div>
				<div class="sc-input-row">
					<div class="sc-input-group">
						<input type="submit" class="save-button action_button" value="Save Password">
					</div>
				</div>
				<div class="sc-input-row">
					<div class="sc-input-group">
						<label for="">You will need to log in again after saving.</label>
					</div>
				</div>
			</form>
		</div>
	</div>
</div>
<div class="hidden">
	<div id="sc-cancel-modal">
		<div class="sc-modal-title">Did you know you can...</div>
		<div class="sc-modal-links">
			<div class="sc-modal-linkbox" onclick="location.href='/tools/skylar/subscriptions?{{ account_query }}&intent=changedate'; return false;">
				<div><img src="{{ 'calendar.svg' | file_url }}" /></div>
				<div class="sc-linkbox-label">Change Shipping Date</div>
				<div><img src="{{ 'sc-link-arrow.svg' | file_url }}" /></div>
			</div>
			<div class="sc-modal-linkbox" onclick="location.href='/tools/skylar/subscriptions?{{ account_query }}&intent=swapscent'; return false;">
				<div><img src="{{ 'swapscent-black.svg' | file_url }}" /></div>
				<div class="sc-linkbox-label">Swap Scents</div>
				<div><img src="{{ 'sc-link-arrow.svg' | file_url }}" /></div>
			</div>
		</div>
		<div class="sc-modal-continue">
			<a href="#" onclick="ScentClub.show_cancel_final(); return false;">Continue To Cancel</a>
		</div>
	</div>
	<div id="sc-cancel-confirm-modal" class="sc-confirm-modal">
		<div>
			<div class="sc-modal-title">Why would you like to cancel your subscription?</div>
			<form class="skip-reason-form">
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
					<a class="action_button skip-confirm-button disabled" onclick="if($(this).hasClass('disabled')){return false;} $(this).addClass('disabled'); ScentClub.cancel_main_sub(ScentClub.get_skip_reason()); return false;">Cancel Subscription</a>
					<a class="action_button inverted" onclick="$.featherlight.close(); return false;">Go Back</a>
				</div>
			</form>
		</div>
	</div>
		<div class="sc-skip-image sc-desktop">
			<img src="{{ all_products['scent-club'].featured_image | img_url: '500x' }}" />
		</div>
		<div>
			<div class="sc-modal-title">Are you sure you want to cancel your Scent Club subscription?</div>
			<div class="sc-modal-subtitle"></div>
			<div class="sc-skip-image sc-mobile sc-tablet">
				<img src="{{ all_products['scent-club'].featured_image | img_url: 'x300' }}" />
			</div>
			<div class="sc-skip-options">
				<a class="action_button warning" onclick="$(this).addClass('disabled'); ScentClub.cancel_main_sub(); return false;">Yes, Cancel My Subscription</a>
				<a class="action_button inverted" onclick="$.featherlight.close(); return false;">No Thanks, Go Back</a>
			</div>
		</div>
	</div>
</div>
{{ 'sc-portal.js' | asset_url | script_tag }}
<script>
    $(document).ready(function(){

        $('.skip-reason-form input, .skip-reason-form textarea').change(function(){
            if(!ScentClub.get_skip_reason()){
                $('.skip-confirm-button').addClass('disabled');
            } else {
                $('.skip-confirm-button').removeClass('disabled');
            }
        });
        $('.skip-reason-form textarea').on('keyup keydown', function(){
            $('.skip-reason-form input[value=other]').prop('checked', true);
            if(!ScentClub.get_skip_reason()){
                $('.skip-confirm-button').addClass('disabled');
            } else {
                $('.skip-confirm-button').removeClass('disabled');
            }
        });

        optional_scripts.onload('mmenu', function(){
            $('#sc-edit-email').mmenu({
                offCanvas: { position: 'right', zposition : "front", pageSelector: "#content_wrapper" },
                classes: "mm-white",
                keyboardNavigation: {
                    enable: true,
                }
            });
            $('#sc-edit-password').mmenu({
                offCanvas: { position: 'right', zposition : "front", pageSelector: "#content_wrapper" },
                classes: "mm-white",
                keyboardNavigation: {
                    enable: true,
                }
            });

            $('#sc-edit-email form').submit(function(e){
                e.preventDefault();
                var data = $(this).serializeJSON();
                data.c = Shopify.queryParams.c;
                $.ajax({
                    type: 'post',
                    url: '/tools/skylar/settings/update-email',
                    data: data,
                    success: function(data){
                        if(data.success){
                            location.reload();
                        } else {
                            alert(data.error);
                        }
                    }
                });
            });

            $('#sc-edit-password form').submit(function(e){
                e.preventDefault();
                var data = $(this).serializeJSON();
                data.c = Shopify.queryParams.c;
                $.ajax({
					type: 'post',
                    url: '/tools/skylar/settings/update-password',
                    data: data,
                    success: function(data){
                        if(data.success){
                            location.reload();
                        } else {
                            alert(data.error);
                        }
                    }
                });
            });
        });
    });
</script>