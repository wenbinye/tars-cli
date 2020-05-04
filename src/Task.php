<?php

declare(strict_types=1);

namespace wenbinye\tars\cli;

use wenbinye\tars\cli\exception\BadResponseException;

class Task
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
    private $parameters;

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
    private $maxTryTimes;
    /**
     * @var int
     */
    private $sleepInterval;

    public function __construct(TarsClient $tarsClient, string $command, int $serverId, array $parameters,
                                callable $onTaskSend, callable $onSuccess, callable $onFail,
                                callable $onRunning, int $maxTryTimes, int $sleepInterval)
    {
        $this->tarsClient = $tarsClient;
        $this->command = $command;
        $this->serverId = $serverId;
        $this->parameters = $parameters;
        $this->maxTryTimes = $maxTryTimes;
        $this->onTaskSend = $onTaskSend;
        $this->onSuccess = $onSuccess;
        $this->onFail = $onFail;
        $this->onRunning = $onRunning;
        $this->sleepInterval = $sleepInterval;
    }

    public function run(): void
    {
        $taskNo = $this->tarsClient->postJson('add_task', [
            'serial' => true,
            'items' => [
                [
                    'server_id' => $this->serverId,
                    'command' => $this->command,
                    'parameters' => $this->parameters,
                ],
            ],
        ]);
        call_user_func($this->onTaskSend, $taskNo);
        if (empty($taskNo)) {
            return;
        }
        $retries = $this->maxTryTimes;
        while ($retries-- > 0) {
            $ret = $this->tarsClient->get('task', ['task_no' => $taskNo]);
            $status = $ret['items'][0]['status_info'] ?? null;
            if (!$status) {
                throw new \InvalidArgumentException("Cannot get task status $taskNo");
            }
            if ('EM_I_SUCCESS' === $status) {
                call_user_func($this->onSuccess, $ret['items'][0]['execute_info']);

                return;
            }

            if ('EM_I_FAILED' === $status) {
                call_user_func($this->onFail, $ret['items'][0]['execute_info']);

                return;
            }

            if ('EM_I_RUNNING' !== $status) {
                throw new BadResponseException("Unknown task status $status");
            }
            call_user_func($this->onRunning);
            sleep($this->sleepInterval);
        }
    }

    public static function builder(): TaskBuilder
    {
        return new TaskBuilder();
    }
}
