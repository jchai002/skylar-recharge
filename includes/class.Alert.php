<?php

class Alert {

	const ALERT_TYPE_REVENUE = 1;

	private $db;
	private $alert_type;

	function __construct(PDO $db, $alert_type){
		$this->db = $db;
		$this->alert_type = $alert_type;

	}

	public function send($msg = ""){
	}

}


if($percent_change['count'] < -50 || $percent_change['revenue'] < -50){
	$to = implode(', ',[
		'tim@timnolansolutions.com',
		'sarah@skylar.com',
		'cat@skylar.com',
	]);
	$msg = "Order count has changed by " . number_format($percent_change['count'],2) . "% over the last hour.
Revenue has changed by " . number_format($percent_change['revenue'],2) . "% over the last hour.";
	$headers = [
		'From' => 'Skylar Alerts <alerts@skylar.com>',
		'Reply-To' => 'tim@timnolansolutions.com',
		'X-Mailer' => 'PHP/' . phpversion(),
	];

	if($smother_message){
		echo "Smothering Alert";
	} else {
		echo "Sending Alert: ".PHP_EOL.$msg.PHP_EOL;

		mail($to, "ALERT: Sales Decline", $msg
		//		,implode("\r\n",$headers)
		);

		$alert_sent = true;
	}
}
