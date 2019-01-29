<?php

require_once dirname(__FILE__).'/../../vendor/autoload.php';

$router = new Router();

$router->route('/members/i', function() {
	require('pages/members.php');
	return true;
});

$path = str_replace('/app/proxy/', '', parse_url($_SERVER['REQUEST_URI'])['path']);

$res = $router->execute($path);

if(!$res){
	echo "Page Not Found";
}