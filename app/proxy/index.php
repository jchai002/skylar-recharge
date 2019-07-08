<?php

require_once dirname(__FILE__).'/../../includes/config.php';

$rc = new RechargeClient();

require_once dirname(__FILE__).'/routes.php';
$path = str_replace('/app/proxy/', '', parse_url($_SERVER['REQUEST_URI'])['path']);


$json_output = false;
try {
	ob_start();
	$res = $router->execute($path);
	if($json_output){
		header('Content-Type: application/json');
	}
} catch (ShopifyApiException $e){
	ob_end_clean();
	if($json_output){
		header('Content-Type: application/json');
		$error_string = '';
		if(!is_array($e->getResponse()['errors'])){
			$error_string = $e->getResponse()['errors'];
		} else {
			foreach($e->getResponse()['errors'] as $error){
				foreach($error as $error_line){
					$error_string .= $error_line.PHP_EOL;
				}
			}
		}
		echo json_encode([
			'success' => false,
			'error' => $error_string,
			'res' => $e->getResponse(),
		]);
	} else {
		header('Content-Type: application/liquid');
		echo "An error has occurred while loading this page. Please try again later.";
		echo "<!-- ".print_r($e->getResponse(), true)." -->";
	}
	$res = true;
} catch (ErrorException $e){
	if($json_output){
		header('Content-Type: application/json');
		echo json_encode([
			'success' => false,
			'res' => $e,
		]);
	} else {
		header('Content-Type: application/liquid');
		echo "An error has occurred while loading this page. Please try again later.";
		echo "<!-- ".var_dump($e)." -->";
	}
	$res = true;
}
ob_end_flush();
if(!$res){
	header('Content-Type: application/liquid');
	echo $path." Not Found";
}