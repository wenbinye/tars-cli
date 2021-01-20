<?php

declare(strict_types=1);

namespace wenbinye\tars\cli\commands;

use Symfony\Component\Console\Input\InputArgument;
use wenbinye\tars\cli\models\Server;

class DestroyCommand extends AbstractCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('destroy');
        $this->setDescription('Destroy server deployment');
        $this->addArgument('server', InputArgument::REQUIRED, 'The server id or name');
    }

    protected function handle(): void
    {
        $serverOrNode = $this->input->getArgument('server');
        $servers = $this->getTarsClient()->getServers($serverOrNode);
        if (count($servers) > 1) {
            $node = $this->io->choice('Destroy the server', array_map(static function (Server $server): string {
                return $server->getNodeName();
            }, $servers));
            foreach ($servers as $server) {
                if ($server->getNodeName() === $node) {
                    $this->removeServer($server);
                }
            }
        } else {
            $server = $servers[0];
            if (!$this->io->confirm('Destroy server '.$server->getServer().' [y/N]:', false)) {
                return;
            }
            $this->removeServer($server);
        }
    }
}
