<p align="center">
    <a href="https://medoo.in" target="_blank"><img src="https://cloud.githubusercontent.com/assets/1467904/19835326/ca62bc36-9ebd-11e6-8b37-7240d76319cd.png"></a>
</p>

<p align="center">
    <a href="https://packagist.org/packages/catfan/medoo"><img alt="Total Downloads" src="https://poser.pugx.org/catfan/medoo/downloads"></a>
    <a href="https://packagist.org/packages/catfan/medoo"><img alt="Latest Stable Version" src="https://poser.pugx.org/catfan/medoo/v/stable"></a>
    <a href="https://packagist.org/packages/catfan/medoo"><img alt="License" src="https://poser.pugx.org/catfan/medoo/license"></a>
    <img alt="Backers on Open Collective" src="https://opencollective.com/Medoo/backers/badge.svg">
	<img alt="Sponsors on Open Collective" src="https://opencollective.com/Medoo/sponsors/badge.svg"> 
</p>

> The Lightest PHP database framework to accelerate development

## Features

* **Lightweight** - Less than 100 KB, portable with only one file

* **Easy** - Extremely easy to learn and use, friendly construction

* **Powerful** - Supports various common and complex SQL queries, data mapping, and prevent SQL injection

* **Compatible** - Supports all SQL databases, including MySQL, MSSQL, SQLite, MariaDB, PostgreSQL, Sybase, Oracle and more

* **Friendly** - Works well with every PHP frameworks, like Laravel, Codeigniter, Yii, Slim, and framework which supports singleton extension or composer

* **Free** - Under MIT license, you can use it anywhere whatever you want

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
    'password' => 'your_password'
]);

// Enjoy
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

// [
//     {
//         "user_name" : "foo",
//         "email" : "foo@bar.com",
//     }
// ]
```

## Contribution Guides

For most of time, Medoo is using develop branch for adding feature and fixing bug, and the branch will be merged into master branch while releasing a public version. For contribution, submit your code to the develop branch, and start a pull request into it.

On develop branch, each commits are started with `[fix]`, `[feature]` or `[update]` tag to indicate the change.

Keep it simple and keep it clear.

## Contributors

This project exists thanks to all the people who contribute. 
<a href="graphs/contributors"><img src="https://opencollective.com/Medoo/contributors.svg?width=890&button=false" /></a>


## Backers

Thank you to all our backers! 🙏 [[Become a backer](https://opencollective.com/Medoo#backer)]

<a href="https://opencollective.com/Medoo#backers" target="_blank"><img src="https://opencollective.com/Medoo/backers.svg?width=890"></a>


## Sponsors

Support this project by becoming a sponsor. Your logo will show up here with a link to your website. [[Become a sponsor](https://opencollective.com/Medoo#sponsor)]

<a href="https://opencollective.com/Medoo/sponsor/0/website" target="_blank"><img src="https://opencollective.com/Medoo/sponsor/0/avatar.svg"></a>
<a href="https://opencollective.com/Medoo/sponsor/1/website" target="_blank"><img src="https://opencollective.com/Medoo/sponsor/1/avatar.svg"></a>
<a href="https://opencollective.com/Medoo/sponsor/2/website" target="_blank"><img src="https://opencollective.com/Medoo/sponsor/2/avatar.svg"></a>
<a href="https://opencollective.com/Medoo/sponsor/3/website" target="_blank"><img src="https://opencollective.com/Medoo/sponsor/3/avatar.svg"></a>
<a href="https://opencollective.com/Medoo/sponsor/4/website" target="_blank"><img src="https://opencollective.com/Medoo/sponsor/4/avatar.svg"></a>
<a href="https://opencollective.com/Medoo/sponsor/5/website" target="_blank"><img src="https://opencollective.com/Medoo/sponsor/5/avatar.svg"></a>
<a href="https://opencollective.com/Medoo/sponsor/6/website" target="_blank"><img src="https://opencollective.com/Medoo/sponsor/6/avatar.svg"></a>
<a href="https://opencollective.com/Medoo/sponsor/7/website" target="_blank"><img src="https://opencollective.com/Medoo/sponsor/7/avatar.svg"></a>
<a href="https://opencollective.com/Medoo/sponsor/8/website" target="_blank"><img src="https://opencollective.com/Medoo/sponsor/8/avatar.svg"></a>
<a href="https://opencollective.com/Medoo/sponsor/9/website" target="_blank"><img src="https://opencollective.com/Medoo/sponsor/9/avatar.svg"></a>



## License

Medoo is under the MIT license.

## Links

* Official website: [https://medoo.in](https://medoo.in)

* Documentation: [https://medoo.in/doc](https://medoo.in/doc)