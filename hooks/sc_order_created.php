<?php
require_once(__DIR__.'/../includes/config.php');

$sc = new ShopifyClient();

$order = $sc->get("/admin/orders/841985818711.json");

header('Content-Type: application/json');
echo json_encode($order);
die();

// First determine if the order has the scent club product

// Then determine what payment gateway they used

// Get token from payment gateway

\Stripe\Stripe::setApiKey($_ENV['STRIPE_API_KEY']);

$charge = \Stripe\Charge::retrieve("ch_1DxiYPGkCEBaf6Qc48mzP3vg");

// Create new ReCharge customer, address, and subscription