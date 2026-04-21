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

## Documentation

* [English](https://medoo.in)
* [العربية](https://medoo.in/ar)
* [Deutsch](https://medoo.in/de)
* [Español](https://medoo.in/es)
* [Français](https://medoo.in/fr)
* [हिन्दी](https://medoo.in/hi)
* [Italiano](https://medoo.in/it)
* [日本語](https://medoo.in/ja)
* [한국어](https://medoo.in/ko)
* [Português](https://medoo.in/pt-BR)
* [Русский](https://medoo.in/ru)
* [ไทย](https://medoo.in/th)
* [Українська](https://medoo.in/uk)
* [Tiếng Việt](https://medoo.in/vi)
* [简体中文](https://medoo.in/zh-Hans)
* [繁體中文](https://medoo.in/zh-Hant)

## Features

* **Lightweight** - A lightweight single-file package that keeps dependencies to a minimum.

* **Easy** - A clean, intuitive API that helps you get started quickly.

* **Powerful** - Designed for complex SQL, data mapping, and prepared statements without sacrificing readability.

* **Compatible** - Works smoothly with MySQL, MariaDB, PostgreSQL, SQLite, MSSQL, Oracle, Sybase, and more.

* **Friendly** - Fits naturally into Laravel, CodeIgniter, Yii, Slim, and other PHP frameworks.

* **Free** - Released under the MIT license and free to use in personal or commercial projects.

## Requirements
- PHP 7.3 or later
- PDO extension enabled

## Get Started
### Install via composer
Add Medoo to the `composer.json` configuration file.
```bash
$ composer require catfan/medoo
```

Then update Composer
```bash
$ composer update
```

```php
// Load Composer's autoloader.
require 'vendor/autoload.php';

// Import the Medoo namespace.
use Medoo\Medoo;

// Create a database connection.
$database = new Medoo([
    'type' => 'mysql',
    'host' => 'localhost',
    'database' => 'name',
    'username' => 'your_username',
    'password' => 'your_password'
]);

// Insert data.
$database->insert('account', [
    'user_name' => 'foo',
    'email' => 'foo@bar.com'
]);

// Retrieve data.
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

## Contribution Guidelines
Before submitting a pull request, ensure compatibility with multiple database engines and include unit tests when possible.

### Testing & Code Style
- Run `phpunit tests` to execute unit tests.
- Use `php-cs-fixer fix` to enforce code style consistency.

### Commit Message Format
Each commit should begin with a tag indicating the type of change:

- `[fix]` for bug fixes
- `[feature]` for new features
- `[update]` for improvements

Keep contributions simple and well-documented.

## License
Medoo is released under the [MIT](https://opensource.org/licenses/MIT) License.

## Links
* Official website: [https://medoo.in](https://medoo.in)
* X.com: [https://x.com/MedooPHP](https://x.com/MedooPHP)
* Open Collective: [https://opencollective.com/medoo](https://opencollective.com/medoo)

## [More Products We Build]
### Gear Browser - AI Extension Web Browser
- [Website](https://gear4.app)
- [App Store](https://apps.apple.com/us/app/id1458962238)

[![Gear Browser](https://github.com/user-attachments/assets/9dfaf39e-8e79-4ef2-b2dd-f7af87a729c0)](https://gear4.app)