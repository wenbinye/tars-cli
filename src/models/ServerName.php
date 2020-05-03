<?php


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
     * @param string $application
     * @param string $serverName
     */
    public function __construct(string $application, string $serverName)
    {
        $this->application = $application;
        $this->serverName = $serverName;
    }

    /**
     * @return string
     */
    public function getApplication(): string
    {
        return $this->application;
    }

    /**
     * @return string
     */
    public function getServerName(): string
    {
        return $this->serverName;
    }

    public static function fromString(string $name): self
    {
        if (strpos($name, '.') === false) {
            throw new \InvalidArgumentException("Invalid server name: $name");
        }
        [$app, $server] = explode('.', $name, 2);
        return new self($app, $server);
    }

    public function __toString()
    {
        return $this->application . '.' . $this->serverName;
    }
}