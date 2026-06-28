<?php

namespace Elielelie\ConnectionGuard\Guards;

use Elielelie\ConnectionGuard\Contracts\Guard;
use Elielelie\ConnectionGuard\Rules\DdlRule;
use Elielelie\ConnectionGuard\Rules\PreventMissingWhereRule;
use Illuminate\Database\Connection;

class PreventiveMassiveGuard implements Guard
{
    protected array $rules;

    protected array $options;

    public function __construct(array $options = [], array $rules = [])
    {
        $this->options = $options;

        // PreventiveMassiveGuard blocks missing WHERE clauses and DROP commands
        $this->rules   = $rules ?: [
            new PreventMissingWhereRule($options),
            new class($options) extends DdlRule
            {
                protected array $commands = ['drop'];
            },
        ];
    }

    public function validate(Connection $connection, string $query, array $bindings = []): void
    {
        foreach ($this->rules as $rule) {
            $rule->validate($query);
        }
    }
}
