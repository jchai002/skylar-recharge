<?php
require_once(__DIR__.'/../includes/config.php');

$address_ids = [
32000101,
33801384,
33678220,
35891988,
38198820,
32913271,
37234536,
35051245,
33829733,
34049193,
35702049,
32890419,
30271676,
30498536,
35815185,
30391156,
30338944,
34325932,
30711502,
33489492,
30318988,
33584298,
35236961,
33993010,
32294769,
35538110,
36651035,
31595268,
35640708,
35645175,
30550148,
30872124,
33064094,
33329526,
37411903,
37532448,
37698572,
37867913,
38023958,
38312003,
38587239,
38977730,
37879781,
30531980,
37473968,
35203770,
36543457,
31877466,
37075451,
32000101,
33801384,
33678220,
35891988,
38198820,
32913271,
32854287,
37234536,
35051245,
33829733,
34049193,
35702049,
32890419,
30271676,
30498536,
35815185,
30391156,
30338944,
34325932,
30711502,
33489492,
30318988,
33584298,
35236961,
33993010,
32294769,
35538110,
36651035,
31595268,
35640708,
35645175,
30550148,
30872124,
33064094,
33329526,
37411903,
37532448,
37698572,
37867913,
38023958,
38312003,
38587239,
38977730,
39507525,
32893078
];

foreach($address_ids as $address_id){
	$res = $rc->put('/addresses/'.$address_id, [
		'shipping_lines_override' => [[
			'price' => "0",
			'code' => "AKHI Legacy Shipping",
			'title' => "AKHI Legacy Shipping",
		]],
	]);
	print_r($res);
//	die();
}