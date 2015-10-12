# MyPDO

MyPDO is a wrapper that adds a few features missing from vanilla PDO:
 - Explicit disconnection
 - Automatic reconnection (no more _2006 MySQL server has gone away_)
 - Nested transactions
 - Methods chainability
 - Hidden password from the stack trace (in case of error)
 - Helpers methods (e.g. `ping()`)

**Important: MyPDO is designed for MySQL only.**

This class will not add higher logic to PDO (such as data mapping, etc.). It is only intended to add low-level features.

[![Build Status](https://travis-ci.org/rlanvin/php-mypdo.svg?branch=master)](https://travis-ci.org/rlanvin/php-mypdo)

## Basic example

This class is intented to be a drop-in replacement for PHP's default PDO. There is nothing special to do, just use `MyPDO` class instead of `PDO` and you're good to go.

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

You can just download `src/MyPDO.php` and `src/MyPDOStatement.php` (if you want to use prepared statements) and require them.

## Documentation

Complete doc is available in [the wiki](https://github.com/rlanvin/php-mypdo/wiki).

## Contribution

Feel free to contribute! Just create a new issue or a new pull request.

## License

This library is released under the MIT License.
