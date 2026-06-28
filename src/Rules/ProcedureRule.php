<?php

namespace Elielelie\ConnectionGuard\Rules;

use Elielelie\ConnectionGuard\Contracts\SqlRule;
use Elielelie\ConnectionGuard\Exceptions\ConnectionGuardException;
use Elielelie\ConnectionGuard\Support\SqlNormalizer;

class ProcedureRule implements SqlRule
{
    protected array $commands = [
        'call',
        'execute',
        'exec',
    ];

    protected array $options;

    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    public function validate(string $sql): void
    {
        $sql              = SqlNormalizer::normalize($sql);

        $exceptProcedures = $this->options['except_procedures'] ?? [];

        if (! empty($exceptProcedures)) {
            if (preg_match('/(?:call|execute|exec)\s+[`"\'\s]?([a-zA-Z0-9_\.-]+)/i', $sql, $matches)) {
                $procedureName = $matches[1];

                if (str_contains($procedureName, '.')) {
                    $parts         = explode('.', $procedureName);
                    $procedureName = end($parts);
                }
                $procedureName = trim($procedureName, '`"\'');

                if (in_array(strtolower($procedureName), array_map('strtolower', $exceptProcedures))) {
                    return;
                }
            }
        }

        foreach ($this->commands as $command) {
            if (str_starts_with($sql, $command)) {
                throw new ConnectionGuardException(
                    "Executing procedures via '{$command}' is not allowed."
                );
            }
        }
    }
}
