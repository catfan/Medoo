<p align="center">
    <a href="https://medoo.in" target="_blank"><img src="https://cloud.githubusercontent.com/assets/1467904/19835326/ca62bc36-9ebd-11e6-8b37-7240d76319cd.png"></a>
</p>

<p align="center">
    <a href="https://github.com/catfan/Medoo/actions"><img alt="Build Status" src="https://github.com/catfan/Medoo/actions/workflows/php.yml/badge.svg"></a>
    <a href="https://packagist.org/packages/catfan/medoo"><img alt="Total Downloads" src="https://poser.pugx.org/catfan/medoo/downloads"></a>
    <a href="https://packagist.org/packages/catfan/medoo"><img alt="Latest Stable Version" src="https://poser.pugx.org/catfan/medoo/v/stable"></a>
    <a href="https://packagist.org/packages/catfan/medoo"><img alt="License" src="https://poser.pugx.org/catfan/medoo/license"></a>
    <a href="https://opencollective.com/medoo"><img alt="Backers on Open Collective" src="https://opencollective.com/Medoo/backers/badge.svg"></a>
    <a href="https://opencollective.com/medoo"><img alt="Sponsors on Open Collective" src="https://opencollective.com/Medoo/sponsors/badge.svg"> </a>
</p>

> The lightweight PHP database framework to accelerate development.

## Features

* **Lightweight** - Portable with only one file.

* **Easy** - Easy to learn and use, with a friendly construction.

* **Powerful** - Supports various common and complex SQL queries, data mapping and prevents SQL injection.

* **Compatible** - Supports MySQL, MSSQL, SQLite, MariaDB, PostgreSQL, Sybase, Oracle, and more.

* **Friendly** - Works well with every PHP framework, such as Laravel, Codeigniter, Yii, Slim, and frameworks that support singleton extension or composer.

* **Free** - Under the MIT license, you can use it anywhere, for whatever purpose.

## Requirements

PHP 7.3+ and installed PDO extension.

## Get Started

### Install via composer

Add Medoo to the composer.json configuration file.
```
$ composer require catfan/medoo
```

And update the composer
```
$ composer update
```

```php
// Require Composer's autoloader.
require 'vendor/autoload.php';

// Use the Medoo namespace.
use Medoo\Medoo;

// Connect to the database.
$database = new Medoo([
    'type' => 'mysql',
    'host' => 'localhost',
    'database' => 'name',
    'username' => 'your_username',
    'password' => 'your_password'
]);

$database->insert('account', [
    'user_name' => 'foo',
    'email' => 'foo@bar.com'
]);

$data = $database->select('account', [
    'user_name',
    'email'
], [
    'user_id' => 50
]);

echo json_encode($data);

// [{
//    "user_name" : "foo",
//    "email" : "foo@bar.com",
// }]
```

## Contribution Guides

Before starting a new pull request, please ensure compatibility with other databases and write unit tests whenever possible.

Run `phpunit tests` for unit testing and `php-cs-fixer fix` to fix code style.

Each commit should start with a tag indicating the type of change: `[fix]`, `[feature]`, or `[update]`.

Please keep it simple and keep it clear.

## License

Medoo is released under the MIT license.

## Links

* Official website: [https://medoo.in](https://medoo.in)

* Documentation: [https://medoo.in/doc](https://medoo.in/doc)

* Twitter: [https://twitter.com/MedooPHP](https://twitter.com/MedooPHP)

* Open Collective: [https://opencollective.com/medoo](https://opencollective.com/medoo)

## Support Our Other Product
[Gear Browser - Web Browser for Geek](https://gear4.app)

[![Gear Browser](https://github-production-user-asset-6210df.s3.amazonaws.com/1467904/240102839-a597972c-458a-4f0e-9ef8-d4ad10ba0690.png)](https://gear4.app)