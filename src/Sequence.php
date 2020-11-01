<?php

declare(strict_types=1);

namespace Mesh;

use ArrayObject;
use Closure;
use Exception;
use Laminas\Filter\FilterInterface;
use Laminas\Validator\ValidatorInterface;
use Mesh\Closure as ClosureDecorator;
use ReflectionClass;
use RuntimeException;

/**
 * Class Sequence
 * @package Mesh
 */
class Sequence
{
    /**
     * @var ArrayObject
     */
    protected ArrayObject $queue;

    /**
     * @var string|int|float|bool
     */
    protected $value;

    /**
     * @var string|int|float|bool
     */
    protected $valueClean;

    /**
     * @var array|null
     */
    protected ?array $context;

    /**
     * @var bool
     */
    protected bool $fail = false;

    /**
     * @var string|null
     */
    protected ?string $failureError = null;

    /**
     * @var bool
     */
    protected bool $break = false;

    /**
     * @var array
     */
    protected array $errors = [];

    /**
     * Sequence constructor.
     * @param string|int|float|bool $value
     */
    public function __construct($value = null)
    {
        $this->queue = new ArrayObject();

        if ($value !== null) {
            $this->setValue($value);
        }
    }

    /**
     * @return array|null
     */
    public function getContext(): ?array
    {
        return $this->context;
    }

    /**
     * @param array|null $context
     * @return Sequence
     */
    public function setContext(?array $context): Sequence
    {
        $this->context = $context;

        return $this;
    }

    /**
     * @param string $name
     * @param array $params
     * @return $this
     */
    public function rule(string $name, array $params = []): Sequence
    {
        try {
            $reflectionClass = new ReflectionClass($name);
        } catch(Exception $e) {
            throw new RuntimeException('Unknown validator');
        }

        if (!key_exists(ValidatorInterface::class, $reflectionClass->getInterfaces())) {
            throw new RuntimeException('Unknown validator');
        }

        $this->queue->append(new $name($params));

        return $this;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function filter(string $name): Sequence
    {
        try {
            $reflectionClass = new ReflectionClass($name);
        } catch(Exception $e) {
            throw new RuntimeException('Unknown filter');
        }

        if (!key_exists(FilterInterface::class, $reflectionClass->getInterfaces())) {
            throw new RuntimeException('Unknown filter');
        }

        $this->queue->append(new $name());

        return $this;
    }

    /**
     * @param string $name
     * @param Closure $closure
     * @return $this
     */
    public function callback(string $name, Closure $closure): Sequence
    {
        $this->queue->append(new ClosureDecorator($name, $closure));

        return $this;
    }

    /**
     * @param null $value
     * @return bool
     */
    public function run($value = null): bool
    {
        // Set value
        if ($value !== null) {
            $this->setValue($value);
        }

        // Nothing in the queue!
        if ($this->queue->count() === 0) {
            return true;
        }

        $success = true;
        foreach ($this->queue as $item) {
            // Exit sequence
            if ($this->break) {
                break;
            }

            // Filter
            if ($item instanceof FilterInterface) {
                $this->valueClean = $item->filter($this->valueClean);
                continue;
            }

            // Sequence
            if ($item instanceof Sequence) {
                $success = $item->run($this->valueClean);
                if (!$success) {
                    $this->addErrors($item->getMessages());
                }

                continue;
            }

            // Rule
            if ($item instanceof ValidatorInterface) {
                $success = $item->isValid($this->valueClean);
                if (!$success) {
                    $this->addErrors($item->getMessages());
                }

                continue;
            }

            // Callback
            if ($item instanceof ClosureDecorator) {
                $value = $item($this->valueClean, $this);
                if ($this->fail) {
                    $success = false;

                    if ($this->failureError) {
                        $this->addErrors([$item->getName() => $this->failureError]);
                    }

                    // Reset failure
                    $this->fail = false;
                    $this->failureError = null;
                } elseif ($value !== null) {
                    $this->valueClean = $value;
                }
            }
        }

        return $success;
    }

    /**
     * @return bool|float|int|string
     */
    public function getValueClean()
    {
        return $this->valueClean;
    }

    /**
     * @param bool|float|int|string $value
     * @return Sequence
     */
    public function setValue($value)
    {
        $this->value = $this->valueClean = $value;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Break sequence
     *
     * @return null
     */
    public function break()
    {
        $this->break = true;

        return null;
    }

    /**
     * Continue sequence
     *
     * @return null
     */
    public function continue()
    {
        $this->break = false;

        return null;
    }

    /**
     * @param string|null $error
     * @return $this
     */
    public function fail(string $error = null): Sequence
    {
        $this->fail = true;
        $this->failureError = $error;
        $this->break = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function failAndContinue(string $error = null): Sequence
    {
        $this->fail = true;
        $this->failureError = $error;

        return $this;
    }

    /**
     * @param array $errors
     */
    protected function addErrors(array $errors): void
    {
        $this->errors = $this->errors + $errors;
    }
}
