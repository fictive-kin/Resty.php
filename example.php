<?php
require "vendor/autoload.php";

use Resty\Resty;

$resty = new Resty();
$resty->setBaseURL('http://httpbin.org/');
$resp = $resty->get('headers');

echo "\n\$resp['status']:\n";
var_dump($resp['status']);

echo "\n\$resp['headers']:\n";
var_dump($resp['headers']);

echo "\n\$resp['body']:\n";
var_dump($resp['body']);

echo "\n\$resp['body_raw']:\n";
var_dump($resp['body_raw']);
