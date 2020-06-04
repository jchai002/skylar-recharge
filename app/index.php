<?php
require_once('../includes/config.php');
require_once('../includes/class.ShopifyClient.php');

//echo "Skylar Shopify App - Created by Tim";

//die();
$sc = new ShopifyAppClient('maven-and-muse.myshopify.com', '', $_ENV['SKYLAR_UTILS_DEV_KEY'], $_ENV['SKYLAR_UTILS_DEV_SECRET']);
if(isset($_GET['code'])) {
	echo $sc->getAccessToken($_GET['code']);
	exit;
}
echo $sc->getAuthorizeUrl(SHOPIFY_SCOPE, 'https://ec2staging.skylar.com/app');