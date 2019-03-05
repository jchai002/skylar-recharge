<?php

global $rc;
$res = $rc->get('/subscriptions', [
	'shopify_customer_id' => $_REQUEST['c'],
	'status' => 'ACTIVE',
]);
$subscriptions = [];
$onetimes = [];
$orders = [];
$charges = [];
$customer = [];
if(!empty($res['subscriptions'])){
	$subscriptions = $res['subscriptions'];
	$rc_customer_id = $subscriptions[0]['customer_id'];
} else {
	$res = $rc->get('/customers', [
		'shopify_customer_id' => $_REQUEST['c'],
	]);
	$customer = $res['customers'][0];
	if(!empty($customer)){
		$rc_customer_id = $customer['id'];
	}
}
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
$upcoming_shipments = generate_subscription_schedule($db, $orders, $subscriptions, $onetimes, $charges);
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
	'arrow::Full Size|rollie:12235409129559:Rollie',
	'capri::Full Size|rollie:12235492425815:Rollie',
	'coral::Full Size|rollie:12235492360279:Rollie',
	'isle::Full Size|rollie:12235492327511:Rollie',
	'meadow::Full Size|rollie:12235492393047:Rollie',
	'willow::Full Size|rollie:12588614484055:Rollie',
];
?>
<!--
<?php print_r($upcoming_box); ?>
-->
{% assign portal_page = 'my_box' %}
{{ 'sc-portal.scss.css' | asset_url | stylesheet_tag }}
<?php if(!empty($upcoming_box['charge'])){ ?>
	{% assign add_to_box_charge_id = '<?=$upcoming_box['charge']['id']?>' %}
<?php } ?>
<div class="sc-portal-page sc-portal-{{ portal_page }} sc-portal-container">
	{% include 'sc-member-nav' %}
	<div class="sc-portal-content">
		<?php if(empty($upcoming_box)){ ?>
		<div class="sc-portal-innercontainer">
			<div class="sc-portal-title">You Aren't A Member!</div>
			<div>
				<a href="/pages/scent-club">Click Here To Learn More</a>
			</div>
		</div>
		<?php } else { ?>
		<div class="sc-portal-innercontainer">
			<div class="sc-portal-title">Your Upcoming Box</div>
			<div class="sc-portal-nextbox">
				<?php foreach($upcoming_box['items'] as $item){ ?>
					{% assign box_product = all_products['<?=$products_by_id[$item['shopify_product_id']]['handle']?>'] %}
					{% assign picked_variant_id = <?=$item['shopify_variant_id']?> | plus: 0 %}
					{% assign box_variant = box_product.variants.first %}
					{% for svariant in box_product.variants %}
						{% if svariant.id == picked_variant_id %}
							{% assign box_variant = svariant %}
						{% endif %}
					{% endfor %}
					<div class="sc-box-item">
						<div class="sc-item-summary">
							<div class="sc-item-image">
								<?php if(is_scent_club($products_by_id[$item['shopify_product_id']])){ ?>
									<img class="lazyload" data-srcset="{{ product | img_url: '100x100' }} 1x, {{ product | img_url: '200x200' }} 2x" />
								<?php } else { ?>
									{% if box_variant.image %}
									<img class="lazyload" data-srcset="{{ box_variant | img_url: '100x100' }} 1x, {{ box_variant | img_url: '200x200' }} 2x" />
									{% else %}
									<img class="lazyload" data-srcset="{{ product | img_url: '100x100' }} 1x, {{ product | img_url: '200x200' }} 2x" />
									{% endif %}
								<?php } ?>
							</div>
							<div>
								<?php if(is_scent_club($products_by_id[$item['shopify_product_id']])){ ?>
									<div class="sc-item-title">Monthly Scent Club</div>
								<?php } else if(is_scent_club_month($products_by_id[$item['shopify_product_id']])){ ?>
									<div class="sc-item-title">Monthly Scent Club</div>
									<div class="sc-item-subtitle">{{ box_variant.title }}</div>
									<div class="sc-item-link"><a href="/products/{{ box_product.handle }}">Explore This Month's Scent</a></div>
								<?php } else if(is_scent_club_swap($products_by_id[$item['shopify_product_id']])){ ?>
									<div class="sc-item-title"><?=$item['product_title']?></div>
									<div class="sc-item-subtitle"><?=$item['variant_title']?></div>
								<?php } else { ?>
									<div class="sc-item-title">{{ box_product.title }}</div>
									<div class="sc-item-subtitle">{{ box_variant.title }}</div>
								<?php } ?>
							</div>
						</div>
						<div class="sc-item-details">
							<div>
								<div class="sc-item-detail-label">Total</div>
								<div class="sc-item-detail-value"> $<?=price_without_trailing_zeroes($item['price']) ?></div>
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
							<div class="sc-discount-title"><?=$discount['code']?>:</div>
							<?php if($discount['type'] == 'percentage'){ ?>
								<div class="sc-discount-value"><?=$discount['amount']?>%</div>
							<?php } else { ?>
								<div class="sc-discount-value">$<?=price_without_trailing_zeroes($discount['amount']) ?></div>
							<?php } ?>
						</div>
					<?php } ?>
					<div class="sc-discount-link" onclick="$('.sc-add-discount').show();$(this).hide();">Got a promo code?</div>
					<form class="sc-add-discount" style="display: none;">
						<div><input type="text" name="discount_code" /></div>
						<div><input type="submit" value="Apply" class="action_button inverted" /></div>
						<?php if(!empty($upcoming_box['charge'])){ ?>
							<input type="hidden" name="charge_id" value="<?=$upcoming_box['charge']['id']?>" />
						<?php } ?>
					</form>
				</div>
				<?php if(!empty($upcoming_box['charge'])){ ?>
					<div class="sc-box-total">
						Grand Total: $<?= price_without_trailing_zeroes($upcoming_box['charge']['total_price']) ?>
					</div>
				<?php } else { ?>
					<div class="sc-box-total">
						Grand Total: $<?= price_without_trailing_zeroes(array_sum(array_column($upcoming_box['items'], 'price'))) ?>
					</div>
				<?php } ?>
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
					{% assign recommended_handles = '<?=$product?>' | split: '|' %}
					{% include 'sc-product-tile' %}
				<?php } ?>
			</div>
		</div>
		<div class="sc-product-section" id="layering">
			<div class="sc-section-title">Layering</div>
			<div class="sc-product-carousel">
				<?php shuffle($recommended_products);
				foreach($recommended_products as $product){ ?>
					{% assign recommended_handles = '<?=$product?>' | split: '|' %}
					{% include 'sc-product-tile' %}
				<?php } ?>
			</div>
		</div>
		<div class="sc-product-section" id="best-sellers">
			<div class="sc-section-title">Best Sellers</div>
			<div class="sc-product-carousel">
				<?php shuffle($recommended_products);
				foreach($recommended_products as $product){ ?>
					{% assign recommended_handles = '<?=$product?>' | split: '|' %}
					{% include 'sc-product-tile' %}
				<?php } ?>
			</div>
		</div>
		<div class="sc-product-section" id="essentials">
			<div class="sc-section-title">The Essentials</div>
			<div class="sc-product-carousel">
				<?php shuffle($recommended_products);
				foreach($recommended_products as $product){ ?>
					{% assign recommended_handles = '<?=$product?>' | split: '|' %}
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
        $('.sc-add-discount').submit(function(e){
            e.preventDefault();
            var btn = $(this).find('.action_button');
            btn.attr('disabled', 'disabled').addClass('disabled');
            btn.find('span').removeClass("zoomIn").addClass('animated zoomOut');
            var data = $(this).serializeJSON();
            data.c = Shopify.queryParams.c;
            $('.loader').fadeIn();
            $.ajax({
                url: '/tools/skylar/subscriptions/update-discount',
                data: data,
                success: function(data){
                    console.log(data);
                    if(data.error){
                        alert(data.error);
                    } else {
                        btn.find('span').text({{ 'products.product.add_to_cart_success' | t | json }}).removeClass('zoomOut').addClass('fadeIn');
                        location.reload();
                    }
                }
            });
        });
        $('.sc-add-line').submit(function(e){
            e.preventDefault();
            var btn = $(this).find('.action_button');
            btn.attr('disabled', 'disabled').addClass('disabled');
            btn.find('span').removeClass("zoomIn").addClass('animated zoomOut');
            var data = $(this).serializeJSON();
            data.c = Shopify.queryParams.c;
            $('.loader').fadeIn();
            $.ajax({
                url: '/tools/skylar/subscriptions/add-to-box',
                data: data,
                success: function(data){
                    console.log(data);
                    if(data.error){
                        alert(data.error);
                    } else {
                        btn.find('span').text({{ 'products.product.add_to_cart_success' | t | json }}).removeClass('zoomOut').addClass('fadeIn');
                        location.reload();
                    }
                }
            });
        });
	    $('.sc-section-menu a').click(function(e){
	        e.preventDefault();
            $('html,body').animate({scrollTop: $($(this).attr('href')).offset().top-140},'slow');
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