<?php

global $rc;

if(empty($_REQUEST['subscription_id']) || empty($_REQUEST['date']) || empty($_REQUEST['handle'])){
	die(json_encode([
		'success' => false,
		'errors' => [['msg'=>'Fields Missing']],
	]));
}
sc_swap_scent($rc, $_REQUEST['subscription_id'], $_REQUEST['handle'], strtotime($_REQUEST['date']));