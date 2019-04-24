<?php
$sc = new ShopifyClient();

$res = $sc->get('/admin/customers/search.json', [
	'query' => 'email:'.$_REQUEST['email'],
]);

echo json_encode($res);