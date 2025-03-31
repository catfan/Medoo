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

* **Lightweight** - Single-file framework with minimal dependencies.

* **Easy** - Simple and intuitive API for quick integration.

* **Powerful** - Supports complex SQL queries, data mapping, and SQL injection prevention.

* **Compatible** - Works with MySQL, MariaDB, PostgreSQL, SQLite, MSSQL, Oracle, Sybase, and more.

* **Friendly** - Integrates seamlessly with Laravel, CodeIgniter, Yii, Slim, and other PHP frameworks.

* **Free** - Licensed under MIT, free to use for any purpose.

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
// Require Composer's autoloader
require 'vendor/autoload.php';

// Import Medoo namespace
use Medoo\Medoo;

// Initialize database connection
$database = new Medoo([
    'type' => 'mysql',
    'host' => 'localhost',
    'database' => 'name',
    'username' => 'your_username',
    'password' => 'your_password'
]);

// Insert data
$database->insert('account', [
    'user_name' => 'foo',
    'email' => 'foo@bar.com'
]);

// Retrieve data
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

Medoo is released under the MIT License.

## Links

* Official website: [https://medoo.in](https://medoo.in)

* Documentation: [https://medoo.in/doc](https://medoo.in/doc)

* Twitter: [https://twitter.com/MedooPHP](https://twitter.com/MedooPHP)

* Open Collective: [https://opencollective.com/medoo](https://opencollective.com/medoo)

## Support Our Other Product
[Gear Browser - Web Browser for Geek](https://gear4.app)

[![Gear Browser](https://github.com/catfan/Medoo/assets/1467904/bc5059d4-6a2d-4bbf-90d9-a9f71bae3335)](https://gear4.app)