<?php
require_once(__DIR__.'/../includes/config.php');

$order = $sc->get("/admin/orders/842038050903.json");

// First determine if the order has the scent club product

// Then determine what payment gateway they used


$transactions = $sc->get("/admin/orders/".$order['id']."/transactions.json");

foreach($transactions as $transaction){
	if(in_array($transaction['kind'], ['sale', 'capture', 'authorization'])){
		$charge_id = $transaction['receipt']['id'];
	}
}
if(empty($charge_id)){
	die();
}


header('Content-Type: application/json');

// Get token from payment gateway

\Stripe\Stripe::setApiKey($_ENV['STRIPE_API_KEY']);

$charge = \Stripe\Charge::retrieve($charge_id);
echo json_encode($charge);

// Create new ReCharge customer, address, and subscription