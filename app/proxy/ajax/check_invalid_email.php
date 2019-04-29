<?php
global $db;
$sc = new ShopifyClient();

$res = $sc->get('/admin/customers/search.json', [
	'query' => 'email:'.$_REQUEST['email'],
]);

if(!empty($res)){
	$customer = $res[0];
}
if($customer['state'] != 'active'){
	$rc = new RechargeClient();
	$main_sub = sc_get_main_subscription($db, $rc, [
		'status' => 'ACTIVE',
		'shopify_customer_id' => $customer['id'],
	]);
	$res = $sc->post('/admin/customers/'.$customer['id'].'/account_activation_url.json');
	if(empty($res)){
		echo json_encode([
			'success' => true,
			'email_sent' => false,
			'res' => $res,
		]);
	} else {
		$url = $res;
		$data = base64_encode(json_encode([
			'token' => "KvQM7Q",
			'event' => 'Sent Transactional Email',
			'customer_properties' => [
				'$email' => $customer['email'],
			],
			'properties' => [
				'email_type' => !empty($main_sub) ? 'request_account' : 'request_account_sc',
				'first_name' => $customer['first_name'],
				'account_activation_url' => $url,
			]
		]));
		$ch = curl_init("https://a.klaviyo.com/api/track?data=$data");
		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
		]);
		$res = json_decode(curl_exec($ch));
		echo json_encode([
			'success' => true,
			'email_sent' => true,
			'res' => $res,
		]);
	}
} else {
	echo json_encode([
		'success' => true,
		'email_sent' => false,
		'res' => $res,
	]);
}