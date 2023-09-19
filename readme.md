# Laravel Test Support

Laravel Test Support is an extension package developed for the Laravel project to help simplify writing PHPUnit tests

## Installation

Require this package with composer using the following command:
```bash
composer config repositories.phpunit-snapshot-assertions vcs https://github.com/baijunyao/phpunit-snapshot-assertions
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
    use CreatesApplication;
}
```

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
