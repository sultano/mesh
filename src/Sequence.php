<?php

declare(strict_types=1);

namespace Mesh;

use ArrayObject;
use Closure;
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
     * @throws ReflectionException
     */
    public function rule(string $name, array $params = []): Sequence
    {
        if (!class_exists($name)) {
            throw new RuntimeException('Unknown validator');
        }

        $reflectionClass = new ReflectionClass($name);
        if (!key_exists(ValidatorInterface::class, $reflectionClass->getInterfaces())) {
            throw new RuntimeException('Unknown validator');
        }

        $this->queue->append(new $name($params));

        return $this;
    }

    /**
     * @param string $name
     * @return $this
     * @throws ReflectionException
     */
    public function filter(string $name): Sequence
    {
        if (!class_exists($name)) {
            throw new RuntimeException('Unknown filter');
        }

        $reflectionClass = new ReflectionClass($name);
        if (!key_exists(FilterInterface::class, $reflectionClass->getInterfaces())) {
            throw new RuntimeException('Unknown filter');
        }

        $this->queue->append(new $name());

        return $this;
    }

    /**
     * @param Closure $closure
     * @param string|null $error
     * @return $this
     */
    public function callback(Closure $closure, ?string $error = null): Sequence
    {
        $this->queue->append(new ClosureDecorator($closure, $error));

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
            if ($item instanceof Closure) {
                $value = $item($this->valueClean, $this->context);
                if ($value === false && $item->getError()) {
                    $success = false;
                    $this->addErrors(['__CALLBACK__' => $item->getError()]);
                } else {
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
     * @param array $errors
     */
    protected function addErrors(array $errors): void
    {
        $this->errors = $this->errors + $errors;
    }
}
