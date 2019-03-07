<?php

$router = new Router();
$router->route('',function(){
	require_customer_id(function(){
		require('pages/index.php');
	});
	return true;
});
$router->route('/members$/i', function() {
	require_customer_id(function(){
		require('pages/members.php');
	});
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
$router->route('/subscriptions$/i', function() {
	require_customer_id(function(){
		require('pages/subscriptions.php');
	});
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
//	require_customer_id(function(){
		require('pages/billing.php');
//	});
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


function require_customer_id($callback_if_true){
	$customer_id = !empty($_REQUEST['c']) ? $_REQUEST['c'] : 0;
	header('Content-Type: application/liquid');
	if(empty($customer_id)){
		echo "
{% layout 'scredirect' %}
{% if customer == nil %}
<script>
    location.href = '/account/login?next='+location.pathname;
</script>
{% else %}
<script>
    location.search = (location.search.length < 1 ? '?' : location.search+'&') + 'c={{ customer.id }}';
</script>
{% endif %}";
		return false;
	}
	echo "{% if customer == nil %}
{% layout 'scredirect' %}
	<script>
		location.href = '/account/login?next='+location.pathname;
	</script>
	{% elsif customer.id != $customer_id %}
	<script>
		location.search = location.search.replace('c=".$customer_id."','c={{customer.id}}');
	</script>
	{% else %}";
	$callback_if_true();
	echo "{% endif %}";
	return true;
}