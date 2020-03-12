<?php
require_once(__DIR__.'/../../includes/config.php');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
	// return only the headers and not the content
	// only allow CORS if we're doing a GET - i.e. no saving for now.
	if (
		isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])
		&& $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] == 'GET'
		&& isset($_SERVER['HTTP_ORIGIN'])
//		&& is_approved($_SERVER['HTTP_ORIGIN'])
	) {
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Headers: X-Requested-With');
	}
	exit;
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
	$data = file_get_contents('php://input');
	$data = json_decode($data, true);
	if(!empty($data['path'])){
		$_REQUEST['path'] = $data['path'];
	}
	if(!empty($data['method'])){
		$_REQUEST['method'] = $data['method'];
	}
	if(!empty($data['payload'])){
		$_REQUEST['payload'] = $data['payload'];
	}
}

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