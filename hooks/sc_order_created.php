<?php
require_once('../includes/config.php');

$sc = new ShopifyClient();

$order = $sc->get("/admin/orders/841985818711.json");

header('Content-Type: application/json');

// First determine if the order has the scent club product

// Then determine what payment gateway they used

// Get token from payment gateway

\Stripe\Stripe::setApiKey("sk_test_ehffn2kaVBPyqeaLcYLDPL5S");

$charge = \Stripe\Charge::retrieve("ch_1DxiYPGkCEBaf6Qc48mzP3vg");
echo json_encode($charge);

// Create new ReCharge customer, address, and subscription