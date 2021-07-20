<?php

declare(strict_types=1);

namespace wenbinye\tars\cli\commands;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ScaleDownCommand extends AbstractCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('scale:down');
        $this->setDescription('Scale down the server');
        $this->addArgument('server', InputArgument::REQUIRED, 'The server name or node');
        $this->addOption('node', null, InputOption::VALUE_REQUIRED, 'The node name');
    }

    protected function handle(): void
    {
        $serverOrNode = $this->input->getArgument('server');
        if ($this->getTarsClient()->hasNode($serverOrNode)) {
            if ($this->io->confirm("Destroy all server on node $serverOrNode")) {
                $this->removeServerOnNode($serverOrNode);
            }

            return;
        }
        if (is_numeric($serverOrNode)) {
            $server = $this->getTarsClient()->getServer($serverOrNode);
        } else {
            $servers = $this->getTarsClient()->getServers($serverOrNode);
            $usedNodes = [];
            foreach ($servers as $server) {
                $usedNodes[] = $server->getNodeName();
            }
            $node = $this->input->getOption('node');
            if (!$node) {
                $node = $this->io->choice('choose node: ', $usedNodes);
            }
            if (!in_array($node, $usedNodes, true)) {
                throw new \InvalidArgumentException("$node 不正确，必须是".implode(',', $usedNodes).'其中之一');
            }
            $server = null;
            foreach ($servers as $one) {
                if ($one->getNodeName() === $node) {
                    $server = $one;
                    break;
                }
            }
        }
        $this->stopServer($server);
        $this->removeServer($server);
    }
}
