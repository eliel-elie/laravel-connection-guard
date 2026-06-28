<?php

namespace Elielelie\ConnectionGuard\Rules;

use Elielelie\ConnectionGuard\Contracts\SqlRule;
use Elielelie\ConnectionGuard\Exceptions\ConnectionGuardException;
use Elielelie\ConnectionGuard\Support\SqlNormalizer;

class WriteRule implements SqlRule
{
    protected array $commands = [
        'insert',
        'update',
        'delete',
        'merge',
        'replace',
        'upsert',
    ];

    protected array $options;

    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    public function validate(string $sql): void
    {
        $sql          = SqlNormalizer::normalize($sql);

        $exceptTables = $this->options['except_tables'] ?? [];

        if (! empty($exceptTables)) {
            if (preg_match('/(?:insert\s+into|insert|update|delete\s+from|from)\s+[`"\'\s]?([a-zA-Z0-9_\.-]+)/i', $sql, $matches)) {
                $tableName = $matches[1];

                if (str_contains($tableName, '.')) {
                    $parts     = explode('.', $tableName);
                    $tableName = end($parts);
                }
                $tableName = trim($tableName, '`"\'');

                if (in_array(strtolower($tableName), array_map('strtolower', $exceptTables))) {
                    return;
                }
            }
        }

        foreach ($this->commands as $command) {
            if (str_starts_with($sql, $command)) {
                throw new ConnectionGuardException(
                    "The SQL command '{$command}' is not allowed."
                );
            }
        }
    }
}
