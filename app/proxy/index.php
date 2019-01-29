<?php

require_once dirname(__FILE__).'/../../vendor/autoload.php';

$router = new Router();

$path = str_replace('/app/proxy', '', parse_url($_SERVER['REQUEST_URI'])['path']);

//$res = $router->execute($_SERVER['REQUEST_URI']);

echo $path;