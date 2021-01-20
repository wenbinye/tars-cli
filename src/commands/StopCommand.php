<?php

declare(strict_types=1);

namespace wenbinye\tars\cli\commands;

use Symfony\Component\Console\Input\InputArgument;

class StopCommand extends AbstractCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('stop');
        $this->setDescription('Stops the server or node');
        $this->addArgument('server', InputArgument::REQUIRED, 'The server id or name or node name');
    }

    protected function handle(): void
    {
        $serverOrNode = $this->input->getArgument('server');
        if (in_array($serverOrNode, $this->getTarsClient()->getNodeList(), true)) {
            $this->stopServerOnNode($serverOrNode);
        } else {
            $servers = $this->getTarsClient()->getServers($serverOrNode);
            foreach ($servers as $server) {
                $this->stopServer($server);
            }
        }
    }

    private function stopServerOnNode(string $nodeName): void
    {
        $apps = [];
        foreach ($this->getTarsClient()->getServerNames() as $serverName) {
            $apps[$serverName->getApplication()] = true;
        }
        foreach (array_keys($apps) as $app) {
            foreach ($this->getTarsClient()->getAllServers($app) as $server) {
                if ($server->getNodeName() === $nodeName) {
                    $this->io->note("Stop {$server->getServerName()} on $nodeName");
                    $this->stopServer($server);
                }
            }
        }
    }
}
