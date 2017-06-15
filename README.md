# Rapture PHP Container

[![PhpVersion](https://img.shields.io/badge/php-5.4-orange.svg?style=flat-square)](#)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](#)

PHP simple DI container with reflection.

## Requirements

- PHP v5.4
- php-pcre

## Install

```
composer require iuliann/rapture-container
```

## Quick start

```php
$container = Container::instance('namespace'); // optional namespace

// store
$container['request'] = Request::fromGlobals();

// fetch
$container['request']->getUri()->getPath();

// reflection
Container::instance()['\Demo\User']; // runs reflection and caches result

// no reflection on 2nd run
Container::instance()->getNew('\Demo\User'); // get a new instance
```

## About

### Author

Iulian N. `rapture@iuliann.ro`

### Testing

```
cd ./test && phpunit
```

### License

Rapture PHP Container is licensed under the MIT License - see the `LICENSE` file for details.
