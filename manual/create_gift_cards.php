<?php
require_once(__DIR__.'/../includes/config.php');

$count = 696;
$value = 78;

$scp = new ShopifyPrivateClient();

$outstream = fopen("giftcards.csv", 'w');
for($i = 0; $i < $count; $i++){
	$gift_card = $scp->post('/admin/api/2019-10/gift_cards.json', ['gift_card' => [
		'note' => 'Automatically created for scent experience',
		'initial_value' => $value,
	]]);
	echo $gift_card['id']." ".$gift_card['balance']." ".$gift_card['code'].PHP_EOL;
	if($i == 0){
		fputcsv($outstream, array_keys($gift_card));
	}
	fputcsv($outstream, $gift_card);
}