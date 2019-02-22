<?php
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
{% assign portal_page = 'my_box' %}
{{ 'sc-portal.css' | asset_url | stylesheet_tag }}
<div class="sc-portal-page sc-portal-{{ portal_page }} sc-portal-container">
	{% include 'sc-member-nav' %}
	<div class="sc-portal-content">
		<div class="sc-portal-innercontainer">
			<div class="sc-portal-title">Your Upcoming Box</div>
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
		</div>
		<div class="sc-product-section" id="recommendations">
			<div class="sc-section-title">Recommendations based on your profile</div>
			<div class="sc-product-carousel">
				<?php shuffle($recommended_products);
				foreach($recommended_products as $product){ ?>
					{% assign box_product = all_products['<?=$product['handle']?>'] %}
					{% include 'sc-product-tile' %}
				<?php } ?>
			</div>
		</div>
		<div class="sc-product-section" id="layering">
			<div class="sc-section-title">Layering</div>
			<div class="sc-product-carousel">
				<?php shuffle($recommended_products);
				foreach($recommended_products as $product){ ?>
					{% assign box_product = all_products['<?=$product['handle']?>'] %}
					{% include 'sc-product-tile' %}
				<?php } ?>
			</div>
		</div>
		<div class="sc-product-section" id="best-sellers">
			<div class="sc-section-title">Best Sellers</div>
			<div class="sc-product-carousel">
				<?php shuffle($recommended_products);
				foreach($recommended_products as $product){ ?>
					{% assign box_product = all_products['<?=$product['handle']?>'] %}
					{% include 'sc-product-tile' %}
				<?php } ?>
			</div>
		</div>
		<div class="sc-product-section" id="essentials">
			<div class="sc-section-title">The Essentials</div>
			<div class="sc-product-carousel">
				<?php shuffle($recommended_products);
				foreach($recommended_products as $product){ ?>
					{% assign box_product = all_products['<?=$product['handle']?>'] %}
					{% include 'sc-product-tile' %}
				<?php } ?>
			</div>
		</div>
	</div>
</div>
{{ 'sc-portal.js' | asset_url | script_tag }}
<script>
	$(document).ready(function(){
	    $('.sc-section-menu a').click(function(e){
	        e.preventDefault();
            $('html,body').animate({scrollTop: $($(this).attr('href')).offset().top-140},'slow');
		});
        $('.add-to-box').click(function(){
            var btn = $(this);
            btn.attr('disabled', 'disabled').addClass('disabled');
            btn.find('span').removeClass("zoomIn").addClass('animated zoomOut');
            window.setTimeout(function(){ // For now, later ajax
                btn.find('span').text({{ 'products.product.add_to_cart_success' | t | json }}).removeClass('zoomOut').addClass('fadeIn');
            }, 1000);
        });
        optional_scripts.onload('slick', function(){
            $('.sc-product-carousel').slick({
                slidesToShow: 3,
                centerPadding: '100px',
                focusOnSelect: true,
                infinite: false,
                arrows: false,
                dots: true,
                responsive: [
                    {
                        breakpoint: 1250,
                        settings: {
                            slidesToShow: 2.8,
                        }
                    },
                    {
                        breakpoint: 1200,
                        settings: {
                            slidesToShow: 2.2,
                        }
                    },
                    {
                        breakpoint: 1000,
                        settings: {
                            slidesToShow: 3.2,
                        }
                    },
                    {
                        breakpoint: 850,
                        settings: {
                            slidesToShow: 2.2,
                        }
                    },
                    {
                        breakpoint: 700,
                        settings: {
                            slidesToShow: 1.7,
                        }
                    },
                    {
                        breakpoint: 450,
                        settings: {
                            slidesToShow: 1.5,
                        }
                    },
                    {
                        breakpoint: 400,
                        settings: {
                            slidesToShow: 1.2,
                        }
                    },
                ],
            });
        });
	});
</script>