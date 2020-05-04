<?php

declare(strict_types=1);

namespace wenbinye\tars\cli;

class TaskBuilder
{
    /**
     * @var TarsClient
     */
    private $tarsClient;
    /**
     * @var string
     */
    private $command;
    /**
     * @var int
     */
    private $serverId;
    /**
     * @var array
     */
    private $parameters = [];
    /**
     * @var callable
     */
    private $onTaskSend;
    /**
     * @var callable
     */
    private $onSuccess;
    /**
     * @var callable
     */
    private $onFail;
    /**
     * @var callable
     */
    private $onRunning;
    /**
     * @var int
     */
    private $maxTryTimes = 15;
    /**
     * @var int
     */
    private $sleepInterval = 2;

    public function getTarsClient(): TarsClient
    {
        return $this->tarsClient;
    }

    public function setTarsClient(TarsClient $tarsClient): TaskBuilder
    {
        $this->tarsClient = $tarsClient;

        return $this;
    }

    public function getCommand(): string
    {
        return $this->command;
    }

    public function setCommand(string $command): TaskBuilder
    {
        $this->command = $command;

        return $this;
    }

    public function getServerId(): int
    {
        return $this->serverId;
    }

    public function setServerId(int $serverId): TaskBuilder
    {
        $this->serverId = $serverId;

        return $this;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function setParameters(array $parameters): TaskBuilder
    {
        $this->parameters = $parameters;

        return $this;
    }

    public function getOnTaskSend(): callable
    {
        return $this->onTaskSend;
    }

    public function setOnTaskSend(callable $onTaskSend): TaskBuilder
    {
        $this->onTaskSend = $onTaskSend;

        return $this;
    }

    public function getOnSuccess(): callable
    {
        return $this->onSuccess;
    }

    public function setOnSuccess(callable $onSuccess): TaskBuilder
    {
        $this->onSuccess = $onSuccess;

        return $this;
    }

    public function getOnFail(): callable
    {
        return $this->onFail;
    }

    public function setOnFail(callable $onFail): TaskBuilder
    {
        $this->onFail = $onFail;

        return $this;
    }

    public function getOnRunning(): callable
    {
        return $this->onRunning;
    }

    public function setOnRunning(callable $onRunning): TaskBuilder
    {
        $this->onRunning = $onRunning;

        return $this;
    }

    public function getMaxTryTimes(): int
    {
        return $this->maxTryTimes;
    }

    public function setMaxTryTimes(int $maxTryTimes): TaskBuilder
    {
        $this->maxTryTimes = $maxTryTimes;

        return $this;
    }

    public function getSleepInterval(): int
    {
        return $this->sleepInterval;
    }

    public function setSleepInterval(int $sleepInterval): TaskBuilder
    {
        $this->sleepInterval = $sleepInterval;

        return $this;
    }

    public function build(): Task
    {
        $dummyCallback = static function () {};
        if (!$this->onSuccess) {
            $this->onSuccess = $dummyCallback;
        }
        if (!$this->onFail) {
            $this->onFail = $dummyCallback;
        }
        if (!$this->onRunning) {
            $this->onRunning = $dummyCallback;
        }
        if (!$this->onTaskSend) {
            $this->onTaskSend = $dummyCallback;
        }
        if (!$this->tarsClient || !$this->command || !$this->serverId) {
            throw new \InvalidArgumentException('required field should not empty');
        }
        if ('patch_tars' === $this->command && !$this->parameters) {
            throw new \InvalidArgumentException('patch_tars require parameters');
        }

        return new Task($this->tarsClient, $this->command, $this->serverId, $this->parameters,
            $this->onTaskSend, $this->onSuccess, $this->onFail,
        $this->onRunning, $this->maxTryTimes, $this->sleepInterval);
    }
}
