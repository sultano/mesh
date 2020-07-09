<?php

declare(strict_types=1);

namespace Mesh;

/**
 * Class Closure
 * @package Mesh
 */
final class Closure
{
    /**
     * @var \Closure
     */
    private \Closure $closure;

    /**
     * @var string|null
     */
    private ?string $error;

    /**
     * Closure constructor.
     * @param \Closure $closure
     * @param string|null $error
     */
    public function __construct(\Closure $closure, ?string $error = null)
    {
        $this->closure = $closure;
        $this->error = $error;
    }

    /**
     * @return string|null
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * @param string|null $error
     * @return $this
     */
    public function setError(?string $error): Closure
    {
        $this->error = $error;

        return $this;
    }

    /**
     * Duplicates the closure with a new bound object and class scope
     * @link https://secure.php.net/manual/en/closure.bindto.php
     * @param object $newthis The object to which the given anonymous function should be bound, or NULL for the closure to be unbound.
     * @param mixed $newscope The class scope to which associate the closure is to be associated, or 'static' to keep the current one.
     * If an object is given, the type of the object will be used instead.
     * This determines the visibility of protected and private methods of the bound object.
     * @return Closure Returns the newly created Closure object or FALSE on failure
     */
    public function bindTo(object $newthis, $newscope = 'static'): \Closure
    {
        return $this->closure->bindTo($newthis, $newscope);
    }

    /**
     * Temporarily binds the closure to newthis, and calls it with any given parameters.
     * @link https://php.net/manual/en/closure.call.php
     * @param object $newthis The object to bind the closure to for the duration of the call.
     * @param mixed $parameters [optional] Zero or more parameters, which will be given as parameters to the closure.
     * @return mixed
     */
    public function call(object $newthis, ...$parameters)
    {
        return $this->closure->call($newthis, $parameters);
    }

    /**
     * @param mixed ...$parameters
     * @return mixed
     */
    public function __invoke(...$parameters)
    {
        return call_user_func_array($this->closure, $parameters);
    }
}
