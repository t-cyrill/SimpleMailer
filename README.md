SimpleMailer
============

SimpleMailer is a lightweight PHP 5.3 library. (Support PHP 5.4 and PHP 5.5)
This is PHP mail function wrapper.

Installation
--------------------

Download the [`composer.phar`](http://getcomposer.org/composer.phar).

``` sh
$ curl -s http://getcomposer.org/installer | php
```

Run Composer: `php composer.phar install`

Usage
--------------------
```php
<?php
require 'vendor/autoload.php';

$mailer = new Net\SimpleMailer("\n");

$mailer->from($from)
     ->to($address)
     ->subject($subject)
     ->message($msg)
     ->attachment($file)
     ->send();
```


