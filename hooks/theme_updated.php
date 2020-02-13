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

echo insert_update_theme($db, $theme).PHP_EOL;

// Check if pullme theme
if(strpos(strtolower($theme['name']), '[pullme]') !== false || !empty($_REQUEST['force'])){
	$dir = ENV_TYPE == 'LIVE' ? 'production' : 'staging';

	$command = "sudo -u deploy bash ../git/make_theme_commit.sh $dir ".$theme['id']." ".$_ENV['SHOPIFY_PRIVATE_APP_KEY'].":".$_ENV['SHOPIFY_PRIVATE_APP_SECRET'];
	$tmp = shell_exec("$command 2>&1");
	echo str_replace($_ENV['SHOPIFY_PRIVATE_APP_KEY'].":".$_ENV['SHOPIFY_PRIVATE_APP_SECRET'], '***', "> $command ".PHP_EOL."< ".$tmp . "\n");

	// See if the pull request already exists
	$ch = curl_init();
	curl_setopt_array($ch, [
		CURLOPT_URL => 'https://api.github.com/repos/JTimNolan/skylar-shopify-theme/pulls?head=JTimNolan:settings-theme-'.$theme['id'],
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_USERPWD => "JTimNolan:".$_ENV['GITHUB_TOKEN'],
		CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
		CURLOPT_USERAGENT => 'Skylar App',
	]);
	$res = curl_exec($ch);
	$pull_request = json_decode($res, true);

	if(empty($pull_request)){
		echo "Creating pull request".PHP_EOL;
		$ch = curl_init();
		curl_setopt_array($ch, [
			CURLOPT_URL => 'https://api.github.com/repos/JTimNolan/skylar-shopify-theme/pulls',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_USERPWD => "JTimNolan:".$_ENV['GITHUB_TOKEN'],
			CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => json_encode([
				"title" => "Settings update: ".trim(str_ireplace('[pullme]', '', $theme['name'])),
				"head" => "settings-theme-".$theme['id'],
				"base" => "master",
			]),
			CURLOPT_USERAGENT => 'Skylar App',
		]);
		$res = curl_exec($ch);
		$pull_request = json_decode($res, true);
	} else {
		echo "Found pull request".PHP_EOL;
		$pull_request = $pull_request[0];
	}
	$new_name = $theme['name'];
	$new_name = trim(str_ireplace('[pullme]', '', $new_name));
	$new_name = trim(preg_replace('/^PR#\d+/i', '', $new_name));
	$new_name = 'PR#'.$pull_request['number'].' '.$new_name;
	$theme = $sc->put('/admin/api/2019-10/themes/'.$theme['id'].'.json', [
		'theme' => [
			'name' => $new_name
		]
	]);
	echo $theme['name'].PHP_EOL;
	echo insert_update_theme($db, $theme);
}

// Check if over 90 themes, delete old ones if so
$themes = $sc->get('/admin/api/2020-01/themes.json', [
	'limit' => 250,
]);

echo count($themes);
print_r($themes);

if(count($themes) > 90){
	// filter out non-pr themes
	$themes = array_filter($themes, function($theme){
		return strpos($theme['name'], 'PR#') === 0;
	});
	foreach($themes as $index=>$theme){
		$matches = [];
		preg_match('/PR#(\d+)/', $theme['name'], $matches);
		$themes[$index]['pr_id'] = $matches[1];
	}
	$themes = usort($themes, function($a, $b){
		return ($a['pr_id'] > $b['pr_id']) ? 1 : -1;
	});
	print_r($themes);
}