<?php
require_once dirname(__FILE__).'/../includes/config.php';
require_once dirname(__FILE__).'/../includes/class.RechargeClient.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

$rc = new RechargeClient();

$res = $rc->get('/charges?', ['status' => 'QUEUED', 'limit' => 250]);

update_charge_discounts($db, $rc, $res['charges']);