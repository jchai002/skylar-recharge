<?php
require_once(__DIR__.'/../../includes/config.php');

$sc = new ShopifyClient([], $_ENV['SHOPIFY_UTILS_APP_TOKEN']);

header('Content-Type: application/json');
echo json_encode($sc->call($_SERVER['REQUEST_METHOD'], $_REQUEST['path'], $params));