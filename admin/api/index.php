<?php
require_once(__DIR__.'/../../includes/config.php');

$sc = new ShopifyClient([], $_ENV['SHOPIFY_UTILS_APP_TOKEN']);

header('Content-Type: application/json');
try {
	print_r($_REQUEST);
	echo json_encode($sc->call($_SERVER['REQUEST_METHOD'], ltrim($_REQUEST['path'] ?? '', '/'), $_REQUEST['data'] ?? []));
} catch (Exception $e) {
	echo json_encode([
		'success' => false,
		'error' => $e->getMessage(),
		'code' => $e->getCode(),
	]);
}