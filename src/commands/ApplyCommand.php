<?php

declare(strict_types=1);

namespace wenbinye\tars\cli\commands;

use Symfony\Component\Console\Input\InputArgument;
use wenbinye\tars\cli\models\Server;
use wenbinye\tars\cli\models\ServerName;

class ApplyCommand extends AbstractCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('apply');
        $this->setDescription('Update server');
        $this->addArgument('file', InputArgument::REQUIRED, 'server definition in json file');
    }

    protected function handle(): void
    {
        $serverList = json_decode(file_get_contents($this->input->getArgument('file')), true);
        foreach ($serverList as $serverNameKey => $config) {
            $serverName = ServerName::fromString($serverNameKey);
            try {
                $nodes = $config['nodes'];
                $adapterConfig = [];
                foreach ($config['adapters'] as $adapter) {
                    $adapterConfig[$adapter['obj_name']] = $adapter;
                }

                $servers = $this->getTarsClient()->getServers((string) $serverName);
                $templateServer = array_pop($servers);
                $this->updateAdapters($templateServer, $adapterConfig);

                $existNodes = [$templateServer->getNodeName()];
                foreach ($servers as $server) {
                    $existNodes[] = $server->getNodeName();
                    if (in_array($server->getNodeName(), $nodes, true)) {
                        $this->updateAdapters($server, $adapterConfig);
                    } else {
                        $this->removeServer($server);
                    }
                }
                foreach (array_diff($nodes, $existNodes) as $newNode) {
                    $this->scaleUp($templateServer, $newNode, true);
                }
                if (!in_array($templateServer->getNodeName(), $nodes, true)) {
                    $this->removeServer($templateServer);
                }
            } catch (\InvalidArgumentException $e) {
                $this->deployServer($serverName, $config);
            }
        }
    }

    private function deployServer(ServerName $serverName, array $config): void
    {
        if (!isset($config['template_name'])) {
            throw new \InvalidArgumentException($serverName.' template_name is missing');
        }

        if (!$this->getTarsClient()->getTemplate($config['template_name'])) {
            throw new \InvalidArgumentException("template_name {$config['template_name']} does not exist");
        }
        $nodes = $config['nodes'];
        if (empty($config['nodes'])) {
            throw new \InvalidArgumentException($serverName.' nodes is missing');
        }
        unset($config['nodes']);
        $firstNode = array_pop($nodes);
        $config = array_replace($config, [
            'application' => $serverName->getApplication(),
            'server_name' => $serverName->getServerName(),
            'server_type' => 'tars_php',
            'node_name' => $firstNode,
            'enable_set' => false,
            'set_name' => '',
            'set_area' => '',
            'set_group' => '',
            'operator' => '',
            'developer' => '',
        ]);

        foreach ($config['adapters'] as &$adapter) {
            $adapter['bind_ip'] = $firstNode;
        }
        unset($adapter);
        $ret = $this->postJson('deploy_server', $config);
        if (isset($ret['server_conf']['id'])) {
            $this->output->writeln("<info>Server {$serverName} was deployed to {$firstNode} successfully!</info>");
            $server = $this->getTarsClient()->getServer($ret['server_conf']['id']);
            foreach ($nodes as $node) {
                $this->scaleUp($server, $node, true);
            }
        } else {
            throw new \RuntimeException("Fail to deploy server {$serverName} to {$firstNode}");
        }
    }

    protected function updateAdapters(Server $server, array $adapterConfig): void
    {
        $adapters = [];
        foreach ($this->getTarsClient()->getAdapters($server->getId()) as $adapter) {
            $adapters[$adapter->getObjName()] = $adapter;
        }
        foreach (array_diff(array_keys($adapters), array_keys($adapterConfig)) as $toRemove) {
            $adapter = $adapters[$toRemove];
            $ret = $this->getTarsClient()->get('delete_adapter_conf', ['id' => $adapter->getId()]);
            if (empty($ret)) {
                $this->output->writeln("<error>Fail to delete adapter {$adapter->getName()} on {$adapter->getEndpoint()->getHost()}</error>");
            } else {
                $this->output->writeln("<info>Delete adapter {$adapter->getName()} on {$adapter->getEndpoint()->getHost()} successfuly</info>");
            }
        }
        foreach (array_diff(array_keys($adapterConfig), array_keys($adapters)) as $toAdd) {
            $config = $adapterConfig[$toAdd];
            $config += [
                'application' => $server->getApplication(),
                'server_name' => $server->getServerName(),
                'node_name' => $server->getNodeName(),
                'endpoint' => "tcp -h {$server->getNodeName()} -t {$config['queuetimeout']} -p {$config['port']} -e 0",
                'servant' => $server->getServer().'.'.$config['obj_name'],
            ];

            $ret = $this->getTarsClient()->postJson('add_adapter_conf', $config);
            if (empty($ret)) {
                $this->output->writeln("<error>Fail to add adapter {$config['servant']} on {$config['node_name']}</error>");
            } else {
                $this->output->writeln("<info>Add adapter {$config['servant']} on {$config['node_name']} successfuly</info>");
            }
        }
    }
}
