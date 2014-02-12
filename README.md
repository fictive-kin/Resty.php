# Resty.php

A simple PHP library for doing RESTful HTTP stuff. Does *not* require the curl extension.

## Example

```php
<?php
require __DIR__."/Resty.php";

$resty = new Resty();
$resty->debug(true);
$resty->setBaseURL('https://gimmebar.com/api/v1/');
$resp = $resty->get('public/assets/funkatron');
print_r($resp);
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
