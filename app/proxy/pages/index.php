<?php
header('Content-Type: application/liquid');
?>
{{ 'sc-portal.scss' | asset_url | stylesheet_tag }}
<div class="sc-portal-page">
	{% include 'sc-member-nav' %}
	<div class="sc-portal-content">
		<div class="sc-portal-title">
			Your Upcoming Box
		</div>
	</div>
</div>