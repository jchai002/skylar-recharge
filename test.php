<?php
require_once('includes/config.php');
require_once('includes/class.ShopifyClient.php');
require_once('includes/class.RechargeClient.php');

$rc = new RechargeClient();

$charges = $rc->get('/charges', ['subscription_id' => 21200731]);
var_dump($charges);