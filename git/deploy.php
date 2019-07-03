<?php
print_r($_REQUEST);
if(empty($_REQUEST['ref'])){
	echo "Empty Payload";
	exit;
}
if(strpos(getcwd(), 'production') !== false && $_REQUEST['ref'] != 'refs/heads/master'){
	echo "Production / not master branch";
	exit;
}
if(strpos(getcwd(), 'staging') !== false && $_REQUEST['ref'] != 'refs/heads/staging'){
	echo "Staging / not staging branch";
	exit;
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