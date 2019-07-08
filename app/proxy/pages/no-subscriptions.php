<?php
global $db, $sc, $rc;
sc_conditional_billing($rc, $_REQUEST['c']);
?>

{% assign portal_page = 'my_box' %}
{{ 'sc-portal.scss.css' | asset_url | stylesheet_tag }}

<div class="sc-portal-page sc-portal-{{ portal_page }} sc-portal-container">
	{% include 'sc-member-nav' %}
	<div class="sc-portal-content">
		<div class="sc-portal-innercontainer">
			<div class="sc-portal-title">You have no upcoming shipments.</div>
			<div>
				<a href="/pages/scent-club">Check out our Scent Club to get a new scent each month!</a>
			</div>
		</div>
	</div>
</div>
{{ 'sc-portal.js' | asset_url | script_tag }}
