<?php

namespace Elielelie\ConnectionGuard\Support;

class SqlNormalizer
{
    public static function normalize(string $sql): string
    {
        // Remove comentários /* */
        $sql = preg_replace('#/\*.*?\*/#s', ' ', $sql);

        // Remove comentários --
        $sql = preg_replace('/--.*$/m', ' ', $sql);

        // Remove espaços duplicados
        $sql = preg_replace('/\s+/', ' ', $sql);

        return strtolower(trim($sql));
    }
}
