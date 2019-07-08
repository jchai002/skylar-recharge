<?php
$hook_data = file_get_contents('php://input');
//echo $hook_data.PHP_EOL;
if(!empty($hook_data)){
	$hook_data = json_decode($hook_data, true);
}
if(empty($hook_data)){
	echo "Empty Payload ".json_last_error_msg();
} else {
//	print_r($hook_data);
	if(strpos(getcwd(), 'production') !== false && $hook_data['ref'] != 'refs/heads/master'){
		echo "Production / not master branch";
		exit;
	}
	if(strpos(getcwd(), 'staging') !== false && $hook_data['ref'] != 'refs/heads/staging'){
		echo "Staging / not staging branch";
		exit;
	}
}
$commands = [
	'echo $PWD',
	'whoami',
	'sudo -u deploy /usr/bin/git pull',
	'git status',
];
foreach($commands as $command){
	$tmp = shell_exec("$command 2>&1");
	echo "> $command ".PHP_EOL."< ".htmlentities(trim($tmp)) . "\n";
}