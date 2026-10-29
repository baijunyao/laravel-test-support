# Laravel Test Support

Laravel Test Support is an extension package developed for the Laravel project to help simplify writing PHPUnit or Pest tests

## Compatibility

The package allows Illuminate 7–13, Pest 3–5, and PHPUnit 11–13. Pest determines the compatible PHPUnit major version: Pest 3 uses PHPUnit 11, Pest 4 uses PHPUnit 12, and Pest 5 uses PHPUnit 13.

The package requires PHP 8.2 or newer. Laravel 13 requires PHP 8.3 or newer; Pest 5 requires PHP 8.4 or newer. Composer selects a compatible combination for the current PHP version.

## Installation

Require this package with composer using the following command:
```bash
composer require baijunyao/laravel-test-support
```

## Usage

Modify the *tests/TestCase.php* file
```diff
<?php

namespace Tests;

- use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
+ use Baijunyao\LaravelTestSupport\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
}
```

After each test, the base test case truncates every table written by that test. If a matching table seeder exists, it then runs the seeder to restore the table's baseline data. Foreign key constraints are temporarily disabled during cleanup.

Modify the *phpunit.xml*  file
```diff
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
>
+   <extensions>
+       <bootstrap class="Baijunyao\LaravelTestSupport\Extensions\CreateRandomDatabaseExtension"/>
+   </extensions>
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>app</directory>
        </include>
    </source>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="BCRYPT_ROUNDS" value="4"/>
        <env name="CACHE_DRIVER" value="array"/>
        <!-- <env name="DB_CONNECTION" value="sqlite"/> -->
        <!-- <env name="DB_DATABASE" value=":memory:"/> -->
+       <env name="DB_HOST" value="127.0.0.1"/>
        <env name="MAIL_MAILER" value="array"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
        <env name="SESSION_DRIVER" value="array"/>
        <env name="TELESCOPE_ENABLED" value="false"/>
    </php>
</phpunit>

```
## Example
- [laravel-bjyblog](https://github.com/baijunyao/laravel-bjyblog/tree/master/tests)

## Package development

```bash
composer update --with-all-dependencies
composer test
```

Run the full suite with `composer test` or `./vendor/bin/pest`. It includes Pest test functions, so running `./vendor/bin/phpunit` directly cannot load the full suite. The Pest runner executes both the Pest tests and the PHPUnit test classes.

The test suite covers the nine resource snapshot helpers, date/cookie normalization, SQLite dirty-table cleanup, and database subscriber success and failure paths. Snapshot baselines are stored in `tests/.pest/snapshots`.

Verified on Laravel 13.39.0 (each combination: 16 tests, 46 assertions):

| PHP | Pest | PHPUnit |
| --- | --- | --- |
| 8.3.33 | 3.8.7 | 11.5.56 |
| 8.3.33 | 4.7.8 | 12.5.33 |
| 8.5.6 | 5.3.4 | 13.4.5 |

MySQL/PostgreSQL database creation and removal are tested using mocks, without connecting to a real database server.
