<?php
header('Content-Type: application/liquid');
$upcoming_box = [
	'items' => [
		[
			'scent_club_product' => true,
			'handle' => 'scent-club-2019-march',
			'price' => 2500,
			'price_formatted' => '$25',
			'order_interval_frequency' => 1,
			'order_interval_unit' => 'month',
			'next_charge_scheduled_at' => strtotime('next month day 4'),
		],
	],
	'discounts' => [[
		'title' => 'Test',
		'value' => '500',
		'value_formatted' => '$5',
	]],
	'total' => '2000',
	'total_formatted' => '$20',
];
$recommended_products = [
	['handle' => 'arrow'],
	['handle' => 'capri'],
	['handle' => 'coral'],
	['handle' => 'isle'],
	['handle' => 'meadow'],
	['handle' => 'willow'],
];
?>
{{ 'sc-portal.scss' | asset_url | stylesheet_tag }}
<div class="sc-portal-page">
	{% include 'sc-member-nav' %}
	<div class="sc-portal-content sc-portal-container">
		<div class="sc-portal-title">
			Your Upcoming Box
		</div>
		<div class="sc-portal-nextbox">
			<?php foreach($upcoming_box['items'] as $item){ ?>
				{% assign box_product = all_products['<?=$item['handle']?>'] %}
				<div class="sc-box-item">
					<div class="sc-item-summary">
						<div class="sc-item-image">
							<img class="lazyload" data-srcset="{{ box_product.images.first | img_url: 100x100 }} 1x, {{ box_product.images.first | img_url: 200x200 }} 2x" />
						</div>
						<div>
							<?php if($item['scent_club_product']){ ?>
								<div class="sc-item-title">Monthly Scent Club</div>
								<div class="sc-item-subtitle">{{ box_product.variants.first.title }}</div>
								<div class="sc-item-link"><a href="/products/{{ box_product.handle }}">Explore This Month's Scent</a></div>
							<?php } else { ?>
								<div class="sc-item-title">{{ box_product.title }}</div>
								<div class="sc-item-subtitle">{{ box_product.variants.first.title }}</div>
							<?php } ?>
						</div>
					</div>
					<div class="sc-item-details">
						<div>
							<div class="sc-item-detail-label">Total</div>
							<div class="sc-item-detail-value"><?=$item['price_formatted']?></div>
						</div>
						<div>
							<div class="sc-item-detail-label">Delivery</div>
							<div class="sc-item-detail-value">
								<?php if($item['order_interval_frequency'] == '1'){ ?>
									Every <?=$item['order_interval_unit']?>
								<?php } else { ?>
									Every <?=$item['order_interval_frequency']?> <?=$item['order_interval_unit']?>s
								<?php } ?>
							</div>
						</div>
						<div>
							<div class="sc-item-detail-label">Next Charge</div>
							<div class="sc-item-detail-value"><?=date('F j, Y', $item['next_charge_scheduled_at'])?></div>
						</div>
					</div>
				</div>
			<?php } ?>
			<div class="sc-box-discounts">
				<?php foreach($upcoming_box['discounts'] as $discount){ ?>
					<div class="sc-box-discount">
						<div class="sc-discount-title"><?=$discount['title']?>:</div>
						<div class="sc-discount-value"><?=$discount['value_formatted']?></div>
					</div>
				<?php } ?>
				<div class="sc-discount-link">Got a promo code?</div>
			</div>
			<div class="sc-box-total">
				Grand Total: <?=$upcoming_box['total_formatted']?>
			</div>
		</div>
		<div class="sc-section-menu">
			<a href="#recommendations">Your Profile Recommendations</a>
			<a href="#layering">Layering</a>
			<a href="#best-sellers">Best Sellers</a>
			<a href="#essentials">The Essentials</a>
		</div>
		<div class="sc-product-section">
			<div class="sc-section-title">Recommendations based on your profile</div>
			<div class="sc-product-carousel">
				<?php foreach($recommended_products as $product){ ?>
					{% assign box_product = all_products['<?=$product['handle']?>'] %}
					{% assign subscription_price = box_product.price_min | times: 9 | divided_by: 10 %}
					{% if box_product.compare_at_price != blank %}
					{% assign compare_at_price = box_product.compare_at_price %}
					{% else %}
					{% assign compare_at_price = box_product.price_max %}
					{% endif %}
					<div class="sc-product-tile">
						<div class="sc-product-image"><img class="lazyload" data-srcset="{{ box_product.images.first | img_url: 240x240 }} 1x, {{ box_product.images.first | img_url: 480x480 }} 2x" /></div>
					</div>
					<div class="sc-product-title">{{ box_product.title }}</div>
					{% if product.metafields.tag_p_grid.text != blank %}<div class="sc-product-subtitle">{{ product.metafields.tag_p_grid.text | replace : ", ", " ‧ " }}</div>{% endif %}
					<div class="sc-price-line">
						<span class="savings-price">{{ box_product.price | money_without_trailing_zeros }}</span>
						<span class="main-price">{{ compare_at_price | money }}</span>
					</div>
				<?php } ?>
			</div>
		</div>
	</div>
</div>