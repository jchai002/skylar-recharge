<?php

require_once dirname(__FILE__).'/../../vendor/autoload.php';

$router = new Router();

$res = $router->execute($_SERVER['REQUEST_URI']);

echo $_SERVER['REQUEST_URI'];