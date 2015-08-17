# MyPDO

MyPDO is a lightweight wrapper for PHP's PDO to add a few features missing from the original PDO:
 - Disconnection
 - Automatic reconnection (no more "2006 MySQL has gone away")
 - Nested transactions
 - Methods chainability

**MyPDO is designed for MySQL.**

This class will NOT add higher logic to PDO (such as lazy loading, data mapping, etc.). It is only intended to add low-level features.

## Basic example

This class can be used directly as a drop-in replacement for PHP's default PDO. There is nothing special to do.

```php
$dsn = 'mysql:dbname=testdb;host=127.0.0.1';
$user = 'dbuser';
$password = 'dbpass';

try {
	$dbh = new MyPDO($dsn, $user, $password);
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}
```

Complete doc is available in [the wiki](https://github.com/rlanvin/php-mypdo/wiki).

## Requirements

- PHP >= 5.3

## Installation

The recommended way is to install the lib [through Composer](http://getcomposer.org/).

Just add this to your `composer.json` file (change the version by the release you want, or use `dev-master` for the development version):

```JSON
{
    "require": {
        "rlanvin/php-mypdo": "1.*"
    }
}
```

Then run `composer install` or `composer update`.

Now you can use the autoloader, and you will have access to the library:

```php
<?php
require 'vendor/autoload.php';
```

### Alternative method

You can just download `src/MyPDO.php` and `src\MyPDOStatement.php` (if you want to use prepared statements) and require them.

## Documentation

Complete doc is available in [the wiki](https://github.com/rlanvin/php-mypdo/wiki).

## Contribution

Feel free to contribute! Just create a new issue or a new pull request.

## License

This library is released under the MIT License.
