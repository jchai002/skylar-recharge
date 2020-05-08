<?php
require_once(__DIR__.'/../includes/config.php');

require_once(__DIR__.'/../includes/class.ShopifyClient.php');
$scp = new ShopifyPrivateClient();

$orders = [];

foreach($orders as $order){
	echo "$order ";
	$order = $sc->get('orders.json?name='.$order)[0];

	$scent_experience_quantity = array_sum(array_column(array_filter($order['line_items'], function($line_item){
		return $line_item['sku'] == '70221408-100'; // digital scent experience
	}), 'quantity'));
	echo "Digital scent experience quantity: $scent_experience_quantity".PHP_EOL;
	if($scent_experience_quantity > 0){
		$codes = [];
		$value = 78;
		while(count($codes) < $scent_experience_quantity){
			// Generate code

			$gift_card = $scp->post('/admin/api/2019-10/gift_cards.json', ['gift_card' => [
				'note' => 'Digital Scent Experience for order '.$order['id'],
				'initial_value' => $value,
			]]);
			$codes[] = strtoupper($gift_card['code']);
			$order_tags[] = 'Scent Experience Code: '.$gift_card['code'];
		}
		klaviyo_send_transactional_email($db, $order['email'], 'scent_experience_gift_codes', ['codes'=>$codes, 'value' => $value, 'smother' => false]);
		$order_tags[] = 'Scent Experience Codes Emailed';
		$order_tags = array_unique($order_tags);
		$res = $sc->put("/admin/orders/".$order['id'].'.json', ['order' => [
			'id' => $order['id'],
			'tags' => implode(',', $order_tags),
		]]);
		if(!empty($res)){
			echo insert_update_order($db, $res, $sc).PHP_EOL;
		}
		echo "email sent".PHP_EOL;
	}
}