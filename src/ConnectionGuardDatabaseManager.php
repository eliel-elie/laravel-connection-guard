<?php

namespace Elielelie\ConnectionGuard;

use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;

class ConnectionGuardDatabaseManager extends DatabaseManager
{
    /**
     * Configure the given connection.
     *
     * @param  string     $type
     * @return Connection
     */
    protected function configure(Connection $connection, $type)
    {
        $connection = parent::configure($connection, $type);

        $name       = $connection->getName();
        $config     = $this->configuration($name);

        if (isset($config['guards']) && is_array($config['guards'])) {
            $manager = $this->app->make(ConnectionGuardManager::class);

            $guards  = [];

            foreach ($config['guards'] as $key => $value) {
                if (is_numeric($key)) {
                    // Simple string guard like 'read-only'
                    $guards[] = $manager->resolve($value);
                } else {
                    // Configured guard like 'read-only' => ['except_tables' => [...]]
                    $guards[] = $manager->resolve($key, $value);
                }
            }

            $connection->beforeExecuting(function (string &$query, array &$bindings, Connection $conn) use ($guards, $manager) {
                if ($manager->isDisabled()) {
                    return;
                }

                foreach ($guards as $guard) {
                    $guard->validate($conn, $query, $bindings);
                }
            });
        }

        return $connection;
    }
}
