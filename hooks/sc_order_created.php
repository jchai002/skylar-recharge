<?php
require_once(__DIR__.'/../includes/config.php');

$sc = new ShopifyClient();

$order = $sc->get("/admin/orders/842038050903.json");

$transactions = $sc->get("/admin/orders/".$order['id']."/transactions.json");

foreach($transactions as $transaction){
	if(in_array($transaction['kind'], ['sale', 'capture', 'authorization'])){
		$charge_id = $transaction['reciept']['id'];
	}
}


header('Content-Type: application/json');
echo json_encode($transactions);
die();

// First determine if the order has the scent club product

// Then determine what payment gateway they used

// Get token from payment gateway

\Stripe\Stripe::setApiKey($_ENV['STRIPE_API_KEY']);

$charge = \Stripe\Charge::retrieve("ch_1DxiYPGkCEBaf6Qc48mzP3vg");

// Create new ReCharge customer, address, and subscription