<?php

namespace Elielelie\ConnectionGuard\Rules;

use Elielelie\ConnectionGuard\Contracts\SqlRule;
use Elielelie\ConnectionGuard\Exceptions\ConnectionGuardException;
use Elielelie\ConnectionGuard\Support\SqlNormalizer;

class PreventMissingWhereRule implements SqlRule
{
    protected array $options;

    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    public function validate(string $sql): void
    {
        $sql          = SqlNormalizer::normalize($sql);

        $isUpdate     = str_starts_with($sql, 'update');
        $isDelete     = str_starts_with($sql, 'delete');

        if (! $isUpdate && ! $isDelete) {
            return;
        }

        $exceptTables = $this->options['except_tables'] ?? [];

        if (! empty($exceptTables)) {
            if (preg_match('/(?:update|delete\s+from|from)\s+[`"\'\s]?([a-zA-Z0-9_\.-]+)/i', $sql, $matches)) {
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

        if (! preg_match('/\bwhere\b/i', $sql)) {
            $type = $isUpdate ? 'UPDATE' : 'DELETE';

            throw new ConnectionGuardException(
                "Dangerous query detected: {$type} without a WHERE clause is not allowed."
            );
        }
    }
}
