<?php

require dirname(__FILE__) . "/../vendor/autoload.php";

use \FUnit as fu;

use \Resty\Resty;

define("HTTPBIN_URL", "http://httpbin.org/");

/**
 * this is an error -> exception handler that is part of a super hack job here
 * to detect if errors were suppressed or not. Lame.
 */
function exception_error_handler($errno, $errstr, $errfile, $errline)
{
    if (error_reporting() === 0) {
        return;
    }
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}

fu::setup(function () {
    fu::fixture('resty', new Resty());
});

fu::teardown(function () {
    fu::reset_fixtures();
});

fu::test('quacks like a duck', function () {
    $r = fu::fixture('resty');
    fu::ok($r instanceof Resty, "is a Resty object");
    fu::strict_equal($r->getBaseUrl(), null, "Base url is blank");
    fu::equal($r->getUserAgent(), 'Resty ' . Resty::VERSION, "Default user agent");
    $r->setUserAgent('Poop');
    fu::equal($r->getUserAgent(), 'Poop', "Poop user agent");
});


fu::test('Un-silence fopen test', function () {

    set_error_handler('exception_error_handler');

    $r = fu::fixture('resty');
    $r->silenceFopenWarning(false);

    try {
        $r->get('http://fai9rp9whqrp9b8hqp98bhpwohropsrihbpohtpowhi/');
    } catch (\ErrorException $e) {
        fu::ok(is_string($e->getMessage()), "ErrorException thrown -- not silenced");
        restore_error_handler();
    }

    restore_error_handler();

});


fu::test('Silence fopen test', function () {

    set_error_handler('exception_error_handler');

    $r = fu::fixture('resty');
    $r->silenceFopenWarning(true);

    try {
        $r->get('http://fai9rp9whqrp9b8hqp98bhpwohropsrihbpohtpowhi/');
    } catch (\ErrorException $e) {
        print $e->getMessage();
        fu::fail("ErrorException thrown -- not silenced");
        restore_error_handler();
    }

    fu::ok(true, "Error silenced");
    restore_error_handler();
});


fu::test('Raise fopen exception', function () {

    $r = fu::fixture('resty');
    $r->silenceFopenWarning(true);
    $r->raiseFopenException(true);

    try {
        $r->get('http://fai9rp9whqrp9b8hqp98bhpwohropsrihbpohtpowhi/');
    } catch (\Exception $e) {
        fu::ok(is_string($e->getMessage()), "Exception thrown");
        $r->raiseFopenException(false);
    }

    $r->raiseFopenException(false);

});


fu::test('Don\'t raise fopen exception', function () {

    $r = fu::fixture('resty');
    $r->silenceFopenWarning(true);
    $r->raiseFopenException(false);

    try {
        $r->get('http://fai9rp9whqrp9b8hqp98bhpwohropsrihbpohtpowhi/');
    } catch (\Exception $e) {
        fu::fail("Exception thrown");
        $r->silenceFopenWarning(false);
    }

    fu::ok(true, "No exception thrown");
    $r->silenceFopenWarning(false);

});


fu::test('gimme bar requests and responses', function () {

    $r = fu::fixture('resty');
    $r->setBaseURL('https://gimmebar.com/api/v1/');
    $resp = $r->get('public/assets/funkatron');
    $req  = $r->getLastRequest();


    // request assertions
    $req_opts = $req['opts']['http'];

    fu::equal($req_opts['method'], 'GET', "GET method");

    fu::equal(
        $req['url'],
        'https://gimmebar.com/api/v1/public/assets/funkatron',
        "URL was correct"
    );

    fu::strict_equal(
        $req_opts['content'],
        null,
        "Body content is null"
    );

    fu::strict_equal(
        $req['querydata'],
        null,
        "Querydata is null"
    );

    fu::strict_equal(
        $req['options'],
        null,
        "options is null"
    );

    fu::ok(
        in_array('Connection: close', $req_opts['header']),
        "Connection: close was sent"
    );

    fu::equal(
        $req_opts['user_agent'],
        $r->getUserAgent(),
        "Default user agent"
    );

    fu::equal(
        $req_opts['timeout'],
        Resty::DEFAULT_TIMEOUT,
        "Default timeout was used"
    );

    fu::strict_equal(
        $req_opts['ignore_errors'],
        1,
        "errors were ignored in HTTP stream wrapper"
    );

    // respose assertions
    fu::ok(is_int($resp['status']), 'response status should be an integer');
    fu::equal($resp['status'], 200, 'response status should be 200');
    fu::ok($resp['body'] instanceof \StdClass, 'Response body should be a StdClass');

});

fu::test('httpin GET status responses', function () {

    $r = fu::fixture('resty');
    $r->setBaseURL(HTTPBIN_URL);

    $resp = $r->get('status/200');
    fu::strict_equal(200, $resp['status'], "Status is 200");

    $resp = $r->get('status/404');
    fu::strict_equal(404, $resp['status'], "Status is 404");

    $resp = $r->get('status/500');
    fu::strict_equal(500, $resp['status'], "Status is 500");

});

fu::test('httpin GET JSON decoding', function () {

    $r = fu::fixture('resty');
    $r->setBaseURL(HTTPBIN_URL);

    $r->jsonToArray(false);
    $resp = $r->get('get');
    fu::ok(is_object($resp['body']), "decoded JSON is object");
    fu::ok($resp['body'] instanceof \StdClass, 'Response body should be a StdClass');

    $r->jsonToArray(true);
    $resp = $r->get('get');
    fu::ok(is_array($resp['body']), "decoded JSON is array");
    $r->jsonToArray(false);

});

fu::test('httpin POST form stuff', function () {

    $r = fu::fixture('resty');
    $r->setBaseURL(HTTPBIN_URL);

    $resp = $r->post("post", array("foo"=>"bar", "foo2"=>"bar2"));
    fu::has("form", $resp['body']);
    fu::has("foo", $resp['body']->form, "foo is in form data");
    fu::has("foo2", $resp['body']->form, "foo2 is in form data");
    fu::strict_equal("bar", $resp['body']->form->foo, "foo value is correct");
    fu::strict_equal("bar2", $resp['body']->form->foo2, "foo2 value is correct");

});

fu::test('httpin POST JSON stuff', function () {

    $r = fu::fixture('resty');
    $r->setBaseURL(HTTPBIN_URL);

    $resp = $r->postJson("post", array("foo"=>"bar", "foo2"=>"bar2"));

    $req = $r->getLastRequest();
    fu::strict_equal('application/json', $req['headers']['Content-Type'], "Request Content-Type is application/json");

    fu::has("json", $resp['body']);
    fu::has("foo", $resp['body']->json, "foo is in json data");
    fu::has("foo2", $resp['body']->json, "foo2 is in json data");
    fu::strict_equal("bar", $resp['body']->json->foo, "foo value is correct");
    fu::strict_equal("bar2", $resp['body']->json->foo2, "foo2 value is correct");

});

fu::run();
