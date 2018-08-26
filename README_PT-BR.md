<p align="center">
    <a href="https://medoo.in" target="_blank"><img src="https://cloud.githubusercontent.com/assets/1467904/19835326/ca62bc36-9ebd-11e6-8b37-7240d76319cd.png"></a>
</p>

<p align="center">
    <a href="https://packagist.org/packages/catfan/medoo"><img alt="Total de Downloads" src="https://poser.pugx.org/catfan/medoo/downloads"></a>
    <a href="https://packagist.org/packages/catfan/medoo"><img alt="Última versão estável" src="https://poser.pugx.org/catfan/medoo/v/stable"></a>
    <a href="https://packagist.org/packages/catfan/medoo"><img alt="Licença" src="https://poser.pugx.org/catfan/medoo/license"></a>
</p>

> O Framework PHP de peso leve para acelerar desenvolvimento

## Características

* **Leve** - Menos que 100 KB, portável com apenas um arquivo

* **Fácil** - Extremamente fácil de aprender e usar, construção amigável

* **Poderoso** - Suporta várias queries SQL comuns e complexas, data mapping e previne SQL injection

* **Compatível** - Suporte todos os bancos SQL, incluindo MySQL, MSSQL, SQLite, MariaDB, PostgreSQL, Sybase, Oracle e mais

* **Amigável** - Funciona bem com cada framework PHP, como Laravel, Codeigniter, Yii, Slim, e frameworks que suportam extensões singleton ou composer

* **Livre** - Sob a licença MIT, você pode usá-lo em qualquer lugar que você queira

## Requerimentos

PHP 5.4+ e extensão PDO instalada

## Começando

### Instale via composer

Adicione Medoo ao arquivo de configuração composer.json.
```
$ composer require catfan/Medoo
```

E atualize o composer
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

## Guia de Contribuição

Na maior parte das vezes, Medoo está usando a branch Desenvolvimento para adicionar recursos e corrigir bugs, e a branch será mesclada à branch master quando for publicada uma versão pública. Para contribuir, envie seu código para a branch de desenvolvimento e inicie uma pull request nela.

Na branch de desenvolvimento, cada commit é inicado por `[fix]`, `[feature]` ou `[update]` para indicar o tipo de alteração.

Mantenha simples e mantenha limpo

## Licença

Medoo está sob a licença MIT.

## Links

* Site oficial: [https://medoo.in](https://medoo.in)

* Documentação: [https://medoo.in/doc](https://medoo.in/doc)