<?php

declare(strict_types=1);

namespace wenbinye\tars\cli\commands;

use Symfony\Component\Console\Input\InputOption;

class ConfigDeleteCommand extends AbstractCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('config:delete');
        $this->addOption('id', null, InputOption::VALUE_REQUIRED, '更新配置');
        $this->setDescription('删除配置');
    }

    protected function handle(): void
    {
        $configId = $this->input->getOption('id');
        $result = $this->getTarsClient()->get('config_file', ['id' => $configId]);
        if (empty($result)) {
            throw new \InvalidArgumentException("配置 $configId 不存在");
        }
        $tempnam = tempnam(sys_get_temp_dir(), $result['server_name'].'_'.$configId);
        file_put_contents($tempnam, $result['config']);
        $deleteId = $this->getTarsClient()->get('delete_config_file', ['id' => $configId]);
        if (empty($deleteId)) {
            $this->output->writeln("<error>删除 {$result['server_name']} 配置 {$result['filename']} 失败</error>");
        } else {
            $this->output->writeln("<info>成功删除 {$result['server_name']} 配置 {$result['filename']}</info>");
            $this->output->writeln("文件临时备份到 $tempnam");
        }
    }
}
