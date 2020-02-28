<?php
require_once(__DIR__.'/../includes/config.php');
require_once(__DIR__.'/../includes/class.ShopifyClient.php');

$count = 20;
$value = 29;

$sc->resetCreds();
$sc->addSecret($_ENV['SHOPIFY_PRIVATE_APP_KEY'], $_ENV['SHOPIFY_PRIVATE_APP_SECRET']);

$sc = new ShopifyPrivateClient();

$outstream = fopen("giftcards.csv", 'w');
for($i = 0; $i < $count; $i++){
	try {
		$gift_card = $sc->post('/admin/api/2019-10/gift_cards.json', ['gift_card' => [
			'note' => 'UX Testing Sample Palette',
			'initial_value' => $value,
		]]);
	} catch(Exception $e){
		print_r($e);
		continue;
	}
	echo $gift_card['id']." ".$gift_card['balance']." ".$gift_card['code'].PHP_EOL;
	if($i == 0){
		fputcsv($outstream, array_keys($gift_card));
	}
	fputcsv($outstream, $gift_card);
}