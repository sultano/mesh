<?php

declare(strict_types=1);

namespace Mesh;

use Zend\Filter\FilterInterface;
use Zend\Validator\ValidatorInterface;

/**
 * Class Sequence
 * @package Mesh
 */
class Sequence
{
    /**
     * @var \SplPriorityQueue
     */
    protected $queue;

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
    protected $format;

    /**
     * @var array
     */
    protected $errors = [];

    /**
     * Sequence constructor.
     * @param string|int|float|bool $value
     */
    public function __construct($value = null)
    {
        $this->queue = new \SplPriorityQueue();

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


        return $this;
    }

    /**
     * @param string $name
     * @return Sequence
     */
    public function filter(string $name): Sequence
    {

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
            throw new \RuntimeException('Value must be supplied');
        }

        // Nothing in the queue!
        if ($this->queue->isEmpty()) {
            return true;
        }

        $success = false;
        foreach ($this->queue as $item) {
            // Filter
            if ($item instanceof FilterInterface) {
                $this->valueClean = $item->filter($this->valueClean);
            }

            // Sequence
            if ($item instanceof Sequence && !$item->run($this->valueClean)) {
                $this->addErrors($item->getErrors());
            }

            // Rule
            if ($item instanceof ValidatorInterface && !$item->isValid($this->valueClean)) {
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
