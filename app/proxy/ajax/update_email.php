<?php

$sc = new ShopifyClient();

try {
	$res = $sc->put('/admin/customers/'.$_REQUEST['c'].'.json', [
		'email' => $_REQUEST['email'],
	]);
} catch(ShopifyApiException $e){
	echo json_encode([
		'success' => false,
		'error' => implode(PHP_EOL, $e->getResponse()['errors']),
	]);
}

if($res['email'] == $_REQUEST['email']){
	echo json_encode([
		'success' => true,
		'res' => $res,
	]);
} else {
	echo json_encode([
		'success' => false,
		'res' => $res,
		'error' => 'Unable to use that email address. Please check that it is valid.',
	]);
}
