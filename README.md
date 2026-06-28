# Laravel Connection Guard

[![Latest Version on Packagist](https://img.shields.io/packagist/v/eliel-elie/laravel-connection-guard.svg?style=flat-square)](https://packagist.org/packages/eliel-elie/laravel-connection-guard)
[![Total Downloads](https://img.shields.io/packagist/dt/eliel-elie/laravel-connection-guard.svg?style=flat-square)](https://packagist.org/packages/eliel-elie/laravel-connection-guard)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)

**Laravel Connection Guard** is a clean, robust, and elegant package designed to protect your database connections in Laravel. It allows you to intercept SQL queries at runtime and prevent unwanted actions (like writes to read-only replicas, schema updates in production, or accidental UPDATEs/DELETEs without WHERE clauses) before they reach your database.

---

## Key Features

-  **Native Interception**: Uses Laravel's native database connection hooks (`beforeExecuting`), ensuring blocked queries are never executed.
-  **Driver-Agnostic**: Works seamlessly with MySQL, PostgreSQL, SQLite, SQL Server, Oracle, and any other Laravel-supported database driver.
-  **Built-in Guards & Rules**:
  - `read-only`: Blocks all DML writes (`insert`, `update`, `delete`, `merge`, `replace`, `upsert`) and DDL structure modifications.
  - `ddl`: Blocks schema changes (DDL: `CREATE`, `DROP`, `ALTER`, `TRUNCATE`, `RENAME`).
  - `procedure`: Blocks procedure calls and executions (e.g., `CALL`, `EXECUTE`, `EXEC`).
  - `preventive-massive`: Prevents dangerous queries, specifically blocking `UPDATE` and `DELETE` without `WHERE` clauses and `DROP` commands.
-  **Flexible Configurations**: Easily exempt specific tables (`except_tables`) or procedures (`except_procedures`) from guards.
-  **Runtime Bypass (`withoutGuards`)**: Safely bypass validation for database migrations, seeds, or specific tasks.
-  **Extensible**: Easily register custom security rules and guards.

---

## Requirements

- PHP `^8.2`
- Laravel `^10.0` | `^11.0` | `^12.0` | `^13.0`

---

## Installation

You can install the package via Composer:

```bash
composer require eliel-elie/laravel-connection-guard
```

Optionally, publish the configuration file:

```bash
php artisan vendor:publish --tag="connection-guard-config"
```

The published file `config/connection-guard.php` maps convenient aliases to the guard classes:

```php
return [
    'guards' => [
        'read-only'          => \Elielelie\ConnectionGuard\Guards\ReadOnlyGuard::class,
        'ddl'                => \Elielelie\ConnectionGuard\Guards\DdlGuard::class,
        'procedure'          => \Elielelie\ConnectionGuard\Guards\ProcedureGuard::class,
        'preventive-massive' => \Elielelie\ConnectionGuard\Guards\PreventiveMassiveGuard::class,
    ],
];
```

---

## Usage

To start protecting your database connections, simply add the `guards` key to your connection configurations in `config/database.php`:

### Example 1: Read-Only Connection (Replica)

```php
// config/database.php
'connections' => [
    'mysql_replica' => [
        'driver' => 'mysql',
        'host' => env('DB_REPLICA_HOST', '127.0.0.1'),
        'database' => env('DB_DATABASE', 'forge'),
        // ...
        'guards' => [
            'read-only',
        ],
    ],
],
```

### Example 2: Connection with Exceptions for Specific Tables

If your application uses database-driven sessions (`database` session driver) or needs to write to an activity log table on a protected replica, use the array syntax to define exemptions:

```php
// config/database.php
'connections' => [
    'mysql_replica' => [
        'driver' => 'mysql',
        // ...
        'guards' => [
            'read-only' => [
                'except_tables' => ['sessions', 'activity_logs', 'migrations'],
            ],
        ],
    ],
],
```

### Example 3: Preventing Massive Updates, Deletes, and Drops

Block massive accidental table updates/deletions lacking filter conditions, and disable `DROP` commands on production connections:

```php
// config/database.php
'connections' => [
    'mysql_production' => [
        'driver' => 'mysql',
        // ...
        'guards' => [
            'preventive-massive',
        ],
    ],
],
```

### Example 4: Blocking Structural Schema Changes Only (DDL)

```php
// config/database.php
'connections' => [
    'mysql_app' => [
        'driver' => 'mysql',
        // ...
        'guards' => [
            'ddl',
        ],
    ],
],
```

### Example 5: Blocking Procedures with Custom Exemptions

Block all stored procedure calls except for specific ones required by your application:

```php
// config/database.php
'connections' => [
    'mysql_db' => [
        'driver' => 'mysql',
        // ...
        'guards' => [
            'procedure' => [
                'except_procedures' => ['sp_log_activity', 'sp_get_report'],
            ],
        ],
    ],
],
```

---

## Advanced Usage

### Bypassing Guards at Runtime (`withoutGuards`)

If your application needs to execute queries that are normally blocked by guards (e.g., during setup, migrations, or data seeders), wrap the execution in the `withoutGuards` method:

```php
use Elielelie\ConnectionGuard\Facades\ConnectionGuard;
use Illuminate\Support\Facades\Schema;

ConnectionGuard::withoutGuards(function () {
    // All guards will be disabled within this closure
    Schema::dropIfExists('old_table');
});
```

You can also toggle the guard state manually:

```php
use Elielelie\ConnectionGuard\Facades\ConnectionGuard;

ConnectionGuard::disable();

// Run unprotected operations...

ConnectionGuard::enable();
```

---

## Creating Custom Rules and Guards

You can extend the security system by creating custom rules or registering custom guards:

### Option 1: Creating a Custom SQL Rule (`SqlRule`)

Create a class that implements `Elielelie\ConnectionGuard\Contracts\SqlRule`:

```php
namespace App\Database\Rules;

use Elielelie\ConnectionGuard\Contracts\SqlRule;
use Elielelie\ConnectionGuard\Exceptions\ConnectionGuardException;

class BlockDropDatabaseRule implements SqlRule
{
    public function validate(string $sql): void
    {
        if (str_contains(strtolower($sql), 'drop database')) {
            throw new ConnectionGuardException("Action prohibited: Deleting databases is not allowed!");
        }
    }
}
```

Reference the rule directly in your connection guards:

```php
'guards' => [
    \App\Database\Rules\BlockDropDatabaseRule::class,
],
```

### Option 2: Extending the Manager with Custom Guards

Register a custom guard programmatically in the `boot` method of your `AppServiceProvider`:

```php
use Elielelie\ConnectionGuard\Facades\ConnectionGuard;
use Elielelie\ConnectionGuard\Contracts\Guard;
use Illuminate\Database\Connection;

ConnectionGuard::extend('custom-audit', function ($app, array $options) {
    return new class implements Guard {
        public function validate(Connection $connection, string $query, array $bindings = []): void
        {
            // Custom validation logic...
        }
    };
});
```

Now, apply it to any connection using its registered alias:

```php
'guards' => [
    'custom-audit',
],
```

---

## Testing

To run the Pest test suite:

```bash
vendor/bin/pest
```

To validate code formatting with Laravel Pint:

```bash
vendor/bin/pint
```

---

## License

This package is open-sourced software licensed under the [MIT License](LICENSE).
