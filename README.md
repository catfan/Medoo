Medoo
=====

The Lightest PHP database framework to accelerate development

Main Features
----------------
### Lightweight ###
Only 7.8KB, one file included.

### Easy ###
Extremely easy to learn and use, friendly construction.

### Powerful ###
Support various common SQL queries.

### Compatible ###
Support various SQL database, including MySQL, MSSQL, SQLite and more.

### Security ###
Prevent SQL injection.

### Free ###
Under MIT license, you can use it anywhere if you want.

Usage
-------
```
$database = new medoo( string $database_type , string $database_name [, string $database_username = null [, string $database_password = null [, string $database_server = 'localhost' ]]] );
```

Get Started
-------------
```
// Include Medoo
require_once 'medoo.php';

// Initialize
$database = new medoo( 'mysql' ,  'my_database_name' , 'my_database_username' , 'my_database_password' );

// Enjoy
$database->insert('account', [
	'user_name' => 'foo'
	'email' => 'foo@bar.com',
	'age' => 25,
	'lang' => ['en', 'fr', 'jp', 'cn']
]);
```

Links
------
Official website: [http://medoo.in](http://medoo.in)

Documentation: [http://medoo.in/doc](http://medoo.in/doc)