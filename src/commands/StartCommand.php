<?php

declare(strict_types=1);

namespace wenbinye\tars\cli\commands;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class StartCommand extends AbstractCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('start');
        $this->setDescription('Starts the server');
        $this->addOption('restart', null, InputOption::VALUE_NONE, 'Restart server');
        $this->addArgument('server', InputArgument::REQUIRED, 'The server id or name or node name');
    }

    protected function handle(): void
    {
        $serverOrNode = $this->input->getArgument('server');
        if (in_array($serverOrNode, $this->getTarsClient()->getNodeList(), true)) {
            $this->startServerOnNode($serverOrNode);
        } else {
            $servers = $this->getTarsClient()->getServers($serverOrNode);
            foreach ($servers as $server) {
                if ($this->input->getOption('restart')) {
                    $this->restartServer($server);
                } else {
                    $this->startServer($server);
                }
            }
        }
    }

    private function startServerOnNode(string $nodeName): void
    {
        $apps = [];
        foreach ($this->getTarsClient()->getServerNames() as $serverName) {
            $apps[$serverName->getApplication()] = true;
        }
        $cmd = $this->input->getOption('restart') ? 'restart' : 'start';
        foreach (array_keys($apps) as $app) {
            foreach ($this->getTarsClient()->getAllServers($app) as $server) {
                if ($server->getNodeName() === $nodeName) {
                    $this->io->note("$cmd {$server->getServerName()} on $nodeName");
                    $this->runOnServer($server, $cmd);
                }
            }
        }
    }
}
