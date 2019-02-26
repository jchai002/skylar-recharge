<?php

global $rc;
$res = $rc->get('/subscriptions', [
	'shopify_customer_id' => $_REQUEST['c'],
	'status' => 'ACTIVE',
]);
$subscriptions = $res['subscriptions'];
if(!empty($subscriptions)){
	$rc_customer_id = $subscriptions[0]['customer_id'];
} else {
	$res = $rc->get('/customers', [
		'shopify_customer_id' => $_REQUEST['c'],
	]);
	$customer = $res['customers'];
	if(!empty($customer)){
		$rc_customer_id = $customer['id'];
	}
}
$onetimes = [];
$orders = [];
$charges = [];
if(!empty($rc_customer_id)){
	$res = $rc->get('/orders', [
		'customer_id' => $rc_customer_id,
		'status' => 'QUEUED',
	]);
	$orders = $res['orders'];
	$res = $rc->get('/charges', [
		'customer_id' => $rc_customer_id,
		'date_min' => date('Y-m-d'),
	]);
	$charges = $res['charges'];
	$res = $rc->get('/onetimes', [
		'customer_id' => $rc_customer_id,
	]);
	foreach($res['onetimes'] as $onetime){
		// Fix for api returning non-onetimes
		if($onetime['status'] == 'ONETIME'){
			$onetimes[] = $onetime;
		}
	}
}
global $db;
$months = empty($more) ? 3 : $more;
$upcoming_shipments = generate_subscription_schedule($orders, $subscriptions, $onetimes, $charges, strtotime(date('Y-m-t',strtotime("+$months months"))));
$products_by_id = [];
$stmt = $db->prepare("SELECT * FROM products WHERE shopify_id=?");
$upcoming_box = false;
foreach($upcoming_shipments as $upcoming_shipment){
	foreach($upcoming_shipment['items'] as $item){
		if(!array_key_exists($item['shopify_product_id'], $products_by_id)){
			$stmt->execute([$item['shopify_product_id']]);
			$products_by_id[$item['shopify_product_id']] = $stmt->fetch();
			if(empty($upcoming_box) && is_scent_club_any($products_by_id[$item['shopify_product_id']])){
				$upcoming_box = $upcoming_shipment;
			}
		}
	}
}

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
{{ 'sc-portal.scss.css' | asset_url | stylesheet_tag }}
<div class="sc-portal-page sc-portal-{{ portal_page }} sc-portal-container">
	{% include 'sc-member-nav' %}
	<div class="sc-portal-content">
		<?php if(empty($upcoming_box)){ ?>
		<div class="sc-portal-innercontainer">
			<div class="sc-portal-title">No Scent Club Subscription Found</div>
		</div>
		<?php } else { ?>
		<div class="sc-portal-innercontainer">
			<div class="sc-portal-title">Your Upcoming Box</div>
			<div class="sc-portal-nextbox">
				<?php foreach($upcoming_box['items'] as $item){ ?>
					{% assign box_product = all_products['<?=$products_by_id[$item['shopify_product_id']]['handle']?>'] %}
					<div class="sc-box-item">
						<div class="sc-item-summary">
							<div class="sc-item-image">
								<img class="lazyload" data-srcset="{{ box_product.images.first | img_url: '100x100' }} 1x, {{ box_product.images.first | img_url: '200x200' }} 2x" />
							</div>
							<div>
								<?php if(is_scent_club_any($products_by_id[$item['shopify_product_id']])){ ?>
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
								<div class="sc-item-detail-value"> ${{ <?=$item['price'] ?> | money_without_trailing_zeroes }}</div>
							</div>
							<div>
								<div class="sc-item-detail-label">Delivery</div>
								<div class="sc-item-detail-value">
									<?php if(is_scent_club_any($products_by_id[$item['shopify_product_id']])){ ?>
										Every Month
									<?php } else if(empty($item['order_interval_frequency'])){ ?>
										Once
									<?php } else if($item['order_interval_frequency'] == '1'){ ?>
										Every <?=$item['order_interval_unit']?>
									<?php } else { ?>
										Every <?=$item['order_interval_frequency']?> <?=$item['order_interval_unit']?>s
									<?php } ?>
								</div>
							</div>
							<div>
								<div class="sc-item-detail-label">Next Charge</div>
								<div class="sc-item-detail-value"><?=date('F j, Y', strtotime($item['next_charge_scheduled_at']))?></div>
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
					Grand Total: ${{ <?=$upcoming_box['price'] ?> | money_without_trailing_zeroes }}
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
		<?php } ?>
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