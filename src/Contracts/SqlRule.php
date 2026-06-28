<?php

namespace Elielelie\ConnectionGuard\Contracts;

use Elielelie\ConnectionGuard\Exceptions\ConnectionGuardException;

interface SqlRule
{
    /**
     * @throws ConnectionGuardException
     */
    public function validate(string $sql): void;
}
