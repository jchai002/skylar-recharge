<?php

require_once dirname(__FILE__).'/../../includes/config.php';

// Right now, assume they are a scent club member
$scent_club_active = true;


require_once dirname(__FILE__).'/routes.php';
$path = str_replace('/app/proxy/', '', parse_url($_SERVER['REQUEST_URI'])['path']);
$res = $router->execute($path);
if(!$res){
	echo $path." Not Found";
}