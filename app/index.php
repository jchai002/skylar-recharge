<?php
require_once('../includes/config.php');

//echo "Skylar Shopify App - Created by Tim";

//die();
if(isset($_GET['code'])) {
	echo $sc->getAccessToken($_GET['code']);
	exit;
}
echo $sc->getAuthorizeUrl(SHOPIFY_SCOPE, 'https://ec2production.skylar.com/app');