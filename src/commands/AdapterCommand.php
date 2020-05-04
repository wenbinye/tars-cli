<?php

declare(strict_types=1);

namespace wenbinye\tars\cli\commands;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use wenbinye\tars\cli\models\Endpoint;
use wenbinye\tars\cli\models\Server;

class AdapterCommand extends AbstractCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('adapter');
        $this->setDescription('Lists adapter of the server');
        $this->addOption('delete', null, InputOption::VALUE_REQUIRED, 'The adapter id to delete');
        $this->addOption('template', null, InputOption::VALUE_NONE, 'Print the adapter template');
        $this->addOption('json', null, InputOption::VALUE_REQUIRED, 'The adapter info in json file');
        $this->addArgument('server', InputArgument::REQUIRED, 'The server id or name');
    }

    protected function handle(): void
    {
        $server = $this->getTarsClient()->getServer($this->input->getArgument('server'));
        if ($this->input->getOption('delete')) {
            $this->deleteAdapter();
        } elseif ($this->input->getOption('template')) {
            echo json_encode($this->createAdapterTemplate($server), JSON_PRETTY_PRINT), "\n";
        } elseif ($this->input->getOption('json')) {
            $this->saveAdapter($server);
        } else {
            $this->listAdapters($server);
        }
    }

    /**
     * @return mixed
     */
    protected function deleteAdapter(): void
    {
        $adapterId = (int) $this->input->getOption('delete');
        if (!$adapterId) {
            throw new \InvalidArgumentException('Adapter id is required for deletion');
        }
        $adapter = $this->getTarsClient()->getAdapter($adapterId);
        $ret = $this->getTarsClient()->get('delete_adapter_conf', ['id' => $adapterId]);
        if (empty($ret)) {
            $this->output->writeln('<error>Fail to delete adapter '.$adapter->getName().'</error>');
        } else {
            $this->output->writeln('<info>Delete adapter '.$adapter->getName().' successfuly</info>');
        }
    }

    protected function listAdapters(Server $server): void
    {
        // 绑定地址	线程数	最大连接数	队列最大长度	队列超时时间(ms)	操作
        $table = $this->createTable(['ID', 'Adapter', 'Address', 'Thread Num', 'Max Conn', 'Queue Capacity', 'Queue Timeout']);
        foreach ($this->getTarsClient()->getAdapters($server->getId()) as $adapter) {
            $table->addRow([
                $adapter->getId(),
                $adapter->getName(),
                (string) $adapter->getEndpoint(),
                $adapter->getThreadNum(),
                $adapter->getMaxConnections(),
                $adapter->getQueueCapacity(),
                $adapter->getQueueTimeout(),
            ]);
        }
        $table->render();
    }

    private function createAdapterTemplate(Server $server): array
    {
        return [
            'application' => $server->getApplication(),         // 应用
            'server_name' => $server->getServerName(),         // 服务
            'node_name' => $server->getNodeName(),           // 节点
            'thread_num' => 5,           // 线程数
            'endpoint' => (string) Endpoint::create($server->getNodeName(),
                $this->getTarsClient()->getAvailablePort($server->getNodeName()) ?? 0),            // EndPoint
            'max_connections' => 1000,      // 最大连接数
            'allow_ip' => '',            // 允许IP
            'servant' => $server->getServer().'.{ObjName}Obj',             // Servant
            'queuecap' => 10000,             // 队列长度
            'queuetimeout' => 60000,         // 队列超时时间
            'protocol' => 'tars|non_tars',            // 协议
            'handlegroup' => '',          // 处理组
        ];
    }

    private function saveAdapter(Server $server): void
    {
        $data = $this->readJson();
        if (empty($data['application'])) {
            $data['application'] = $server->getApplication();
        } elseif ($data['application'] !== $server->getApplication()) {
            throw new \InvalidArgumentException('application not match, should equalt to '.$server->getApplication());
        }
        if (empty($data['server_name'])) {
            $data['server_name'] = $server->getServerName();
        } elseif ($data['server_name'] !== $server->getServerName()) {
            throw new \InvalidArgumentException('server_name not match, should equalt to '.$server->getServerName());
        }
        if (empty($data['node_name'])) {
            $data['node_name'] = $server->getNodeName();
        } elseif ($data['node_name'] !== $server->getNodeName()) {
            throw new \InvalidArgumentException('node_name not match, should equalt to '.$server->getNodeName());
        }
        if (!in_array($data['protocol'], ['tars', 'non_tars'], true)) {
            throw new \InvalidArgumentException('protocol not match, should one of tars, non_tars');
        }
        if ($data['servant'] === $server->getServer().'.{ObjName}Obj') {
            throw new \InvalidArgumentException('please replace {ObjName} with your adapter name in servant');
        }
        $data['adapter_name'] = $data['servant'].'Adapter';

        if (empty($data['id'])) {
            $ret = $this->getTarsClient()->postJson('add_adapter_conf', $data);
        } else {
            $ret = $this->getTarsClient()->postJson('update_adapter_conf', $data);
        }
        if (!empty($ret['id'])) {
            $this->output->writeln('<info>save adapter config successfully</info>');
        }
    }
}
