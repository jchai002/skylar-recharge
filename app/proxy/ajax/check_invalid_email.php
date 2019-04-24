<?php
$sc = new ShopifyClient();

$res = $sc->get('/admin/customers/search.json', [
	'query' => 'email:'.$_REQUEST['email'],
]);

if(!empty($res)){
	$customer = $res[0];
}
if($customer['state'] != 'active'){
	$res = $sc->post('/admin/customers/'.$customer['id'].'/account_activation_url.json');
}
if(empty($res['account_activation_url'])){
	echo json_encode([
		'success' => true,
		'email_sent' => false,
		'res' => $res,
	]);
} else {
	$ch = curl_init("https://a.klaviyo.com/api/v1/email-template/LkpPDS/send");
	curl_setopt_array($ch, [
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_POST => true,
		CURLOPT_POSTFIELDS => [
			'api_key' => $_ENV['KLAVIYO_API_KEY'],
			'from_email' => 'hello@skylar.com',
			'from_name' => 'Skylar',
			'subject' => 'Activate Your Account!',
			'to' => json_encode([
				['email' => $customer['email']],
			]),
			'context' => json_encode([
				'first_name' => $customer['gift_message'],
				'account_activation_url ' => $res['account_activation_url'],
			]),
		]
	]);
	$res = curl_exec($ch);
	echo json_encode([
		'success' => true,
		'email_sent' => true,
		'res' => $res,
	]);
}
