<?php
header('Content-Type: application/liquid');
?>
{% assign scent_club_product = all_products['scent-club'] %}
{{ 'blackdiamond.css' | asset_url | stylesheet_tag }}
<style>
	.sc-script {
		font-family: "BlackDiamond", "Brush Script MT", "Brush Script Std", cursive;
	}
	img {
		display: inline-block;
	}
	.action_button.inverted {
		background: #FFF;
		border: 1px solid #000;
		color: #000;
	}
	.sc-section-bg {
		background-color: #f8f3f1;
	}
</style>
<div class="sc-members-container">
	<div class="sc-members-hero">
		<div class="sc-hero-title">Hi Becky, Welcome Back</div>
		<div class="sc-hero-subtitle">Shop exclusive Skylar releases with your Monthly Scent Club membership</div>
		<div class="sc-hero-actions">
			<div class="action_button">Customize My Box</div>
			<div class="action_button inverted">Manage My Box</div>
		</div>
	</div>
	<div class="sc-members-section">
		<div class="sc-section-title">We Recommend for You...</div>
		<div class="sc-section-subtitle">
			<a href="#TODO">shop all products</a>
		</div>
		<div class="sc-recommended-products">
			<div class="sc-recommended-product">
				{% assign product = all_products['arrow'] %}
				{% include 'product-thumbnail-flex' %}
			</div>
		</div>
	</div>
	<div class="sc-members-section sc-section-bg">

	</div>
	<div class="sc-members-section">

	</div>
</div>
<script></script>