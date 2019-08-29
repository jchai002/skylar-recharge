<?php
global $db, $sc, $rc;

// Actually add to box
if(strpos($_REQUEST['c'], '@') !== false){
	$res = $rc->get('/customers', [
		'email' => $_REQUEST['c'],
	]);
} else {
	$res = $rc->get('/customers', [
		'shopify_customer_id' => $_REQUEST['c'],
	]);
}



if(!empty($res['customers'])){
	$customer = $res['customers'][0];
	$res = $rc->get('/charges', [
		'customer_id' => $customer['id'],
		'status' => 'QUEUED',
	]);
	$charges = $res['charges'];
	usort($charges, function ($item1, $item2) {
		if (strtotime($item1['scheduled_at']) == strtotime($item2['scheduled_at'])) return 0;
		return strtotime($item1['scheduled_at']) < strtotime($item2['scheduled_at']) ? -1 : 1;
	});

	$add_to_charge = $charges[0];
	foreach($charges as $charge){
		foreach($charge['line_items'] as $line_item){
			if(is_scent_club_any(get_product($db, $line_item['shopify_product_id']))){
				$add_to_charge = $charge;
				break 2;
			}
		}
	}
}

if(!empty($add_to_charge)){
	$variant = get_variant($db, $_REQUEST['v']);
	$product = get_product($db, $variant['shopify_product_id']);
	$price = get_subscription_price($product, $variant);
	$subscription_price = round($variant['price']*.9);
	$month = date('F', strtotime($add_to_charge['scheduled_at']));

    // Check if they already have this product in a sub
        $stmt = $db->prepare("SELECT * FROM rc_subscriptions rcs
    LEFT JOIN rc_addresses rca ON rcs.address_id=rca.id
    LEFT JOIN rc_customers rcc ON rca.rc_customer_id=rcc.id
    WHERE (rcs.status = 'ONETIME' OR rcs.status = 'ACTIVE')
    AND rcs.deleted_at IS NULL
    AND rcs.cancelled_at IS NULL
    AND rcc.recharge_id=:rc_customer_id
    AND rcs.variant_id=:variant_id");

	$stmt->execute([
		'rc_customer_id' => $customer['id'],
		'variant_id' => $variant['id'],
	]);
	if($stmt->rowCount() > 0){
	    $res = ['subscription'=>$stmt->fetch()];
    } else {
		$res = $rc->post('/subscriptions', [
			'address_id' => $add_to_charge['address_id'],
			'next_charge_scheduled_at' => $add_to_charge['scheduled_at'],
			'price' => $price,
			'quantity' => 1,
			'shopify_variant_id' => $variant['shopify_id'],
			'product_title' => $product['title'],
			'variant_title' => $variant['title'],
			'order_interval_unit' => 'month',
			'order_interval_frequency' => '1',
			'charge_interval_frequency' => '1',
		]);
		if(!empty($res['subscription'])){
		    insert_update_rc_subscription($db, $res['subscription'], $rc, $sc);
        }
		log_event($db, 'SUBSCRIPTION', $res, 'QUICK_ADDED', $_REQUEST, '', 'customer');
    }
}
header('Content-Type: application/liquid');
echo "<!-- ".print_r($res, true)." -->";
?>

{% assign portal_page = 'lander-addtobox' %}
{{ 'sc-portal.scss.css' | asset_url | stylesheet_tag }}
<div class="sc-portal-page sc-portal-{{ portal_page }} sc-portal-container sc-portal-lander">
	<?php if(!empty($add_to_charge) && !empty($res['subscription'])){ ?>
	<div class="sc-lander-title">You added <?=$product['title']?> to your Skylar Box.</div>
    <?php if($product['type'] == 'Body Bundle'){ ?>
            <div class="sc-lander-price">
                <span>Total:</span> <span class="was_price">$56.00</span> <span class="price">$<?=number_format($price,2)?></span> <span class="sc-lander-savings">*You save over 22%!</span>
            </div>
            <div class="sc-lander-image">
                <img class="lazyload" data-srcset="{{ all_products['<?= $product['handle'] ?>'].featured_image | img_url: 'x280' }} 1x, {{ all_products['<?= $product['handle'] ?>'].featured_image | img_url: 'x280', scale: 2 }} 2x" />
            </div>
    <?php } else { ?>
            <div class="sc-lander-price">
                <span>Total:</span> <span class="was_price">$<?=$variant['price']?></span> <span class="price">$<?=number_format($price,2)?></span> <span class="sc-lander-savings">*You save 10%!</span>
            </div>
            <div class="sc-lander-image">
                <img class="lazyload" data-srcset="{{ all_products['<?= $product['handle'] ?>'].featured_image | img_url: '220x280', crop: 'center' }} 1x, {{ all_products['<?= $product['handle'] ?>'].featured_image | img_url: '220x280', crop: 'center', scale: 2 }} 2x" />
            </div>
    <?php } ?>
	<div class="sc-lander-note">
		This item will ship each month, starting with your <?=$month?> box. <br />Change, skip, swap, or cancel any time. <br />Need to make more changes to your box? <br class="sc-mobile" />Log into your account now.
	</div>
	<?php } else {
		log_event($db, 'SUBSCRIPTION', $res, 'QUICK_ADDED', $_REQUEST, 'Failed and saw error', 'customer');
		?>
		<div class="sc-lander-note">
			Sorry, we were unable to locate your account! Please log in to add your item:
		</div>
	<?php } ?>
	<div class="sc-lander-button">
		<a href="/tools/skylar/schedule" class="action_button">Login to My Account</a>
	</div>
</div>
<style>
	.promo_banner {
		display: none;
	}
	.header {
		position: fixed;
	}
</style>