<?php

namespace Elielelie\ConnectionGuard;

use Closure;
use Elielelie\ConnectionGuard\Contracts\Guard;
use Elielelie\ConnectionGuard\Contracts\SqlRule;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

class ConnectionGuardManager
{
    /**
     * The container instance.
     */
    protected Container $container;

    /**
     * The registered custom guard creators.
     */
    protected array $customCreators = [];

    /**
     * Indicates if guards are globally disabled.
     */
    protected bool $disabled        = false;

    /**
     * Create a new ConnectionGuardManager instance.
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Disable all guards globally.
     */
    public function disable(): void
    {
        $this->disabled = true;
    }

    /**
     * Enable all guards globally.
     */
    public function enable(): void
    {
        $this->disabled = false;
    }

    /**
     * Determine if guards are globally disabled.
     */
    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    /**
     * Run a callback with all guards globally disabled.
     */
    public function withoutGuards(Closure $callback): mixed
    {
        $this->disable();

        try {
            return $callback();
        } finally {
            $this->enable();
        }
    }

    /**
     * Resolve a guard instance.
     *
     *
     * @throws InvalidArgumentException
     */
    public function resolve(mixed $guard, array $options = []): Guard
    {
        if ($guard instanceof Guard) {
            return $guard;
        }

        if ($guard instanceof Closure) {
            return new class($guard) implements Guard
            {
                protected Closure $callback;

                public function __construct(Closure $callback)
                {
                    $this->callback = $callback;
                }

                public function validate(mixed $connection, string $query, array $bindings = []): void
                {
                    ($this->callback)($connection, $query, $bindings);
                }
            };
        }

        if (is_string($guard)) {
            // Check if there is a custom creator registered for this name
            if (isset($this->customCreators[$guard])) {
                return $this->callCustomCreator($guard, $options);
            }

            // Check if it's mapped in the config aliases
            $configGuards = $this->container['config']->get('connection-guard.guards', []);

            if (isset($configGuards[$guard])) {
                $guardClass = $configGuards[$guard];

                return $this->resolve($guardClass, $options);
            }

            // Otherwise, assume it's a class name and resolve it via container
            if (class_exists($guard)) {
                $instance = $this->container->make($guard, ['options' => $options]);

                if ($instance instanceof Guard) {
                    return $instance;
                }

                if ($instance instanceof SqlRule) {
                    return new class($instance) implements Guard
                    {
                        protected SqlRule $rule;

                        public function __construct(SqlRule $rule)
                        {
                            $this->rule = $rule;
                        }

                        public function validate(mixed $connection, string $query, array $bindings = []): void
                        {
                            $this->rule->validate($query);
                        }
                    };
                }

                throw new InvalidArgumentException("Guard [{$guard}] must implement " . Guard::class . ' or ' . SqlRule::class);
            }
        }

        throw new InvalidArgumentException("Unable to resolve guard [{$guard}].");
    }

    /**
     * Register a custom guard creator.
     */
    public function extend(string $name, Closure $callback): self
    {
        $this->customCreators[$name] = $callback;

        return $this;
    }

    /**
     * Call a custom guard creator.
     */
    protected function callCustomCreator(string $name, array $options = []): Guard
    {
        $instance = $this->customCreators[$name]($this->container, $options);

        if ($instance instanceof Guard) {
            return $instance;
        }

        throw new InvalidArgumentException("Custom creator for guard [{$name}] must return an instance of " . Guard::class);
    }
}
