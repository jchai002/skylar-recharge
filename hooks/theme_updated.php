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

	die();
	$dir = ENV_TYPE == 'LIVE' ? 'production' : 'staging';
	$commands = [
		'whoami',
		'su -u deploy',
		'cd ~/repos/'.$dir.'/skylar-shopify-theme',
		'echo $PWD',
//		'git checkout master',
//		'sudo -u deploy /usr/bin/git pull',
//		'git checkout settings-theme-'.$theme['id'].' || git checkout -b settings-theme-'.$theme['id'],
	];
	foreach($commands as $command){
		$tmp = shell_exec("$command 2>&1");
		echo "> $command ".PHP_EOL."< ".htmlentities(trim($tmp)) . "\n";
	}
	file_put_contents('/home/deploy/repos/'.$dir.'/src/config/settings_data.json', $settings_data);
	$command = 'git commit -am "pull settings from shopify" && git push';
	$tmp = shell_exec("$command 2>&1");
	echo "> $command ".PHP_EOL."< ".htmlentities(trim($tmp)) . "\n";

	$command = "GITHUB_TOKEN=".$_ENV['GITHUB_TOKEN'];
	$tmp = shell_exec("$command 2>&1");
	echo "> ".str_replace($_ENV['GITHUB_TOKEN'], '***', $command)." ".PHP_EOL."< ".htmlentities(trim(str_replace($_ENV['GITHUB_TOKEN'], '***', $tmp))) . "\n";

	$command = 'hub pr list -h settings-theme-'.$theme['id'];
	$tmp = shell_exec("$command 2>&1");
	echo "> $command ".PHP_EOL."< ".htmlentities(trim($tmp)) . "\n";
	if(empty(trim($tmp))){
		// Create pull request
		$command = 'hub pull-request -m "Settings update: '.trim(str_replace('[pullme]', '', $theme['name'])).'"';
		$tmp = shell_exec("$command 2>&1");
		echo "> $command ".PHP_EOL."< ".htmlentities(trim($tmp)) . "\n";
	}

	// TODO Get PR number and update theme name

}