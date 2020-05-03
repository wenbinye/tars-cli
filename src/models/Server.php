<?php


namespace wenbinye\tars\cli\models;

use Carbon\Carbon as DateTime;

class Server
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
     * @var string
     */
    private $nodeName;

    /**
     * @var string
     */
    private $serverType;

    /**
     * @var bool
     */
    private $enableSet;

    /**
     * @var string
     */
    private $templateName;

    /**
     * @var int
     */
    private $asyncThreadNum;

    /**
     * @var int
     */
    private $patchVersion;

    /**
     * @var ?DateTime
     */
    private $patchTime;

    /**
     * @var int
     */
    private $processId;

    /**
     * @var DateTime
     */
    private $posttime;

    /**
     * @var string
     */
    private $settingState;

    /**
     * @var string
     */
    private $presentState;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getApplication(): string
    {
        return $this->server->getApplication();
    }

    /**
     * @return string
     */
    public function getServerName(): string
    {
        return $this->server->getServerName();
    }

    public function getServer(): ServerName
    {
        return $this->server;
    }

    /**
     * @param ServerName $server
     */
    public function setServer(ServerName $server): void
    {
        $this->server = $server;
    }

    /**
     * @return string
     */
    public function getNodeName(): string
    {
        return $this->nodeName;
    }

    /**
     * @param string $nodeName
     */
    public function setNodeName(string $nodeName): void
    {
        $this->nodeName = $nodeName;
    }

    /**
     * @return string
     */
    public function getServerType(): string
    {
        return $this->serverType;
    }

    /**
     * @param string $serverType
     */
    public function setServerType(string $serverType): void
    {
        $this->serverType = $serverType;
    }

    /**
     * @return bool
     */
    public function isEnableSet(): bool
    {
        return $this->enableSet;
    }

    /**
     * @param bool $enableSet
     */
    public function setEnableSet(bool $enableSet): void
    {
        $this->enableSet = $enableSet;
    }

    /**
     * @return string
     */
    public function getTemplateName(): string
    {
        return $this->templateName;
    }

    /**
     * @param string $templateName
     */
    public function setTemplateName(string $templateName): void
    {
        $this->templateName = $templateName;
    }

    /**
     * @return int
     */
    public function getAsyncThreadNum(): int
    {
        return $this->asyncThreadNum;
    }

    /**
     * @param int $asyncThreadNum
     */
    public function setAsyncThreadNum(int $asyncThreadNum): void
    {
        $this->asyncThreadNum = $asyncThreadNum;
    }

    /**
     * @return int
     */
    public function getPatchVersion(): int
    {
        return $this->patchVersion;
    }

    /**
     * @param int $patchVersion
     */
    public function setPatchVersion(int $patchVersion): void
    {
        $this->patchVersion = $patchVersion;
    }

    /**
     * @return DateTime
     */
    public function getPatchTime(): ?DateTime
    {
        return $this->patchTime;
    }

    /**
     * @param DateTime $patchTime
     */
    public function setPatchTime(?DateTime $patchTime): void
    {
        $this->patchTime = $patchTime;
    }

    /**
     * @return int
     */
    public function getProcessId(): int
    {
        return $this->processId;
    }

    /**
     * @param int $processId
     */
    public function setProcessId(int $processId): void
    {
        $this->processId = $processId;
    }

    public function getCreateTime(): DateTime
    {
        return $this->posttime;
    }

    /**
     * @return DateTime
     */
    public function getPosttime(): DateTime
    {
        return $this->posttime;
    }

    /**
     * @param DateTime $posttime
     */
    public function setPosttime(DateTime $posttime): void
    {
        $this->posttime = $posttime;
    }

    /**
     * @return string
     */
    public function getSettingState(): string
    {
        return $this->settingState;
    }

    /**
     * @param string $settingState
     */
    public function setSettingState(string $settingState): void
    {
        $this->settingState = $settingState;
    }

    /**
     * @return string
     */
    public function getPresentState(): string
    {
        return $this->presentState;
    }

    /**
     * @param string $presentState
     */
    public function setPresentState(string $presentState): void
    {
        $this->presentState = $presentState;
    }

    public function __toString()
    {
        return (string) $this->server;
    }
}