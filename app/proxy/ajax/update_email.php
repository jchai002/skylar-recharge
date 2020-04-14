<?php

global $sc, $rc;

// Probably need to check if updating is possible in both RC and SC first
$can_update = true;

$res = $sc->get('customers/search.json', ['query' => 'email:'.$_REQUEST['email']]);
if(!empty($res)){
	$res = array_filter($res, function($customer){
		return strtolower($customer['email']) == strtolower($_REQUEST['email']);
	});
}
if(!empty($res)){
	$can_update = false;
	echo json_encode([
		'success' => false,
		'res' => $res,
		'error' => 'This email address is already in use. If you need to merge two accounts together, please contact support at hello@skylar.com.',
	]);
}

$res = $rc->get('customers', ['email' => $_REQUEST['email']]);
if(!empty($res)){
	$can_update = false;
	echo json_encode([
		'success' => false,
		'res' => $res,
		'error' => 'This email address is already in use. If you need to merge two accounts together, please contact support at hello@skylar.com.',
	]);
}

if($can_update){
	try {
		$res = $sc->put('customers/'.intval($_REQUEST['c']).'.json', [
			'customer' => [
				'id' => intval($_REQUEST['c']),
				'email' => $_REQUEST['email'],
			]
		]);

		if($res['email'] == $_REQUEST['email']){

			$res = $rc->put('customers/'.intval($_REQUEST['c']).'.json', [
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
}
