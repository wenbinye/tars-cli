<?php

declare(strict_types=1);

namespace wenbinye\tars\cli\commands;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class AdapterCommand extends AbstractCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('adapter');
        $this->setDescription('Lists adapter of the server');
        $this->addOption('delete', null, InputOption::VALUE_REQUIRED, 'The adapter id to delete');
        $this->addArgument('server', InputArgument::REQUIRED, 'The server id or name');
    }

    protected function handle(): void
    {
        if ($this->input->getOption('delete')) {
            $this->deleteAdapter();
        } else {
            $this->listAdapters();
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

    protected function listAdapters(): void
    {
        $servers = $this->getTarsClient()->getServers($this->input->getArgument('server'));
        // 绑定地址	线程数	最大连接数	队列最大长度	队列超时时间(ms)	操作
        $table = $this->createTable(['ID', 'Adapter', 'Address', 'Thread Num', 'Max Conn', 'Queue Capacity', 'Queue Timeout']);
        foreach ($servers as $server) {
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
        }
        $table->render();
    }
}
