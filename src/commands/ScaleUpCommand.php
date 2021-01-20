<?php

declare(strict_types=1);

namespace wenbinye\tars\cli\commands;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use wenbinye\tars\cli\models\Server;

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
        if ($this->getTarsClient()->hasNode($serverOrNode)) {
            $this->scaleUpFromNode($serverOrNode, $this->getNode());
        } else {
            $servers = $this->getTarsClient()->getServers($serverOrNode);
            $usedNodes = [];
            foreach ($servers as $server) {
                $usedNodes[] = $server->getNodeName();
            }
            $server = $servers[0];

            $node = $this->getNode($usedNodes);
            $this->scaleUp($server, $node);
        }
    }

    private function scaleUpFromNode(string $fromNode, string $node): void
    {
        $apps = [];
        foreach ($this->getTarsClient()->getServerNames() as $serverName) {
            $apps[$serverName->getApplication()] = true;
        }
        foreach (array_keys($apps) as $app) {
            foreach ($this->getTarsClient()->getAllServers($app) as $server) {
                if ($server->getNodeName() === $fromNode) {
                    $found = false;
                    foreach ($this->getTarsClient()->getServers((string) $server->getServer()) as $runningServer) {
                        if ($runningServer->getNodeName() === $node) {
                            $this->io->note("$runningServer already exist");
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        $this->scaleUp($server, $node);
                    }
                }
            }
        }
    }

    private function scaleUp(Server $server, string $node): void
    {
        $adapterPorts = [];
        foreach ($this->getTarsClient()->getAdapters($server->getId()) as $adapter) {
            $adapterPorts[$adapter->getName()] = $adapter->getEndpoint()->getPort();
        }
        $result = $this->getTarsClient()->postJson('expand_server_preview', [
            'application' => $server->getApplication(),
            'server_name' => $server->getServerName(),
            'set' => '',
            'node_name' => $server->getNodeName(),
            'expand_nodes' => [$node],
            'enable_set' => false,
            'set_name' => '',
            'set_area' => '',
            'set_group' => '',
            'copy_node_config' => true,
            'nodeName' => [],
        ]);

        $adapters = [];
        foreach ($result as $adapter) {
            $adapters[] = [
                'bind_ip' => $node,
                'node_name' => $node,
                'obj_name' => $adapter['obj_name'],
                'port' => $adapterPorts[$server->getServer().'.'.$adapter['obj_name'].'Adapter'],
            ];
        }

        $this->getTarsClient()->postJson('expand_server', [
            'application' => $server->getApplication(),
            'server_name' => $server->getServerName(),
            'set' => '',
            'node_name' => $server->getNodeName(),
            'copy_node_config' => true,
            'expand_preview_servers' => $adapters,
        ]);
        $this->io->success("成功扩容 {$server} 到 $node");

        if ($this->input->getOption('no-patch')) {
            return;
        }

        $newServer = null;
        foreach ($this->getTarsClient()->getServers((string) $server->getServer()) as $new) {
            if ($new->getNodeName() === $node) {
                $newServer = $new;
                break;
            }
        }
        if (null !== $newServer) {
            $ret = $this->getTarsClient()->get('server_patch_list', [
                'application' => $server->getApplication(),
                'module_name' => $server->getServerName(),
                'page_size' => 1,
            ]);
            if (!empty($ret)) {
                $patchId = $ret['rows'][0]['id'];
                $this->io->success("发布代码 $patchId 到 {$newServer}");
                $this->applyPatch($patchId, $newServer);
            }
        } else {
            $this->io->error('无法查询到服务');
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
