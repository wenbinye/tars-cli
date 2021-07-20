<?php

declare(strict_types=1);

namespace wenbinye\tars\cli\commands;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use wenbinye\tars\cli\models\Adapter;
use wenbinye\tars\cli\models\Server;

class ServerListCommand extends AbstractCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('server:list');
        $this->setDescription('Lists server by app or id');
        $this->addOption('all', 'a', InputOption::VALUE_NONE, 'Display all application include tars');
        $this->addArgument('server', InputArgument::OPTIONAL, 'Server id or name or app name');
    }

    protected function handle(): void
    {
        $serverOrNode = $this->input->getArgument('server');
        if ($serverOrNode) {
            if ($this->getTarsClient()->hasNode($serverOrNode)) {
                $this->listNodeServers($serverOrNode);
            } else {
                $this->listAppServers();
            }
        } else {
            $this->listServers();
        }
    }

    private function listServers(): void
    {
        $includeTars = $this->input->getOption('all');

        foreach ($this->getTarsClient()->getServerNames() as $server) {
            if (!$includeTars && 'tars' === $server->getApplication()) {
                continue;
            }
            $rows[] = [(string) $server];
        }
        $this->writeTable(['Server'], $rows);
    }

    private function listNodeServers(string $nodeName): void
    {
        $apps = [];
        foreach ($this->getTarsClient()->getServerNames() as $serverName) {
            $apps[$serverName->getApplication()] = true;
        }
        $servers = [];
        foreach (array_keys($apps) as $app) {
            foreach ($this->getTarsClient()->getAllServers($app) as $server) {
                if ($server->getNodeName() === $nodeName) {
                    $servers[] = $server;
                }
            }
        }
        $this->showServers($servers);
    }

    private function listAppServers(): void
    {
        $serverName = $this->input->getArgument('server');
        if (false !== strpos($serverName, '.')) {
            $servers = $this->getTarsClient()->getServers($serverName);
        } else {
            $appName = $serverName;
            $servers = $this->getTarsClient()->getAllServers($appName);
        }
        $this->showServers($servers);
    }

    private function stateDesc(string $state): string
    {
        if (!empty($state) && $this->isAscii()) {
            $tag = 'active' === $state ? 'info' : 'error';

            return "<$tag>$state</$tag>";
        }

        return $state;
    }

    /**
     * @param Server[] $servers
     */
    private function showServers(array $servers): void
    {
        $names = array_unique(array_map(function (Server $server) {
            return (string) $server->getServer();
        }, $servers));
        if (1 === count($names) && $this->isJson()) {
            $server = $servers[0];
            $adapters = $this->getTarsClient()->getAdapters($server->getId());
            $this->output->write(json_encode([
                (string) $server->getServer() => [
                    'server_type' => $server->getServerType(),
                    'template_name' => $server->getTemplateName(),
                    'nodes' => array_map(function (Server $server) {
                        return $server->getNodeName();
                    }, $servers),
                    'adapters' => array_map(function (Adapter $adapter) {
                        return $adapter->toArray();
                    }, $adapters),
                ],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            return;
        }
        $rows = [];
        foreach ($servers as $server) {
            $rows[] = [
                $server->getId(),
                (string) $server->getServer(),
                $server->getNodeName(),
                $this->stateDesc($server->getSettingState()),
                $this->stateDesc($server->getPresentState()),
                $server->getProcessId(),
                $server->getPatchVersion(),
                $server->getPatchTime() ? $server->getPatchTime()->toDateTimeString() : '',
            ];
        }
        $this->writeTable(['ID', 'Server', 'Node', 'Setting', 'Present', 'PID', 'Patch', 'Patched At'], $rows);
    }
}
