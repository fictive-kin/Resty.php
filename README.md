# Resty.php

A simple PHP library for doing RESTful HTTP stuff. Does *not* require the curl extension.

## Installation

Use [composer](https://getcomposer.org/) to install Resty:

1. Install composer into your project:
```
curl -s https://getcomposer.org/installer | php
```

Create a `composer.json` file in your project root:

``` json
{
    "require": {
        "resty/resty": "@stable"
    }
}
```

Install via composer:

```
php composer.phar install
```


## Example

``` php
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
```

*Output*

```
$resp['status']:
int(200)

$resp['headers']:
array(6) {
  ["Access-Control-Allow-Origin"]=>
  string(1) "*"
  ["Content-Type"]=>
  string(16) "application/json"
  ["Date"]=>
  string(29) "Thu, 13 Feb 2014 15:09:33 GMT"
  ["Server"]=>
  string(15) "gunicorn/0.17.4"
  ["Content-Length"]=>
  string(3) "225"
  ["Connection"]=>
  string(5) "Close"
}

$resp['body']:
object(stdClass)#3 (1) {
  ["headers"]=>
  object(stdClass)#4 (5) {
    ["Host"]=>
    string(11) "httpbin.org"
    ["Connection"]=>
    string(5) "close"
    ["Content-Type"]=>
    string(33) "application/x-www-form-urlencoded"
    ["X-Request-Id"]=>
    string(36) "259dfd8e-6a24-4e77-ae83-9ce50c29ea78"
    ["User-Agent"]=>
    string(11) "Resty 0.6.0"
  }
}

$resp['body_raw']:
string(225) "{
  "headers": {
    "Host": "httpbin.org",
    "Connection": "close",
    "Content-Type": "application/x-www-form-urlencoded",
    "X-Request-Id": "259dfd8e-6a24-4e77-ae83-9ce50c29ea78",
    "User-Agent": "Resty 0.6.0"
  }
}"
```

## Coding Standards

For PHP code, I've decided to adopt [the PSR-2 standard](http://www.php-fig.org/psr/psr-2/).

To automatically check against the PSR-2 standard in Sublime Text, you can use the [Phpcs package](https://sublime.wbond.net/packages/Phpcs) or the [SumblimeLinter-phpcs plugin](https://sublime.wbond.net/packages/SublimeLinter-phpcs). I use the former at the moment. I have the following in *Preferences > Package Settings > PHP Code Sniffer Settings - User*:

    {
        "phpcs_executable_path": "/usr/local/Cellar/php55/5.5.8/bin/phpcs",
        "phpcs_additional_args": {
            "--standard": "PSR2",
            "-n": ""
        }
    }
