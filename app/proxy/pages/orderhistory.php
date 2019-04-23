<?php
global $rc;
sc_conditional_billing($rc, $_REQUEST['c']);
?>
{% assign portal_page = 'orderhistory' %}
{{ 'sc-portal.scss.css' | asset_url | stylesheet_tag }}
<div class="sc-portal-page sc-portal-{{ portal_page }} sc-portal-container">
	{% include 'sc-member-nav' %}
	<div class="sc-portal-content">
		<div class="sc-portal-innercontainer">
			<div class="sc-portal-title">Order History</div>
			<div class="sc-portal-subtitle">Here you have the ability to view your past orders</div>
			{% if is_alias %}Aliased accounts do not currently support order history. Please view the customer's orders from <a href="/admin/customers/{{customer_id}}" target="_blank" style="text-decoration: underline;">the Shopify admin</a>.{% else %}{% include 'sc-order-history' %}{% endif %}
		</div>
	</div>
</div>
{{ 'sc-portal.js' | asset_url | script_tag }}