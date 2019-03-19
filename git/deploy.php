<?php
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
