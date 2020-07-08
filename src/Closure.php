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
     * @param callable $callback
     * @param string|null $error
     */
    public function __construct(callable $callback, ?string $error = null)
    {
        $this->closure = \Closure::fromCallable($callback);
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
     * @param mixed $value
     * @param array|null $context
     * @return mixed
     */
    public function __invoke($value, ?array $context)
    {
        return $this->closure->call($this, $value, $context);
    }
}
