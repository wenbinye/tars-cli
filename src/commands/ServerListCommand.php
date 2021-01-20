<?php

declare(strict_types=1);

namespace wenbinye\tars\cli\commands;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use wenbinye\tars\cli\models\Server;

class ServerListCommand extends AbstractCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('server:list');
        $this->setDescription('Lists server by app or id');
        $this->addOption('all', 'a', InputOption::VALUE_NONE, 'Display all application include tars');
        $this->addOption('node', null, InputOption::VALUE_REQUIRED, 'Display server on the node');
        $this->addArgument('server', InputArgument::OPTIONAL, 'Server id or name or app name');
    }

    protected function handle(): void
    {
        if ($this->input->getArgument('server')) {
            $this->listAppServers();
        } elseif ($this->input->getOption('node')) {
            $this->listNodeServers($this->input->getOption('node'));
        } else {
            $this->listServers();
        }
    }

    private function listServers(): void
    {
        $table = $this->createTable(['Server']);
        $includeTars = $this->input->getOption('all');

        foreach ($this->getTarsClient()->getServerNames() as $server) {
            if (!$includeTars && 'tars' === $server->getApplication()) {
                continue;
            }
            $table->addRow([(string) $server]);
        }
        $table->render();
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
        if (!empty($state)) {
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
        $rows = [];
        foreach ($servers as $server) {
            $rows[] = [
                $server->getId(),
                (string) $server,
                $server->getNodeName(),
                $this->stateDesc($server->getSettingState()),
                $this->stateDesc($server->getPresentState()),
                $server->getProcessId(),
                $server->getPatchVersion(),
                $server->getPatchTime() ? $server->getPatchTime()->toDateTimeString() : '',
            ];
        }
        $this->createTable(['ID', 'Server', 'Node', 'Setting', 'Present', 'PID', 'Patch', 'Patched At'])
            ->addRows($rows)
            ->render();
    }
}
