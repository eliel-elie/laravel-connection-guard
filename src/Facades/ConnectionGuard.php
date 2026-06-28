<?php

namespace Elielelie\ConnectionGuard\Facades;

use Elielelie\ConnectionGuard\ConnectionGuardManager;
use Elielelie\ConnectionGuard\Contracts\Guard;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Guard                  resolve(mixed $guard, array $options = [])
 * @method static ConnectionGuardManager extend(string $name, \Closure $callback)
 * @method static void                   disable()
 * @method static void                   enable()
 * @method static bool                   isDisabled()
 * @method static mixed                  withoutGuards(\Closure $callback)
 *
 * @see ConnectionGuardManager
 */
class ConnectionGuard extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'connection-guard';
    }
}
