<?php

declare(strict_types=1);

namespace Mesh;

use InvalidArgumentException;
use SplPriorityQueue;

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
     * @var SplPriorityQueue
     */
    private SplPriorityQueue $queue;

    /**
     * Mesh constructor.
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->dataDirty = $data;
        }

        $this->queue = new SplPriorityQueue();
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
     * @param string|int $key
     * @param Sequence $sequence
     * @return Mesh
     */
    public function add($key, Sequence $sequence): Mesh
    {
        if (!is_string($key) && !is_int($key)) {
            throw new InvalidArgumentException('Key must be a string or integer');
        }

        $this->queue->insert([$key => $sequence], 1);

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
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @return bool
     */
    public function validate(): bool
    {
        // No sequences given
        if ($this->queue->isEmpty()) {
            return true;
        }

        foreach ($this->queue as $arr) {
            $key = key($arr);
            /** @var Sequence $item */
            $item = $arr[$key];

            if ($item->run($this->dataDirty[$key])) {
                // Set clean value
                $this->dataClean[$key] = $item->getValueClean();
            } else {
                // Add errors
                $this->addErrors($item->getErrors());
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
