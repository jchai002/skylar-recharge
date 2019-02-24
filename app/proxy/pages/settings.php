{% assign portal_page = 'settings' %}
{{ 'sc-portal.scss.css' | asset_url | stylesheet_tag }}
<div class="sc-portal-page sc-portal-{{ portal_page }} sc-portal-container">
	{% include 'sc-member-nav' %}
	<div class="sc-portal-content">
		<div class="sc-portal-innercontainer">
			<div class="sc-portal-title">Order History</div>
			{% include 'sc-order-history' %}
		</div>
	</div>
</div>
{{ 'sc-portal.js' | asset_url | script_tag }}