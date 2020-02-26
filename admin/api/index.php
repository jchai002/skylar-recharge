<?php
require_once(__DIR__.'/../../includes/config.php');

$sc = new ShopifyClient([], $_ENV['SHOPIFY_UTILS_APP_TOKEN']);
$path = $_REQUEST['path'] ?? str_replace('/admin/api/', '', parse_url($_SERVER['REQUEST_URI'])['path']);

header('Content-Type: application/json');
try {
	echo json_encode(['response' => $sc->call($_SERVER['REQUEST_METHOD'], $path, $_REQUEST['data'] ?? [])]);
} catch (Exception $e) {
	echo json_encode([
		'success' => false,
		'error' => $e->getMessage(),
		'code' => $e->getCode(),
	]);
}