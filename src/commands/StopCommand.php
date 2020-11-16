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
        $this->setDescription('Stops the server');
        $this->addArgument('server', InputArgument::REQUIRED, 'The server id or name');
    }

    protected function handle(): void
    {
        $servers = $this->getTarsClient()->getServers($this->input->getArgument('server'));
        foreach ($servers as $server) {
            $this->stopServer($server);
        }
    }
}
