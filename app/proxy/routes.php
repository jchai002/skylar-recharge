<?php

$router = new Router();
$router->route('',function() use($db, $rc){
	require_customer_id(function() use($db, $rc){
		if(!empty(sc_get_main_subscription($db, $rc, [
			'shopify_customer_id' => intval($_REQUEST['c']),
			'status' => 'ACTIVE'
		]))){
			require('pages/members.php');
		} else {
			$stmt = $db->prepare("SELECT 1 FROM rc_subscriptions s
									LEFT JOIN rc_addresses a ON s.address_id=a.id
									LEFT JOIN rc_customers rcc ON a.rc_customer_id=rcc.id
									LEFT JOIN customers c ON c.id=rcc.customer_id
									WHERE c.shopify_id = ?
										AND (s.status='ACTIVE' OR s.status='ONETIME')
										AND s.deleted_at IS NULL
									LIMIT 1");
			$stmt->execute([$_REQUEST['c']]);
			if($stmt->rowCount() > 0){
				require('pages/schedule.php');
			} else {
				require('pages/orderhistory.php');
			}
		}
	});
	return true;
});
$router->route('/members$/i', function() {
	require_customer_id(function(){
		require('pages/members.php');
	});
	return true;
});
$router->route('/schedule/i', function() {
	require_customer_id(function(){
		require('pages/schedule.php');
	});
	return true;
});
$router->route('/staging$/i', function() {
	require_customer_id(function(){
		require('pages/subscriptions.php');
	});
	return true;
});
$router->route('/subscriptions$/i', function() {
	require_customer_id(function(){
		require('pages/subscriptions.php');
	});
	return true;
});
$router->route('/quick-add$/i', function() {
	require('pages/addtobox_lander.php');
	return true;
});

$router->route('/check-invalid-email$/i', function() use(&$json_output) {
	$json_output = true;
	if(empty($_REQUEST['email'])){
		return false;
	}
	require('ajax/check_invalid_email.php');
	return true;
});
$router->route('/subscriptions\/update-box-date$/i', function() use(&$json_output) {
	$json_output = true;
	if(empty($_REQUEST['c'])){
		echo json_encode([
			'success' => false,
			'error' => 'Missing customer ID. Please refresh.',
		]);
		return true;
	}
	if(empty($_REQUEST['charge_id'])){
		echo json_encode([
			'success' => false,
			'error' => 'Missing charge ID. Please refresh.',
		]);
		return true;
	}
	if(empty($_REQUEST['date']) && empty(strtotime($_REQUEST['date']))){
		echo json_encode([
			'success' => false,
			'error' => 'Missing date. Please refresh.',
		]);
		return true;

	}
	require('ajax/update_box_date.php');
	return true;
});
$router->route('/subscriptions\/ac-swap/i', function() use(&$json_output) {
	$json_output = true;
	if(empty($_REQUEST['c'])){
		echo json_encode([
			'success' => false,
			'error' => 'Missing customer ID. Please refresh.',
		]);
		return true;
	}
	require('ajax/ac_swap.php');
	return true;
});
$router->route('/subscriptions\/swap-variant$/i', function() use(&$json_output) {
	$json_output = true;
	if(empty($_REQUEST['c'])){
		echo json_encode([
			'success' => false,
			'error' => 'Missing customer ID. Please refresh.',
		]);
		return true;
	}
	require('ajax/swap_variant.php');
	return true;
});
$router->route('/subscriptions\/ac-cancel/i', function() use(&$json_output) {
	$json_output = true;
	if(empty($_REQUEST['c'])){
		echo json_encode([
			'success' => false,
			'error' => 'Missing customer ID. Please refresh.',
		]);
		return true;
	}
	require('ajax/ac_cancel.php');
	return true;
});
$router->route('/subscriptions\/ac-move-today/i', function() use(&$json_output) {
	$json_output = true;
	if(empty($_REQUEST['c'])){
		echo json_encode([
			'success' => false,
			'error' => 'Missing customer ID. Please refresh.',
		]);
		return true;
	}
	require('ajax/ac_move_today.php');
	return true;
});
$router->route('/subscriptions\/ac-move-back/i', function() use(&$json_output) {
	$json_output = true;
	if(empty($_REQUEST['c'])){
		echo json_encode([
			'success' => false,
			'error' => 'Missing customer ID. Please refresh.',
		]);
		return true;
	}
	require('ajax/ac_move_back.php');
	return true;
});
$router->route('/subscriptions\/swap/i', function() use(&$json_output) {
	$json_output = true;
	if(empty($_REQUEST['c'])){
		echo json_encode([
			'success' => false,
			'error' => 'Missing customer ID. Please refresh.',
		]);
		return true;
	}
	require('ajax/swap.php');
	return true;
});
$router->route('/subscriptions\/cancel$/i', function() use(&$json_output) {
	$json_output = true;
	if(empty($_REQUEST['c'])){
		echo json_encode([
			'success' => false,
			'error' => 'Missing customer ID. Please refresh.',
		]);
		return true;
	}
	require('ajax/cancel.php');
	return true;
});
$router->route('/subscriptions\/update-discount/i', function() use(&$json_output) {
	$json_output = true;
	if(empty($_REQUEST['c'])){
		echo json_encode([
			'success' => false,
			'error' => 'Missing customer ID. Please refresh.',
		]);
		return true;
	}
	require('ajax/update_discount.php');
	return true;
});
$router->route('/subscriptions\/skip/i', function() use(&$json_output) {
	$json_output = true;
	if(empty($_REQUEST['c'])){
		echo json_encode([
			'success' => false,
			'error' => 'Missing customer ID. Please refresh.',
		]);
		return true;
	}
	require('ajax/skip.php');
	return true;
});
$router->route('/subscriptions\/add-to-box-date$/i', function() use(&$json_output) {
	$json_output = true;
	if(empty($_REQUEST['c'])){
		echo json_encode([
			'success' => false,
			'error' => 'Missing customer ID. Please refresh.',
		]);
		return true;
	}
	require('ajax/add_to_box_date.php');
	return true;
});
$router->route('/subscriptions\/add-to-box$/i', function() use(&$json_output) {
	$json_output = true;
	if(empty($_REQUEST['c'])){
		echo json_encode([
			'success' => false,
			'error' => 'Missing customer ID. Please refresh.',
		]);
		return true;
	}
	require('ajax/add_to_box.php');
	return true;
});
$router->route('/subscriptions\/get-box$/i', function() use(&$json_output) {
	$json_output = true;
	if(empty($_REQUEST['c'])){
		echo json_encode([
			'success' => false,
			'error' => 'Missing customer ID. Please refresh.',
		]);
		return true;
	}
	require('ajax/get_box.php');
	return true;
});
$router->route('/subscriptions\/remove-item/i', function() use(&$json_output) {
	$json_output = true;
	if(empty($_REQUEST['c'])){
		echo json_encode([
			'success' => false,
			'error' => 'Missing customer ID. Please refresh.',
		]);
		return true;
	}
	if(empty($_REQUEST['id'])){
		echo json_encode([
			'success' => false,
			'error' => 'Missing subscription ID. Please refresh.',
		]);
		return true;
	}
	require('ajax/remove_item.php');
	return true;
});
$router->route('/order-history$/i', function() {
	require_customer_id(function(){
		require('pages/orderhistory.php');
	});
	return true;
});
$router->route('/billing\/update-card/i', function() use(&$json_output) {
	$json_output = true;
	if(empty($_REQUEST['c'])){
		echo json_encode([
			'success' => false,
			'error' => 'Missing customer ID. Please refresh.',
		]);
		return true;
	}
	if(empty($_REQUEST['token'])){
		echo json_encode(['success' => false]);
		return false;
	}
	require('ajax/update_card.php');
	return true;
});
$router->route('/billing\/update-address/i', function() use(&$json_output) {
	$json_output = true;
	if(empty($_REQUEST['c'])){
		echo json_encode([
			'success' => false,
			'error' => 'Missing customer ID. Please refresh.',
		]);
		return true;
	}
	require('ajax/update_address.php');
	return true;
});
$router->route('/billing$/i', function() {
	require_customer_id(function(){
		require('pages/billing.php');
	});
	return true;
});
$router->route('/settings\/update-email/i', function() use(&$json_output) {
	$json_output = true;
	if(empty($_REQUEST['c'])){
		echo json_encode([
			'success' => false,
			'error' => 'Missing customer ID. Please refresh.',
		]);
		return true;
	}
	require('ajax/update_email.php');
	return true;
});
$router->route('/settings\/update-password/i', function() use(&$json_output) {
	$json_output = true;
	if(empty($_REQUEST['c'])){
		echo json_encode([
			'success' => false,
			'error' => 'Missing customer ID. Please refresh.',
		]);
		return true;
	}
	require('ajax/update_password.php');
	return true;
});
$router->route('/settings$/i', function() {
	require_customer_id(function(){
		require('pages/settings.php');
	});
	return true;
});

$admin_customers = [644696211543];
function require_customer_id($callback_if_true){
	global $admin_customers;
	$customer_id = !empty($_REQUEST['c']) ? $_REQUEST['c'] : 0;
	header('Content-Type: application/liquid');
	if(empty($customer_id)){
		echo "
{% layout 'scredirect' %}
{% if customer == nil %}
<script>
    location.href = '/account/login?next='+encodeURIComponent(location.pathname+location.search);
</script>
{% else %}
<script>
    location.search = (location.search.length < 1 ? '?' : location.search+'&') + 'c={{ customer.id }}';
</script>
{% endif %}";
		return false;
	}
	$alias_override = !empty($_REQUEST['alias']) && $_REQUEST['alias'] == md5($_ENV['ALIASKEY'].$customer_id);
	if(!empty($alias_override)){
		global $sc;
		$first_name = 'Alias';
		if($sc === null){
			$sc = new ShopifyClient();
		}
		if($sc instanceof ShopifyClient){
			$customer = $sc->get('/admin/customers/'.$customer_id.'.json');
			if(!empty($customer)){
				$first_name = $customer['first_name']."*";
			}
		}
		echo "
		{% assign is_alias = true %}
		{% if theme.id == 73040330839 %}
		{% assign theme_query = '' %}
		{% else %}
		{% assign theme_query = '&theme_id=' | append: theme.id %}
		{% endif %}
		{% assign account_query = 'c=$customer_id&alias=".$_REQUEST['alias']."' | append: theme_query %}
		{% if customer.id != $customer_id %}{% assign customer_first_name = '$first_name' %}{% else %}{% assign customer_first_name = customer.first_name %}{% endif %}
		{% assign customer_id = $customer_id %}";
		$callback_if_true();
		return true;
	}

	ob_start();
	$callback_if_true();
	$output = ob_get_contents();
	ob_end_clean();
	echo "{% assign admin_customers = '".implode('|',$admin_customers)."' %}
{% if theme.id == 73040330839 %}
{% assign theme_query = '' %}
{% else %}
{% assign theme_query = '&theme_id=' | append: theme.id %}
{% endif %}
{% assign account_query = 'c=$customer_id' | append: theme_query %}
{% if customer == nil %}
	{% layout 'scredirect' %}
	<script>
		location.href = '/account/login?next='+encodeURIComponent(location.pathname+location.search);
	</script>
{% elsif customer.id == $customer_id or admin_customers contains customer.id %}
	{% assign is_alias = customer.id != $customer_id %}
	{% if customer.id != $customer_id %}{% assign customer_first_name = 'Alias'  %}{% else %}{% assign customer_first_name = customer.first_name %}{% endif %}
	{% assign customer_id = $customer_id %}
	".$output."
{% else %}
	<script>
		location.search = location.search.replace('c=".$customer_id."','c={{customer.id}}');
	</script>
{% endif %}";
	return true;
}