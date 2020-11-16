<?php

declare(strict_types=1);

namespace wenbinye\tars\cli\commands;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ChoiceQuestion;

class ScaleUpCommand extends AbstractCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('scale:up');
        $this->setDescription('Scale up the server');
        $this->addArgument('server', InputArgument::REQUIRED, 'The server name');
        $this->addArgument('node', InputArgument::OPTIONAL, 'The node name');
        $this->addOption('no-patch', null, InputOption::VALUE_NONE, '只扩容不部署文件');
    }

    protected function handle(): void
    {
        $servers = $this->getTarsClient()->getServers($this->input->getArgument('server'));
        $usedNodes = [];
        $adapterPorts = [];
        foreach ($servers as $server) {
            $usedNodes[] = $server->getNodeName();
        }
        $server = $servers[0];

        $allNodes = $this->getTarsClient()->get('node_list');
        $availNodes = array_values(array_diff($allNodes, $usedNodes));
        if (empty($availNodes)) {
            throw new \RuntimeException(sprintf('无可用节点，已使用节点：%s', implode(',', $usedNodes)));
        }
        $node = $this->input->getArgument('node');
        if (!$node) {
            /** @var QuestionHelper $helper */
            $helper = $this->getHelper('question');
            $node = $helper->ask($this->input, $this->output, new ChoiceQuestion('choose node: ', $availNodes));
        }
        if (!in_array($node, $availNodes, true)) {
            throw new \InvalidArgumentException("$node 不正确，必须是".implode(',', $availNodes).'其中之一');
        }

        $fromNode = $usedNodes[0];
        foreach ($this->getTarsClient()->getAdapters($server->getId()) as $adapter) {
            $adapterPorts[$adapter->getName()] = $adapter->getEndpoint()->getPort();
        }
        $result = $this->getTarsClient()->postJson('expand_server_preview', [
            'application' => $server->getApplication(),
            'server_name' => $server->getServerName(),
            'set' => '',
            'node_name' => $fromNode,
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
            'node_name' => $fromNode,
            'copy_node_config' => true,
            'expand_preview_servers' => $adapters,
        ]);
        $this->output->writeln("<info>成功扩容 {$server->getServerName()} 从 $fromNode 到 $node</info>");

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
                $this->output->writeln("<info>发布代码 $patchId 到 {$newServer->getNodeName()}</info>");
                $this->applyPatch($patchId, $newServer);
            }
        } else {
            $this->output->writeln('<error>无法查询到服务</error>');
        }
    }
}
