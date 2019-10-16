<?php
require_once(__DIR__.'/../includes/config.php');

$themes = $sc->get('/admin/api/2019-10/themes.json');

foreach($themes as $theme){
	echo insert_update_theme($db, $theme).PHP_EOL;
	// Check settings
	// cd to dir for theme git
	// Check out master
	// git pull
	// Check if branch exists, create if not
	// Overwrite settings file
}