<?php

namespace Elielelie\ConnectionGuard\Guards;

use Elielelie\ConnectionGuard\Contracts\Guard;
use Elielelie\ConnectionGuard\Rules\DdlRule;
use Illuminate\Database\Connection;

class DdlGuard implements Guard
{
    protected array $rules;

    protected array $options;

    public function __construct(array $options = [], array $rules = [])
    {
        $this->options = $options;
        $this->rules   = $rules ?: [
            new DdlRule($options),
        ];
    }

    public function validate(Connection $connection, string $query, array $bindings = []): void
    {
        foreach ($this->rules as $rule) {
            $rule->validate($query);
        }
    }
}
