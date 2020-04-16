<?php

global $sc;

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
} catch(\GuzzleHttp\Exception\ClientException $e){
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
