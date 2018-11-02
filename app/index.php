<?php
require_once('../includes/config.php');
require_once('../includes/class.ShopifyClient.php');

echo "Skylar Shopify App - Created by Tim";

//die();
$rc = new RechargeClient();
if(isset($_GET['code'])) {
	echo $sc->getAccessToken($_GET['code']);
	exit;
}
echo $sc->getAuthorizeUrl(SHOPIFY_SCOPE, 'https://ec2production.skylar.com/app');