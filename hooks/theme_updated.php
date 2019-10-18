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

if(strpos(strtolower($theme['name']), '[pullme]') !== false){
	$settings_data = $sc->get('/admin/api/2019-10/themes/'.$theme['id'].'/assets.json', ['asset'=>['key'=>'config/settings_data.json']])['value'];
	$dir = ENV_TYPE == 'LIVE' ? 'production' : 'staging';

	$command = "sudo -u deploy bash ../git/prep_theme_commit.sh $dir ".$theme['id'];
	$tmp = shell_exec("$command 2>&1");
	echo "> $command ".PHP_EOL."< ".$tmp . "\n";

	file_put_contents("/home/deploy/repos/$dir/skylar-shopify-theme", $settings_data);

	$command = "sudo -u deploy bash ../git/make_theme_commit.sh $dir ".$theme['id'];
	$tmp = shell_exec("$command 2>&1");
	echo "> $command ".PHP_EOL."< ".$tmp . "\n";

	// See if the pull request already exists
	$ch = curl_init();
	curl_setopt_array($ch, [
		CURLOPT_URL => 'https://api.github.com/repos/JTimNolan/skylar-shopify-theme/pulls?head=settings-theme-'.$theme['id'],
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_USERPWD => "JTimNolan:".$_ENV['GITHUB_TOKEN'],
		CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
		CURLOPT_USERAGENT => 'Skylar App',
	]);
	$res = curl_exec($ch);
	$pull_request = json_decode($res, true);

	if(empty($pull_request)){
		$ch = curl_init();
		curl_setopt_array($ch, [
			CURLOPT_URL => 'https://api.github.com/repos/JTimNolan/skylar-shopify-theme/pulls',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_USERPWD => "JTimNolan:".$_ENV['GITHUB_TOKEN'],
			CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => json_encode([
				"theme" => "Settings update: ".trim(str_ireplace('[pullme]', '', $theme['name'])),
				"head" => "settings-theme-".$theme['id'],
				"base" => "master",
			]),
			CURLOPT_USERAGENT => 'Skylar App',
		]);
		$res = curl_exec($ch);
		$pull_request = json_decode($res, true);
	}
	$theme = $sc->put('/admin/api/2019-10/themes/'.$theme['id'].'.json', [
		'theme' => [
			'name' => '[PULLED] '.trim(str_ireplace('[pullme]', '', $theme['name']))
		]
	]);
	echo $theme['name'].PHP_EOL;
	echo insert_update_theme($db, $theme);
}