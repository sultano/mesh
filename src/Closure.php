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
     * @var string
     */
    private string $name;

    /**
     * @var \Closure
     */
    private \Closure $closure;

    /**
     * Closure constructor.
     * @param string $name
     * @param \Closure $closure
     */
    public function __construct(string $name, \Closure $closure)
    {
        $this->name = $name;
        $this->closure = $closure;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Closure
     */
    public function setName(string $name): Closure
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return \Closure
     */
    public function getClosure(): \Closure
    {
        return $this->closure;
    }

    /**
     * @param \Closure $closure
     * @return Closure
     */
    public function setClosure(\Closure $closure): Closure
    {
        $this->closure = $closure;

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
     * @param string|int|float|bool $value
     * @param array|null $context
     * @param mixed ...$parameters
     * @return mixed
     */
    public function __invoke(...$parameters)
    {
        return call_user_func_array($this->closure, $parameters);
    }
}
