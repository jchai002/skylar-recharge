{% assign portal_page = 'orderhistory' %}
{{ 'sc-portal.scss.css' | asset_url | stylesheet_tag }}
<div class="sc-portal-page sc-portal-{{ portal_page }} sc-portal-container">
	{% include 'sc-member-nav' %}

	<div class="sc-portal-content">
		<div class="sc-portal-innercontainer">
			{% include 'sc-order-history' %}
		</div>
	</div>
</div>