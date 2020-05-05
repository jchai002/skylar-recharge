<?php

require_once(__DIR__.'/includes/config.php');

$cc_order = $cc->get('SalesOrders/210029', [
	'query' => [
		'fields' => implode(',', ['id', 'lineItems']),
	],
])->getJson();

print_r($cc_order);
die();

$sort = array_reduce($cc_order['lineItems'], function($carry, $item){
	return $item['sort'] > $carry ? $item['sort'] : $carry;
}, 1);
$sort++;
$cc_order['lineItems'][] = [
//	'id' => 384333,
//	'createdDate' => gmdate('Y-m-d\TH:i:s\Z'),
	'transactionId' => $cc_order['id'],
	'productId' => 1494,
	'productOptionId' => 1495,
//	'integrationRef' => '0',
	'sort' => $sort,
	'code' => '99238701-112',
	'name' => 'Scent Peel Back Salt Air',
	'qty' => 1,
	'styleCode' => '99238701-112',
//	'barcode' => '',
//	'sizeCodes' => NULL,
	'lineComments' => 'Auto-added by API',
//	'accountCode' => '',
//	'stockControl' => 'FIFO',
//	'stockMovements' => [],
];

print_r($cc_order);
sleep(1);

$res = $cc->put('SalesOrders', [
	'http_errors' => false,
	'json' => [$cc_order],
]);

print_r($res->getBody()->getContents());