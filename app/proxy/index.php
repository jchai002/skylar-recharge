<?php

require_once dirname(__FILE__).'/../../includes/config.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Right now, assume they are a scent club member
$scent_club_active = true;

$rc = new RechargeClient();

require_once dirname(__FILE__).'/routes.php';
$path = str_replace('/app/proxy/', '', parse_url($_SERVER['REQUEST_URI'])['path']);
$res = $router->execute($path);
if(!$res){
	echo $path." Not Found";
}