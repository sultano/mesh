<?php

declare(strict_types=1);

namespace Mesh;

use Laminas\Filter\FilterInterface;
use Laminas\Validator\ValidatorInterface;
use ReflectionClass;
use RuntimeException;
use SplQueue;

/**
 * Class Sequence
 * @package Mesh
 */
class Sequence
{
    /**
     * @var SplQueue
     */
    protected SplQueue $queue;

    /**
     * @var string|int|float|bool
     */
    protected $value;

    /**
     * @var string|int|float|bool
     */
    protected $valueClean;

    /**
     * @var Sequence
     */
    protected Sequence $format;

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
        $this->queue = new SplQueue();

        if ($value !== null) {
            $this->setValue($value);
        }
    }

    /**
     * @param Sequence $format
     * @return Sequence
     */
    public function setFormat(Sequence $format): Sequence
    {
        $this->format = $format;

        return $this;
    }

    /**
     * @param string $name
     * @param array $params
     * @return Sequence
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

        $this->queue->enqueue(new $name());

        return $this;
    }

    /**
     * @param string $name
     * @return Sequence
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

        $this->queue->enqueue(new $name());

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

        // Check we don't have a NULL value
        if ($this->value === null) {
            throw new RuntimeException('Value must be supplied');
        }

        // Nothing in the queue!
        if ($this->queue->isEmpty()) {
            return true;
        }

        $success = true;
        foreach ($this->queue as $item) {
            // Filter
            if ($item instanceof FilterInterface) {
                $this->valueClean = $item->filter($this->valueClean);
            }

            // Sequence
            if ($item instanceof Sequence && !$item->run($this->valueClean)) {
                $success = false;
                $this->addErrors($item->getErrors());
            }

            // Rule
            if ($item instanceof ValidatorInterface && !$item->isValid($this->valueClean)) {
                $success = false;
                $this->addErrors($item->getMessages());
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
