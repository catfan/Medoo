## [Medoo](http://medoo.in)

> The Lightest PHP database framework to accelerate development

### Main Features

* **Lightweight** - Only 14KB with one file.

* **Easy** - Extremely easy to learn and use, friendly construction.

* **Powerful** - Support various common and complex SQL queries.

* **Compatible** - Support various SQL database, including MySQL, MSSQL, SQLite, MariaDB, Sybase, Oracle, PostgreSQL, Google Cloud SQL and more.

* **Security** - Prevent SQL injection.

* **Free** - Under MIT license, you can use it anywhere if you want.

### Get Started

```php
// Include Medoo (configured)
require_once 'medoo.php';

// Initialize
$database = new medoo();

// Enjoy
$database->insert('account', [
    'user_name' => 'foo'
    'email' => 'foo@bar.com',
    'age' => 25,
    'lang' => ['en', 'fr', 'jp', 'cn']
]);

// Or initialize via independent configuration
$database = new medoo([
    'database_type' => 'mysql',
    'database_name' => 'name',
    'server' => 'localhost',
    'username' => 'your_username',
    'password' => 'your_password',
]);

// For Google Cloud SQL
$database = new medoo([
    'database_type' => 'cloudsql',
    'database_name' => 'name',
    'server' => 'instance-id:database-id',
    'username' => 'your_username',
    'password' => 'your_password',
]);
```

### Google Cloud SQL Server Name

When using Google Cloud SQL, the "server" variable when initializing Medoo is in the form of "instance-id:database-id", for example, "regal-panther-523:db". This is listed as the "Instance ID" within the Google Developer Console's Cloud SQL administration interface.

### Contribution Guides

For most of time, Medoo is using develop branch for adding feature and fixing bug, and the branch will be merged into master branch while releasing a public version. For contribution, submit your code to the develop branch, and start a pull request into it.

On develop branch, each commits are started with `[fix]`, `[feature]` or `[update]` tag to indicate the change.

Keep it simple and keep it clear.

### License

Medoo is under the MIT License.

### Links

* Official website: [http://medoo.in](http://medoo.in)

* Documentation: [http://medoo.in/doc](http://medoo.in/doc)