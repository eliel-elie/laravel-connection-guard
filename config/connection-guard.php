<?php

use Elielelie\ConnectionGuard\Guards\DdlGuard;
use Elielelie\ConnectionGuard\Guards\PreventiveMassiveGuard;
use Elielelie\ConnectionGuard\Guards\ProcedureGuard;
use Elielelie\ConnectionGuard\Guards\ReadOnlyGuard;

return [

    /*
    |--------------------------------------------------------------------------
    | Connection Guards
    |--------------------------------------------------------------------------
    |
    | Here you may define the guards and their classes mapped to simple keys.
    | You can apply these guards to any database connection by adding a
    | 'guards' array with these keys to your connection configuration.
    |
    */

    'guards' => [
        'read-only'          => ReadOnlyGuard::class,
        'ddl'                => DdlGuard::class,
        'procedure'          => ProcedureGuard::class,
        'preventive-massive' => PreventiveMassiveGuard::class,
    ],

];
