<?php
global $rc, $db;
$main_sub = sc_get_main_subscription($db, $rc, [
	'shopify_customer_id' => $_REQUEST['c'],
]);

$sc = new ShopifyClient();
$customer = $sc->get('/admin/customers/'.intval($_REQUEST['c']).'.json');

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
			<div class="sc-portal-minisection">
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
{{ 'sc-portal.js' | asset_url | script_tag }}
<script>
    $(document).ready(function(){
        optional_scripts.onload('mmenu', function(){
            $('#sc-edit-email').mmenu({
                offCanvas: { position: 'right', zposition : "front", pageSelector: "#content_wrapper" },
                classes: "mm-white",
            });
            $('#sc-edit-password').mmenu({
                offCanvas: { position: 'right', zposition : "front", pageSelector: "#content_wrapper" },
                classes: "mm-white",
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