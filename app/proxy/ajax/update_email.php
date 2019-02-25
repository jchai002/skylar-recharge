<?php

$sc = new ShopifyClient();

$res = $sc->put('/admin/customers/'.$_REQUEST['c'].'.json', [
	'email' => $_REQUEST['email'],
]);

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
