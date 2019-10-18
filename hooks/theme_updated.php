<?php
require_once(__DIR__.'/../includes/config.php');

if(!empty($_REQUEST['id'])){
	$theme = $sc->get('/admin/api/2019-10/themes/'.intval($_REQUEST['id']).'.json');
} else {
	respondOK();
	$data = file_get_contents('php://input');
	$theme = json_decode($data, true);
}
if(empty($theme)){
	die('no data');
}

echo insert_update_theme($db, $theme);

if(strpos(strtolower($theme['name']), '[pullme]') !== 'false'){
	$settings_data = $sc->get('/admin/api/2019-10/themes/'.$theme['id'].'/assets.json', ['asset'=>['key'=>'config/settings_data.json']])['value'];
	$dir = ENV_TYPE == 'LIVE' ? 'production' : 'staging';
	$settings_data = 'test';

	$command = "sudo -u deploy bash ../git/create_pull_request.sh $dir ".$theme['id'].' "'.addcslashes(trim(str_replace('[pullme]', '', $theme['name'])), '"').'" "'.addcslashes($settings_data, '"').'"';

	$tmp = shell_exec("$command 2>&1");
	echo "> $command ".PHP_EOL."< ".htmlentities(trim($tmp)) . "\n";

	// TODO Get PR number and update theme name

}