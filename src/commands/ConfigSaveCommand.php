<?php

declare(strict_types=1);

namespace wenbinye\tars\cli\commands;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use wenbinye\tars\cli\ConfigLevel;

class ConfigSaveCommand extends AbstractCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('config:save');
        $this->addOption('id', null, InputOption::VALUE_REQUIRED, '更新配置');
        $this->addOption('name', null, InputOption::VALUE_REQUIRED, '文件名');
        $this->addOption('level', null, InputOption::VALUE_REQUIRED, '配置级别，可选值app|set|area|group|server', 'server');
        $this->addOption('file', null, InputOption::VALUE_REQUIRED, '配置内容');
        $this->addOption('reason', null, InputOption::VALUE_REQUIRED, '更新备注');
        $this->addArgument('server', InputArgument::OPTIONAL, 'Server id or name');
        $this->setDescription('更新配置');
    }

    protected function handle(): void
    {
        $file = $this->input->getOption('file');
        if (!$file) {
            throw new \InvalidArgumentException('--file 参数必须指定');
        }
        if ('-' === $file) {
            $file = 'php://stdin';
        }
        $content = file_get_contents($file);
        if ($this->input->getOption('id')) {
            $reason = $this->input->getOption('reason');
            if (!$reason) {
                throw new \InvalidArgumentException('--reason 不能为空');
            }
            $result = $this->getTarsClient()->postJson('update_config_file', [
                'id' => $this->input->getOption('id'),
                'config' => $content,
                'reason' => $reason,
            ]);
            $this->output->writeln("<info>成功更新 {$result['server_name']} 配置 {$result['filename']}</info>");
        } else {
            $serverId = $this->input->getArgument('server');
            if (!$serverId) {
                throw new \InvalidArgumentException('server 不能为空');
            }
            $serverName = $this->getTarsClient()->getServerName($serverId);
            $level = ConfigLevel::fromName($this->input->getOption('level'));
            if (!$level) {
                throw new \InvalidArgumentException('层级不正确');
            }
            $name = $this->input->getOption('name');
            if (!$name) {
                throw new \InvalidArgumentException('--name 参数文件名不能为空');
            }
            try {
                $configId = $this->getConfigByName($name);
                $reason = $this->input->getOption('reason') ?? date('Y-m-d').' 更新配置';
                $result = $this->getTarsClient()->postJson('update_config_file', [
                    'id' => $configId,
                    'config' => $content,
                    'reason' => $reason,
                ]);
                $this->output->writeln("<info>成功更新 {$result['server_name']} 配置 {$result['filename']}</info>");
            } catch (\InvalidArgumentException $e) {
                $result = $this->getTarsClient()->postJson('add_config_file', [
                    'level' => $level,
                    'application' => $serverName->getApplication(),
                    'server_name' => $serverName->getServerName(),
                    'filename' => $name,
                    'config' => $content,
                ]);
                $this->output->writeln("<info>成功添加 {$result['server_name']} 配置 {$result['filename']}</info>");
            }
        }
    }

    private function getConfigByName($configName): int
    {
        foreach ($this->getConfigFiles() as $file) {
            if ($file['filename'] === $configName) {
                return (int) $file['id'];
            }
        }
        throw new \InvalidArgumentException("配置文件 $configName 不存在");
    }

    protected function getConfigFiles(): array
    {
        $level = ConfigLevel::fromName($this->input->getOption('level'));
        if (!isset($level)) {
            throw new \InvalidArgumentException('level 级别不正确');
        }
        $serverId = $this->input->getArgument('server');
        if (!$serverId) {
            throw new \InvalidArgumentException('server 不能为空');
        }
        $serverName = $this->getTarsClient()->getServerName($serverId);

        return $this->getTarsClient()->get('config_file_list', [
            'application' => $serverName->getApplication(),
            'server_name' => $serverName->getServerName(),
            'level' => $level,
        ]);
    }
}
