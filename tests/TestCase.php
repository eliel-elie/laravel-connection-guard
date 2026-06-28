<?php

namespace Elielelie\Connection\Tests;

use Elielelie\ConnectionGuard\ConnectionGuardServiceProvider;
use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    /**
     * Get package providers.
     *
     * @param  Application $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            ConnectionGuardServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  Application $app
     * @return void
     */
    protected function defineEnvironment($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Setup protected connection config
        $app['config']->set('database.connections.guarded', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
            'guards'   => [
                'read-only',
            ],
        ]);

        // Setup guarded connection with exempt tables
        $app['config']->set('database.connections.guarded_custom', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
            'guards'   => [
                'read-only' => [
                    'except_tables' => ['sessions', 'migrations'],
                ],
            ],
        ]);

        // Setup guarded DDL connection config
        $app['config']->set('database.connections.guarded_ddl', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
            'guards'   => [
                'ddl',
            ],
        ]);

        // Setup guarded Procedure connection config
        $app['config']->set('database.connections.guarded_procedure', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
            'guards'   => [
                'procedure',
            ],
        ]);

        // Setup guarded Preventive Massive connection config
        $app['config']->set('database.connections.guarded_preventive_massive', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
            'guards'   => [
                'preventive-massive',
            ],
        ]);
    }
}
