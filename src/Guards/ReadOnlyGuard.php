<?php

namespace Elielelie\ConnectionGuard\Guards;

use Elielelie\ConnectionGuard\Contracts\Guard;
use Elielelie\ConnectionGuard\Contracts\SqlRule;
use Elielelie\ConnectionGuard\Rules\DdlRule;
use Elielelie\ConnectionGuard\Rules\WriteRule;
use Illuminate\Database\Connection;

class ReadOnlyGuard implements Guard
{
    /**
     * @var SqlRule[]
     */
    protected array $rules;

    protected array $options;

    public function __construct(array $options = [], array $rules = [])
    {
        $this->options = $options;
        $this->rules   = $rules ?: [
            new WriteRule($options),
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
