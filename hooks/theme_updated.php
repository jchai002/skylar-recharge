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
	$settings_data = 'test'.rand(1,99999);

	$command = "sudo -u deploy bash ../git/create_pull_request.sh $dir ".$theme['id'].' '.$_ENV['GITHUB_TOKEN'].' "'.addcslashes(trim(str_replace('[pullme]', '', $theme['name'])), '"').'" "'.addcslashes($settings_data, '"').'"';

	$tmp = shell_exec("$command 2>&1");
	echo str_replace($_ENV['GITHUB_TOKEN'], '***', "> $command ".PHP_EOL."< ".$tmp . "\n");

	// See if the pull request already exists
	$ch = curl_init();
	curl_setopt_array($ch, [
		CURLOPT_URL => 'https://api.github.com/repos/JTimNolan/skylar-shopify-theme/pulls?head=settings-theme-'.$theme['id'],
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_USERPWD => "JTimNolan:".$_ENV['GITHUB_TOKEN'],
		CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
	]);
	$res = curl_exec($ch);

	// TODO Get PR number and update theme name

}