<?php

declare(strict_types=1);

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

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getApplication(): string
    {
        return $this->server->getApplication();
    }

    public function getServerName(): string
    {
        return $this->server->getServerName();
    }

    public function getServer(): ServerName
    {
        return $this->server;
    }

    public function setServer(ServerName $server): void
    {
        $this->server = $server;
    }

    public function getNodeName(): string
    {
        return $this->nodeName;
    }

    public function setNodeName(string $nodeName): void
    {
        $this->nodeName = $nodeName;
    }

    public function getServerType(): string
    {
        return $this->serverType;
    }

    public function setServerType(string $serverType): void
    {
        $this->serverType = $serverType;
    }

    public function isEnableSet(): bool
    {
        return $this->enableSet;
    }

    public function setEnableSet(bool $enableSet): void
    {
        $this->enableSet = $enableSet;
    }

    public function getTemplateName(): string
    {
        return $this->templateName;
    }

    public function setTemplateName(string $templateName): void
    {
        $this->templateName = $templateName;
    }

    public function getAsyncThreadNum(): int
    {
        return $this->asyncThreadNum;
    }

    public function setAsyncThreadNum(int $asyncThreadNum): void
    {
        $this->asyncThreadNum = $asyncThreadNum;
    }

    public function getPatchVersion(): int
    {
        return $this->patchVersion;
    }

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

    public function getProcessId(): int
    {
        return $this->processId;
    }

    public function setProcessId(int $processId): void
    {
        $this->processId = $processId;
    }

    public function getCreateTime(): DateTime
    {
        return $this->posttime;
    }

    public function getPosttime(): DateTime
    {
        return $this->posttime;
    }

    public function setPosttime(DateTime $posttime): void
    {
        $this->posttime = $posttime;
    }

    public function getSettingState(): string
    {
        return $this->settingState;
    }

    public function setSettingState(string $settingState): void
    {
        $this->settingState = $settingState;
    }

    public function getPresentState(): string
    {
        return $this->presentState;
    }

    public function setPresentState(string $presentState): void
    {
        $this->presentState = $presentState;
    }

    public function __toString()
    {
        return $this->server.' on '.$this->nodeName;
    }

    public static function fromArray(array $info): self
    {
        $server = new self();
        $server->setId($info['id']);
        $server->setServer(new ServerName($info['application'], $info['server_name']));
        $server->setServerType($info['server_type']);
        $server->setEnableSet($info['enable_set']);
        $server->setNodeName($info['node_name']);
        $server->setTemplateName($info['template_name'] ?? '');
        $server->setAsyncThreadNum((int) ($info['async_thread_num'] ?? 0));
        if (!empty($info['patch_version'])) {
            $server->setPatchVersion((int) $info['patch_version']);
            $server->setPatchTime(DateTime::parse($info['patch_time']));
        } else {
            $server->setPatchVersion(0);
        }
        $server->setPosttime(DateTime::parse($info['posttime']));
        $server->setProcessId((int) ($info['process_id'] ?? 0));
        $server->setSettingState($info['setting_state'] ?? '');
        $server->setPresentState($info['present_state'] ?? '');

        return $server;
    }
}
