<?php

use Elielelie\ConnectionGuard\ConnectionGuardDatabaseManager;
use Elielelie\ConnectionGuard\Exceptions\ConnectionGuardException;
use Elielelie\ConnectionGuard\Facades\ConnectionGuard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Eloquent Model for testing
|--------------------------------------------------------------------------
|
| A minimal Eloquent model used exclusively within this test file to
| validate that guards are properly enforced on Eloquent operations.
|
*/

class EloquentTestUser extends Model
{
    protected $table = 'users';

    protected $guarded = [];
}

/*
|--------------------------------------------------------------------------
| Setup & Teardown
|--------------------------------------------------------------------------
*/

beforeEach(function () {
    ConnectionGuard::withoutGuards(function () {
        Schema::connection('guarded')->create('users', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::connection('guarded_custom')->create('users', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::connection('guarded_custom')->create('sessions', function ($table) {
            $table->string('id')->primary();
            $table->text('payload');
        });
    });
});

afterEach(function () {
    ConnectionGuard::withoutGuards(function () {
        Schema::connection('guarded')->dropIfExists('users');
        Schema::connection('guarded_custom')->dropIfExists('users');
        Schema::connection('guarded_custom')->dropIfExists('sessions');
    });
});

/*
|--------------------------------------------------------------------------
| Connection Resolver
|--------------------------------------------------------------------------
*/

test('eloquent model connection resolver is the guarded database manager', function () {
    $resolver = Model::getConnectionResolver();

    expect($resolver)->toBeInstanceOf(ConnectionGuardDatabaseManager::class);
});

/*
|--------------------------------------------------------------------------
| Eloquent Write Blocking
|--------------------------------------------------------------------------
*/

test('eloquent create is blocked by read-only guard', function () {
    $user = new EloquentTestUser;
    $user->setConnection('guarded');

    expect(fn () => $user->getConnection()->table('users')->insert(['name' => 'Eliel']))
        ->toThrow(ConnectionGuardException::class, "The SQL command 'insert' is not allowed.");
});

test('eloquent model create() is blocked by read-only guard', function () {
    EloquentTestUser::on('guarded')->create(['name' => 'Eliel']);
})->throws(ConnectionGuardException::class, "The SQL command 'insert' is not allowed.");

test('eloquent model save() for new record is blocked by read-only guard', function () {
    $user       = new EloquentTestUser;
    $user->name = 'Eliel';
    $user->setConnection('guarded');
    $user->save();
})->throws(ConnectionGuardException::class, "The SQL command 'insert' is not allowed.");

test('eloquent model update is blocked by read-only guard', function () {
    // First, insert a record bypassing the guard
    ConnectionGuard::withoutGuards(function () {
        EloquentTestUser::on('guarded')->create(['name' => 'Eliel']);
    });

    $user = EloquentTestUser::on('guarded')->first();

    expect(fn () => $user->update(['name' => 'Updated']))
        ->toThrow(ConnectionGuardException::class, "The SQL command 'update' is not allowed.");
});

test('eloquent model delete is blocked by read-only guard', function () {
    ConnectionGuard::withoutGuards(function () {
        EloquentTestUser::on('guarded')->create(['name' => 'Eliel']);
    });

    $user = EloquentTestUser::on('guarded')->first();

    expect(fn () => $user->delete())
        ->toThrow(ConnectionGuardException::class, "The SQL command 'delete' is not allowed.");
});

/*
|--------------------------------------------------------------------------
| Eloquent Read Allowed
|--------------------------------------------------------------------------
*/

test('eloquent select operations are allowed on guarded connection', function () {
    ConnectionGuard::withoutGuards(function () {
        EloquentTestUser::on('guarded')->create(['name' => 'Eliel']);
    });

    $users = EloquentTestUser::on('guarded')->get();
    expect($users)->toHaveCount(1);

    $user = EloquentTestUser::on('guarded')->find(1);
    expect($user->name)->toBe('Eliel');

    $count = EloquentTestUser::on('guarded')->count();
    expect($count)->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Eloquent with withoutGuards()
|--------------------------------------------------------------------------
*/

test('eloquent operations succeed within withoutGuards callback', function () {
    ConnectionGuard::withoutGuards(function () {
        $user = EloquentTestUser::on('guarded')->create(['name' => 'Eliel']);

        expect($user->exists)->toBeTrue();
        expect($user->name)->toBe('Eliel');

        $user->update(['name' => 'Eliel Ferreira']);
        expect($user->fresh()->name)->toBe('Eliel Ferreira');

        $user->delete();
        expect(EloquentTestUser::on('guarded')->count())->toBe(0);
    });
});

test('eloquent operations are blocked again after withoutGuards callback', function () {
    ConnectionGuard::withoutGuards(function () {
        EloquentTestUser::on('guarded')->create(['name' => 'Eliel']);
    });

    expect(fn () => EloquentTestUser::on('guarded')->create(['name' => 'Another']))
        ->toThrow(ConnectionGuardException::class, "The SQL command 'insert' is not allowed.");
});

/*
|--------------------------------------------------------------------------
| Eloquent with except_tables
|--------------------------------------------------------------------------
*/

test('eloquent write is blocked on non-excepted tables with except_tables config', function () {
    expect(fn () => EloquentTestUser::on('guarded_custom')->create(['name' => 'Eliel']))
        ->toThrow(ConnectionGuardException::class, "The SQL command 'insert' is not allowed.");
});
