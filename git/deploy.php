<?php
$hook_data = file_get_contents('php://input');
echo $hook_data;
if(!empty($hook_data)){
	$hook_data = json_decode($hook_data, true);
}
if(!empty($hook_data)){
	if(strpos(getcwd(), 'production') !== false && $hook_data['ref'] != 'refs/heads/master'){
		echo "Production / not master branch";
		exit;
	}
	if(strpos(getcwd(), 'staging') !== false && $hook_data['ref'] != 'refs/heads/staging'){
		echo "Staging / not staging branch";
		exit;
	}
} else {
	echo "Empty Payload";
}
$commands = [
	'echo $PWD',
	'whoami',
	'sudo -u deploy /usr/bin/git pull',
	'git status',
];
foreach($commands as $command){
	$tmp = shell_exec("$command 2>&1");
	echo "$command ".htmlentities(trim($tmp)) . "\n";
}