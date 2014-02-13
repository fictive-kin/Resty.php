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


/**
 * Resty::postJson() encodes the object or array as JSON and sends it as 
 * the request body.
 */
$to_json = array(
                 "foo"=>array(
                              "bar"=>"baz",
                              "bee"=>"bim",
                              "bop"=>23
                              )
                 );
$resp = $resty->postJson('post', $to_json);

echo "\n\$resp['body']->json:\n";
var_dump($resp['body']->json);
