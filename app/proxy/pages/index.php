<?php
header('Content-Type: application/liquid');
$upcoming_box = [
	'scent_club_product' => true,
	'items' => [
		'handle' => 'scent-club-2019-march',
		'price' => 2500,
		'price_formatted' => '$25',
		'order_interval_frequency' => 1,
		'order_interval_unit' => 'month',
		'next_charge_scheduled_at' => strtotime('4th of next month'),
	],
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
				<div class="sc-portal-tile sc-box-item">
					<div>
						<div class="sc-item-image">
							<img class="lazyload" data-srcset="{{ box_product.images.first | img_url: 100x100 }} 1x, {{ box_product.images.first | img_url: 200x200 }} 2x" />
						</div>
						<div>
							<div class="sc-item-title">{{ box_product.title }}</div>
							<div class="sc-item-subtitle">{{ box_product.variants.first.title }}</div>
							<?php if($item['scent_club_product']){ ?>
								<div class="sc-item-link"><a href="/products/{{ box_product.handle }}">Explore This Month's Scent</a></div>
							<?php } ?>
						</div>
					</div>
					<div>
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
		</div>
	</div>
</div>