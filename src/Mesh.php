<?php

declare(strict_types=1);

namespace Mesh;

use InvalidArgumentException;
use SplQueue;

/**
 * Class Mesh
 * @package Mesh
 */
class Mesh
{
    /**
     * @var array
     */
    protected array $dataDirty = [];

    /**
     * @var array
     */
    protected array $dataClean = [];

    /**
     * @var array
     */
    protected array $errors = [];

    /**
     * @var SplQueue
     */
    private SplQueue $queue;

    /**
     * @var bool
     */
    private bool $isValidated = false;

    /**
     * Mesh constructor.
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->setData($data);
        }

        $this->queue = new SplQueue();
    }

    /**
     * @param array $data
     * @return Mesh
     */
    public function setData(array $data): Mesh
    {
        $this->dataDirty = $data;
        $this->dataClean = $data;

        return $this;
    }

    /**
     * @return array
     */
    public function getDataDirty(): array
    {
        return $this->dataDirty;
    }

    /**
     * @return array|bool
     */
    public function getDataClean()
    {
        return $this->isValidated && !$this->hasErrors() ? $this->dataClean : false;
    }

    /**
     * @param string|int $key
     * @param Sequence $sequence
     * @return Mesh
     */
    public function add($key, Sequence $sequence): Mesh
    {
        if (!is_string($key) && !is_int($key)) {
            throw new InvalidArgumentException('Key must be a string or integer');
        }

        $this->queue->enqueue([$key => $sequence]);

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
     * @return array|bool
     */
    public function getErrors()
    {
        return $this->isValidated ? $this->errors : false;
    }

    /**
     * @return bool
     */
    public function validate(): bool
    {
        $this->isValidated = true;

        // No sequences given
        if ($this->queue->isEmpty()) {
            return true;
        }

        foreach ($this->queue as $arr) {
            $key = key($arr);
            /** @var Sequence $item */
            $item = $arr[$key];
            $item->setContext($this->getDataDirty());

            if ($item->run($this->dataDirty[$key])) {
                // Set clean value
                $this->dataClean[$key] = $item->getValueClean();
            } else {
                // Add errors
                $this->addErrors([$key => $item->getErrors()]);
            }
        }

        return !$this->hasErrors();
    }

    /**
     * @param array $errors
     */
    protected function addErrors(array $errors): void
    {
        $this->errors = $this->errors + $errors;
    }
}
