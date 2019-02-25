<?php
header('Content-Type: application/json');

$sc = new ShopifyClient();

$res = $sc->put('/admin/customers/'.)

echo json_encode([
	'success' => true,
	'res' => $res,
]);