<?php

declare(strict_types=1);

namespace wenbinye\tars\cli;

use wenbinye\tars\cli\exception\BadResponseException;

class Task
{
    public const STATUS_SUCCESS = 'EM_I_SUCCESS';
    public const STATUS_FAILED = 'EM_I_FAILED';
    public const STATUS_NOT_START = 'EM_I_NOT_START';
    public const STATUS_RUNNING = 'EM_I_RUNNING';

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
    private $callbacks;

    /**
     * @var int
     */
    private $maxTryTimes;
    /**
     * @var int
     */
    private $sleepInterval;

    public function __construct(TarsClient $tarsClient, string $command, int $serverId, array $parameters,
                                callable $onTaskSend, array $callbacks, int $maxTryTimes, int $sleepInterval)
    {
        $this->tarsClient = $tarsClient;
        $this->command = $command;
        $this->serverId = $serverId;
        $this->parameters = $parameters;
        $this->maxTryTimes = $maxTryTimes;
        $this->onTaskSend = $onTaskSend;
        $this->callbacks = $callbacks;
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
            if (isset($this->callbacks[$status])) {
                call_user_func($this->callbacks[$status], $ret['items'][0]['execute_info']);
                if (self::STATUS_RUNNING !== $status) {
                    return;
                }
            } else {
                throw new BadResponseException("Unknown task status $status");
            }
            sleep($this->sleepInterval);
        }
    }

    public static function builder(): TaskBuilder
    {
        return new TaskBuilder();
    }
}
