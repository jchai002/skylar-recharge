<?php
require_once('../includes/config.php');

$sc = new ShopifyClient();

$order = $sc->get("/admin/orders/841985818711.json");

header('Content-Type: application/json');
echo json_encode($order);

// First determine if the order has the scent club product

// Then determine what payment gateway they used

// Get token from payment gateway

// Create new ReCharge customer, address, and subscription