<?php

require __DIR__."/Resty.php";

$resty = new Resty(array(
	// pass an anon func as a callback
	'onRequestLog'=>function($req) {
		var_dump($req);	
	})
);
$resty->enableDebugging(true);
$resty->setBaseURL('https://gimmebar.com/api/v0/');
$resp = $resty->get('public/assets/funkatron');
print_r($resp);