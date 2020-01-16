<?php

global $sc;

try {
	$res = $sc->put('/admin/customers/'.intval($_REQUEST['c']).'.json', [
		'customer' => [
			'id' => intval($_REQUEST['c']),
			'email' => $_REQUEST['email'],
		]
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
} catch(ShopifyApiException $e){
	$errors = $e->getResponse()['errors'];
	$error_string = '';
	foreach($errors as $error){
		foreach($error as $error_line){
			$error_string .= $error_line.PHP_EOL;
		}
	}
	echo json_encode([
		'success' => false,
		'error' => $error_string,
		'res' => $e->getResponse(),
	]);
}
