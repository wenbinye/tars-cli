<?php

declare(strict_types=1);

namespace wenbinye\tars\cli\models;

class Adapter
{
    /**
     * @var int
     */
    private $id;
    /**
     * @var ServerName
     */
    private $server;
    /**
     * @var Endpoint
     */
    private $endpoint;
    /**
     * @var string
     */
    private $node;
    /**
     * @var string
     */
    private $name;
    /**
     * @var int
     */
    private $threadNum;
    /**
     * @var int
     */
    private $maxConnections;
    /**
     * @var int
     */
    private $queueCapacity;
    /**
     * @var int
     */
    private $queueTimeout;
    /**
     * @var string
     */
    private $handleGroup;
    /**
     * @var string
     */
    private $protocol;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): Adapter
    {
        $this->id = $id;

        return $this;
    }

    public function getServer(): ServerName
    {
        return $this->server;
    }

    public function setServer(ServerName $server): Adapter
    {
        $this->server = $server;

        return $this;
    }

    public function getEndpoint(): Endpoint
    {
        return $this->endpoint;
    }

    public function setEndpoint(Endpoint $endpoint): Adapter
    {
        $this->endpoint = $endpoint;

        return $this;
    }

    public function getNode(): string
    {
        return $this->node;
    }

    public function setNode(string $node): Adapter
    {
        $this->node = $node;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Adapter
    {
        $this->name = $name;

        return $this;
    }

    public function getThreadNum(): int
    {
        return $this->threadNum;
    }

    public function setThreadNum(int $threadNum): Adapter
    {
        $this->threadNum = $threadNum;

        return $this;
    }

    public function getMaxConnections(): int
    {
        return $this->maxConnections;
    }

    public function setMaxConnections(int $maxConnections): Adapter
    {
        $this->maxConnections = $maxConnections;

        return $this;
    }

    public function getQueueCapacity(): int
    {
        return $this->queueCapacity;
    }

    public function setQueueCapacity(int $queueCapacity): Adapter
    {
        $this->queueCapacity = $queueCapacity;

        return $this;
    }

    public function getQueueTimeout(): int
    {
        return $this->queueTimeout;
    }

    public function setQueueTimeout(int $queueTimeout): Adapter
    {
        $this->queueTimeout = $queueTimeout;

        return $this;
    }

    public function getHandleGroup(): string
    {
        return $this->handleGroup;
    }

    public function setHandleGroup(string $handleGroup): Adapter
    {
        $this->handleGroup = $handleGroup;

        return $this;
    }

    public function getProtocol(): string
    {
        return $this->protocol;
    }

    public function setProtocol(string $protocol): Adapter
    {
        $this->protocol = $protocol;

        return $this;
    }

    public function getObjName(): string
    {
        $parts = explode('.', $this->name);

        return preg_replace('/Adapter$/', '', end($parts));
    }

    public static function fromArray($info): self
    {
        $adapter = new self();
        $adapter->id = (int) $info['id'];
        $adapter->protocol = $info['protocol'] ?? 'tars';
        $adapter->server = new ServerName($info['application'], $info['server_name']);
        $adapter->endpoint = Endpoint::fromString($info['endpoint']);
        $adapter->name = $info['adapter_name'];
        $adapter->node = $info['node_name'];
        $adapter->maxConnections = (int) $info['max_connections'];
        $adapter->queueCapacity = (int) $info['queuecap'];
        $adapter->queueTimeout = (int) $info['queuetimeout'];
        $adapter->threadNum = (int) $info['thread_num'];
        $adapter->handleGroup = $info['handlegroup'];

        return $adapter;
    }

    public function toArray(): array
    {
        return [
            'obj_name' => $this->getObjName(),
            'port' => $this->endpoint->getPort(),
            'port_type' => $this->endpoint->getProtocol(),
            'protocol' => $this->getProtocol(),
            'thread_num' => $this->getThreadNum(),
            'max_connections' => $this->getMaxConnections(),
            'queuecap' => $this->getQueueCapacity(),
            'queuetimeout' => $this->getQueueTimeout(),
        ];
    }
}
