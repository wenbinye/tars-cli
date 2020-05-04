<?php

declare(strict_types=1);

namespace wenbinye\tars\cli\models;

class ServerName
{
    /**
     * @var string
     */
    private $application;

    /**
     * @var string
     */
    private $serverName;

    /**
     * ServerName constructor.
     */
    public function __construct(string $application, string $serverName)
    {
        $this->application = $application;
        $this->serverName = $serverName;
    }

    public function getApplication(): string
    {
        return $this->application;
    }

    public function getServerName(): string
    {
        return $this->serverName;
    }

    public static function fromString(string $name): self
    {
        if (false === strpos($name, '.')) {
            throw new \InvalidArgumentException("Invalid server name: $name");
        }
        [$app, $server] = explode('.', $name, 2);

        return new self($app, $server);
    }

    public function __toString()
    {
        return $this->application.'.'.$this->serverName;
    }
}
