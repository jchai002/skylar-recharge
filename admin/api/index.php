<?php
require_once(__DIR__.'/../../includes/config.php');

$sc = new ShopifyClient([], $_ENV['SHOPIFY_UTILS_APP_TOKEN']);
$path = $_REQUEST['path'] ?? str_replace('/admin/api/', '', parse_url($_SERVER['REQUEST_URI'])['path']);

header('Content-Type: application/json');
$result = json_decode($_REQUEST['payload'], true);
if(json_last_error() === JSON_ERROR_NONE){
	$_REQUEST['payload'] = $result;
}
try {
	echo json_encode(['response' => $sc->call($_REQUEST['method'] ?? $_SERVER['REQUEST_METHOD'], $path, $_REQUEST['payload'] ?? []), 'payload' => $_REQUEST['payload'], 'path' => $path]);
} catch (\GuzzleHttp\Exception\ClientException $e) {
	echo json_encode([
		'success' => false,
		'error' => $e->getMessage(),
		'code' => $e->getCode(),
		'response' => json_decode($e->getResponse()->getBody()->getContents()),
		'payload' => $_REQUEST['payload'], 'path' => $path,
	]);
} catch (\GuzzleHttp\Exception\ServerException $e) {
	echo json_encode([
		'success' => false,
		'error' => $e->getMessage(),
		'code' => $e->getCode(),
		'response' => json_decode($e->getResponse()->getBody()->getContents()),
		'payload' => $_REQUEST['payload'], 'path' => $path,
	]);
} catch (Exception $e) {
	echo json_encode([
		'success' => false,
		'error' => $e->getMessage(),
		'code' => $e->getCode(),
		'payload' => $_REQUEST['payload'], 'path' => $path,
	]);
}