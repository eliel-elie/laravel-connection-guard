<?php

namespace Elielelie\ConnectionGuard\Contracts;

use Elielelie\ConnectionGuard\Exceptions\ConnectionGuardException;
use Illuminate\Database\Connection;

interface Guard
{
    /**
     * Validates whether an operation can be executed.
     *
     * @throws ConnectionGuardException
     */
    public function validate(
        Connection $connection,
        string $query,
        array $bindings = []
    ): void;
}
