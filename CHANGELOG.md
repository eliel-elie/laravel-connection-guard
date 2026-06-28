# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.1.0] - 2026-06-27

### Added

- Initial release of the Laravel Connection Guard package.
- **Connection Interception**:
  - Custom `ConnectionGuardDatabaseManager` extending Laravel's native `DatabaseManager` to automatically apply guards using connection `beforeExecuting` callbacks.
  - Custom `ConnectionGuardManager` for resolving, managing, and extending guards.
  - `ConnectionGuard` Facade to toggle validation status globally and handle programmatic extension.
- **Built-in Guards**:
  - `ReadOnlyGuard` (`read-only`): Blocks DML writes and DDL structure modifications.
  - `DdlGuard` (`ddl`): Blocks structural changes exclusively.
  - `ProcedureGuard` (`procedure`): Blocks stored procedure executions (`CALL`, `EXECUTE`, `EXEC`), with support for exempted procedures via the `except_procedures` option.
  - `PreventiveMassiveGuard` (`preventive-massive`): Aggregates safety blocks against `UPDATE`/`DELETE` without `WHERE` clauses and destuctive `DROP` commands.
- **Built-in Rules**:
  - `WriteRule`: Identifies insert, update, delete, merge, replace, and upsert commands.
  - `DdlRule`: Identifies create, alter, drop, truncate, and rename commands.
  - `ProcedureRule`: Identifies procedure call and execute statements.
  - `PreventMissingWhereRule`: Identifies update and delete commands lacking a WHERE clause.
- **Advanced Features**:
  - `except_tables` configuration to bypass validation for specific tables (useful for session database drivers, log tables, etc.).
  - `ConnectionGuard::withoutGuards(Closure $callback)` to dynamically bypass validation at runtime (ideal for migrations and database seeding).
- **Testing and Styling**:
  - Full Pest test suite with 100% coverage on guards, rules, exceptions, and configuration behaviors.
  - Integrated Laravel Pint configuration for code styling consistency.
