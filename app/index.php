<?php
require_once('../includes/config.php');
require_once('../includes/class.ShopifyClient.php');

error_reporting(E_ALL);
ini_set('display_errors', 1);

$sc = new ShopifyClient();

echo SHOPIFY_APP_KEY;

if(isset($_GET['code'])) {
	echo $sc->getAccessToken($_GET['code']);
	exit;
}
echo $sc->getAuthorizeUrl(SHOPIFY_SCOPE, 'https://ec2production.skylar.com/app');