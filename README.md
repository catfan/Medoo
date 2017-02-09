![](https://cloud.githubusercontent.com/assets/1467904/19835326/ca62bc36-9ebd-11e6-8b37-7240d76319cd.png)

## [Medoo](http://medoo.in)

[![Total Downloads](https://poser.pugx.org/catfan/medoo/downloads)](https://packagist.org/packages/catfan/medoo)
[![Latest Stable Version](https://poser.pugx.org/catfan/medoo/v/stable)](https://packagist.org/packages/catfan/medoo)
[![License](https://poser.pugx.org/catfan/medoo/license)](https://packagist.org/packages/catfan/medoo)

> The Lightest PHP database framework to accelerate development

## Main Features

* **Lightweight** - 26KB around with only one file.

* **Easy** - Extremely easy to learn and use, friendly construction.

* **Powerful** - Support various common and complex SQL queries, data mapping, and prevent SQL injection.

* **Compatible** - Support all SQL databases, including MySQL, MSSQL, SQLite, MariaDB, Sybase, Oracle, PostgreSQL and more.

* **Friendly** - Work well with every PHP frameworks, like Laravel, Codeigniter, Yii, Slim, and framework which supports singleton extension.

* **Free** - Under MIT license, you can use it anywhere if you want.

## Requirement

PHP 5.4+ and PDO extension installed

## Get Started

### Install via composer

Add Medoo to composer.json configuration file.
```
$ composer require catfan/Medoo
```

And update the composer
```
$ composer update
```

```php
// If you installed via composer, just use this code to requrie autoloader on the top of your projects.
require 'vendor/autoload.php';

// Using Medoo namespace
use Medoo\Medoo;

// Initialize
$database = new Medoo([
    'database_type' => 'mysql',
    'database_name' => 'name',
    'server' => 'localhost',
    'username' => 'your_username',
    'password' => 'your_password',
    'charset' => 'utf8'
]);

// Enjoy
$database->insert('account', [
    'user_name' => 'foo',
    'email' => 'foo@bar.com',
    'age' => 25,
    'lang' => ['en', 'fr', 'jp', 'cn']
]);
```

## Contribution Guides

For most of time, Medoo is using develop branch for adding feature and fixing bug, and the branch will be merged into master branch while releasing a public version. For contribution, submit your code to the develop branch, and start a pull request into it.

On develop branch, each commits are started with `[fix]`, `[feature]` or `[update]` tag to indicate the change.

Keep it simple and keep it clear.

## License

Medoo is under the MIT license.

## Links

* Official website: [http://medoo.in](http://medoo.in)

* Documentation: [http://medoo.in/doc](http://medoo.in/doc)