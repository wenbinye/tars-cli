<?php

declare(strict_types=1);

namespace wenbinye\tars\cli\commands;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

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
        if ($this->input->getArgument('server')) {
            $this->listAppServers();
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

    private function listAppServers(): void
    {
        $serverName = $this->input->getArgument('server');
        if (false !== strpos($serverName, '.')) {
            $servers = $this->getTarsClient()->getServers($serverName);
        } else {
            $appName = $serverName;
            $servers = $this->getTarsClient()->getAllServers($appName);
        }
        $table = $this->createTable(['ID', 'Server', 'Node', 'Setting', 'Present', 'PID', 'Patch', 'Patched At']);
        foreach ($servers as $server) {
            $table->addRow([$server->getId(),
                (string) $server,
                $server->getNodeName(),
                $this->stateDesc($server->getSettingState()),
                $this->stateDesc($server->getPresentState()),
                $server->getProcessId(),
                $server->getPatchVersion(),
                $server->getPatchTime() ? $server->getPatchTime()->toDateTimeString() : '', ]);
        }
        $table->render();
    }

    private function stateDesc(string $state): string
    {
        if (!empty($state)) {
            $tag = 'active' === $state ? 'info' : 'error';

            return "<$tag>$state</$tag>";
        }

        return $state;
    }
}
