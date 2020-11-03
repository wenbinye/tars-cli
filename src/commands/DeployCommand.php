<?php

declare(strict_types=1);

namespace wenbinye\tars\cli\commands;

use Symfony\Component\Console\Input\InputOption;
use wenbinye\tars\cli\Config;

class DeployCommand extends AbstractCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('deploy');
        $this->setDescription('Deploy server');
        $this->addOption('json', null, InputOption::VALUE_REQUIRED, 'server definition in json file');
        $this->addOption('template', null, InputOption::VALUE_NONE, 'output deploy template');
    }

    protected function handle(): void
    {
        if ($this->input->getOption('template')) {
            echo json_encode($this->createDeployTemplate(), JSON_PRETTY_PRINT), "\n";
        } elseif ($this->input->getOption('json')) {
            $data = $this->readJson();
            if (!isset($data['template_name'])) {
                throw new \InvalidArgumentException('template_name is missing');
            }
            if (!$this->getTarsClient()->getTemplate($data['template_name'])) {
                throw new \InvalidArgumentException("template_name {$data['template_name']} does not exist");
            }
            $ret = $this->postJson('deploy_server', $data);
            if (isset($ret['server_conf']['id'])) {
                $this->output->writeln('<info>Server deployed successfully!</info>');
            } else {
                $this->output->writeln('<error>Fail to deploy server</error>');
            }
        } else {
            $this->output->writeln('--template|--json is required');
        }
    }

    protected function createDeployTemplate(): array
    {
        $node = Config::getInstance()->getNode();
        if (!$node) {
            foreach ($this->get('list_tars_node')['rows'] as $nodeInfo) {
                if ('active' === $nodeInfo['present_state']) {
                    $node = $nodeInfo['node_name'];
                    break;
                }
            }
        }
        if (!$node) {
            throw new \InvalidArgumentException('Cannot get node');
        }
        $port = $this->getTarsClient()->getAvailablePort($node);

        return [
            'application' => 'appName',
            'server_name' => 'serverName',
            'server_type' => 'tars_php',
            'template_name' => $this->getTarsClient()->getConfig()->getTemplate() ?: 'tars.tarsphp.default',
            'node_name' => $node,
            'enable_set' => false,
            'set_name' => '',
            'set_area' => '',
            'set_group' => '',
            'operator' => '',
            'developer' => '',
            'adapters' => [
                [
                    'obj_name' => 'obj',
                    'bind_ip' => $node,
                    'port' => $port,
                    'port_type' => 'tcp',
                    'protocol' => 'not_tars',
                    'thread_num' => 0,
                    'max_connections' => 100000,
                    'queuecap' => 50000,
                    'queuetimeout' => 20000,
                ],
            ],
        ];
    }
}
