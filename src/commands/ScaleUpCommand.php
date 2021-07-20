<?php

declare(strict_types=1);

namespace wenbinye\tars\cli\commands;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ScaleUpCommand extends AbstractCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('scale:up');
        $this->setDescription('Scale up the server');
        $this->addArgument('server', InputArgument::REQUIRED, 'The server name or from node name');
        $this->addOption('node', null, InputOption::VALUE_REQUIRED, 'Scale to node name');
        $this->addOption('no-patch', null, InputOption::VALUE_NONE, '只扩容不部署文件');
    }

    protected function handle(): void
    {
        $serverOrNode = $this->input->getArgument('server');
        $patch = !$this->input->getOption('no-patch');
        if ($this->getTarsClient()->hasNode($serverOrNode)) {
            $this->scaleUpFromNode($serverOrNode, $this->getNode(), $patch);
        } else {
            $servers = $this->getTarsClient()->getServers($serverOrNode);
            $usedNodes = [];
            foreach ($servers as $server) {
                $usedNodes[] = $server->getNodeName();
            }
            $server = $servers[0];

            $node = $this->getNode($usedNodes);
            $this->scaleUp($server, $node, $patch);
        }
    }

    /**
     * @param string[] $excludeNodes
     */
    protected function getNode(array $excludeNodes = []): string
    {
        $allNodes = $this->getTarsClient()->getNodeList();
        $availNodes = array_values(array_diff($allNodes, $excludeNodes));
        if (empty($availNodes)) {
            throw new \RuntimeException(sprintf('无可用节点，已使用节点：%s', implode(',', $excludeNodes)));
        }
        $node = $this->input->getOption('node');
        if (!$node) {
            $node = $this->io->choice('choose node: ', $availNodes);
        }
        if (!in_array($node, $availNodes, true)) {
            throw new \InvalidArgumentException("$node 不正确，必须是".implode(',', $availNodes).'其中之一');
        }

        return $node;
    }
}
