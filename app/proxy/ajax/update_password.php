<?php

$sc = new ShopifyClient();

try {
	$res = $sc->put('/admin/customers/'.$_REQUEST['c'].'.json', [
		'customer' => [
			'id' => intval($_REQUEST['c']),
			'password' => $_REQUEST['password'],
			'password_confirmation' => $_REQUEST['password_confirmation'],
		]
	]);

	echo json_encode([
		'success' => true,
	]);
} catch(ShopifyApiException $e){
	echo json_encode([
		'success' => false,
		'error' => implode(PHP_EOL, $e->getResponse()['errors']),
	]);
}
