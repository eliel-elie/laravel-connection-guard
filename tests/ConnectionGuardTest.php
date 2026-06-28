<?php

use Elielelie\ConnectionGuard\Exceptions\ConnectionGuardException;
use Elielelie\ConnectionGuard\Facades\ConnectionGuard;
use Elielelie\ConnectionGuard\Rules\PreventMissingWhereRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    ConnectionGuard::withoutGuards(function () {
        // Initialize databases structure for testbench connection
        Schema::connection('testbench')->create('users', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        // Initialize databases structure for guarded connection
        Schema::connection('guarded')->create('users', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        // Initialize databases structure for guarded custom connection
        Schema::connection('guarded_custom')->create('users', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::connection('guarded_custom')->create('sessions', function ($table) {
            $table->string('id')->primary();
            $table->text('payload');
        });

        // Initialize databases structure for new guarded connections
        Schema::connection('guarded_ddl')->create('users', function ($table) {
            $table->increments('id');
            $table->string('name');
        });

        Schema::connection('guarded_procedure')->create('users', function ($table) {
            $table->increments('id');
            $table->string('name');
        });

        Schema::connection('guarded_preventive_massive')->create('users', function ($table) {
            $table->increments('id');
            $table->string('name');
        });
    });
});

afterEach(function () {
    ConnectionGuard::withoutGuards(function () {
        Schema::connection('testbench')->dropIfExists('users');
        Schema::connection('guarded')->dropIfExists('users');
        Schema::connection('guarded_custom')->dropIfExists('users');
        Schema::connection('guarded_custom')->dropIfExists('sessions');
        Schema::connection('guarded_ddl')->dropIfExists('users');
        Schema::connection('guarded_procedure')->dropIfExists('users');
        Schema::connection('guarded_preventive_massive')->dropIfExists('users');
    });
});

test('unguarded connection allows all operations', function () {
    $db = DB::connection('testbench');

    $db->table('users')->insert(['name' => 'Eliel']);
    expect($db->table('users')->count())->toBe(1);

    $db->table('users')->where('id', 1)->update(['name' => 'Eliel Ferreira']);
    expect($db->table('users')->first()->name)->toBe('Eliel Ferreira');

    $db->table('users')->where('id', 1)->delete();
    expect($db->table('users')->count())->toBe(0);

    Schema::connection('testbench')->dropIfExists('users');
    expect(Schema::connection('testbench')->hasTable('users'))->toBeFalse();
});

test('guarded connection blocks write dml operations', function () {
    $db = DB::connection('guarded');

    expect(fn () => $db->table('users')->insert(['name' => 'Eliel']))
        ->toThrow(ConnectionGuardException::class, "The SQL command 'insert' is not allowed.");
});

test('guarded connection blocks ddl operations', function () {
    expect(fn () => Schema::connection('guarded')->drop('users'))
        ->toThrow(ConnectionGuardException::class, "The DDL command 'drop' is not allowed.");
});

test('except tables allows writes on specified tables', function () {
    $db = DB::connection('guarded_custom');

    // Writing to sessions table should be allowed because it is in except_tables
    $db->table('sessions')->insert(['id' => 'sess_1', 'payload' => 'data']);
    expect($db->table('sessions')->count())->toBe(1);

    // Writing to users table should still be blocked
    expect(fn () => $db->table('users')->insert(['name' => 'Test']))
        ->toThrow(ConnectionGuardException::class, "The SQL command 'insert' is not allowed.");
});

test('prevent missing where rule blocks unrestricted updates and deletes', function () {
    config(['database.connections.where_guarded' => [
        'driver'   => 'sqlite',
        'database' => ':memory:',
        'prefix'   => '',
        'guards'   => [
            PreventMissingWhereRule::class,
        ],
    ]]);

    Schema::connection('where_guarded')->create('users', function ($table) {
        $table->increments('id');
        $table->string('name');
    });

    $db = DB::connection('where_guarded');

    // Test UPDATE without WHERE is blocked
    expect(fn () => $db->statement('UPDATE users SET name = "John"'))
        ->toThrow(ConnectionGuardException::class, 'Dangerous query detected: UPDATE without a WHERE clause is not allowed.');

    // Test DELETE without WHERE is blocked
    expect(fn () => $db->statement('DELETE FROM users'))
        ->toThrow(ConnectionGuardException::class, 'Dangerous query detected: DELETE without a WHERE clause is not allowed.');

    // Test UPDATE with WHERE is allowed by the guard
    $ok = true;

    try {
        $db->statement('UPDATE users SET name = "John" WHERE id = 1');
    } catch (ConnectionGuardException $e) {
        $ok = false;
    } catch (Exception $e) {
        // other exceptions are fine
    }
    expect($ok)->toBeTrue();
});

test('ddl guard blocks ddl but allows write operations', function () {
    $db = DB::connection('guarded_ddl');

    // DDL operations should be blocked
    expect(fn () => Schema::connection('guarded_ddl')->drop('users'))
        ->toThrow(ConnectionGuardException::class, "The DDL command 'drop' is not allowed.");

    // Write operations should be allowed
    $db->table('users')->insert(['name' => 'Eliel']);
    expect($db->table('users')->count())->toBe(1);
});

test('procedure guard blocks call and execute statements', function () {
    $db = DB::connection('guarded_procedure');

    // Procedure calls should be blocked
    expect(fn () => $db->statement('CALL my_procedure()'))
        ->toThrow(ConnectionGuardException::class, "Executing procedures via 'call' is not allowed.");

    // Regular operations should be allowed
    $db->table('users')->insert(['name' => 'Eliel']);
    expect($db->table('users')->count())->toBe(1);
});

test('preventive massive guard blocks drop and missing where clauses but allows insert and updates with where', function () {
    $db = DB::connection('guarded_preventive_massive');

    // DROP should be blocked
    expect(fn () => Schema::connection('guarded_preventive_massive')->drop('users'))
        ->toThrow(ConnectionGuardException::class, "The DDL command 'drop' is not allowed.");

    // UPDATE without WHERE should be blocked
    expect(fn () => $db->statement('UPDATE users SET name = "John"'))
        ->toThrow(ConnectionGuardException::class, 'Dangerous query detected: UPDATE without a WHERE clause is not allowed.');

    // Inserting should be allowed
    $db->table('users')->insert(['name' => 'Eliel']);
    expect($db->table('users')->count())->toBe(1);

    // Updating with WHERE should be allowed
    $db->table('users')->where('id', 1)->update(['name' => 'Eliel Ferreira']);
    expect($db->table('users')->first()->name)->toBe('Eliel Ferreira');
});
