<?php

declare(strict_types=1);

namespace wenbinye\tars\cli\commands;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use wenbinye\tars\cli\ConfigLevel;

class ConfigListCommand extends AbstractCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('config:list');
        $this->setDescription('Lists config');
        $this->addOption('level', null, InputOption::VALUE_REQUIRED, '配置级别，可选值app|set|area|group|server', 'server');
        $this->addOption('id', null, InputOption::VALUE_REQUIRED, '配置ID');
        $this->addOption('name', null, InputOption::VALUE_REQUIRED, '配置Name');
        $this->addOption('out', null, InputOption::VALUE_REQUIRED, '输出文件');
        $this->addArgument('server', InputArgument::OPTIONAL, 'Server id or name');
    }

    protected function handle(): void
    {
        if ($configId = $this->input->getOption('id')) {
            $this->showConfigById($configId);
        } elseif ($configName = $this->input->getOption('name')) {
            $this->showConfigByName($configName);
        } else {
            $this->showConfigFileList();
        }
    }

    protected function showConfigFileList(): void
    {
        $files = $this->getConfigFiles();
        $table = $this->createTable(['ID', '服务', '文件名称', '最后修改时间']);
        foreach ($files as $file) {
            $table->addRow([$file['id'], $file['server_name'], $file['filename'], $file['posttime']]);
        }
        $table->render();
    }

    private function showConfigByName($configName): void
    {
        foreach ($this->getConfigFiles() as $file) {
            if ($file['filename'] === $configName) {
                $this->showConfigById($file['id']);

                return;
            }
        }
        throw new \InvalidArgumentException("配置文件 $configName 不存在");
    }

    private function showConfigById($configId): void
    {
        $result = $this->getTarsClient()->get('config_file', ['id' => $configId]);
        if (empty($result)) {
            throw new \InvalidArgumentException("配置 $configId 不存在");
        }
        $output = $this->input->getOption('out');
        if ($output) {
            if ('-' === $output) {
                $output = 'php://stdout';
            }
            file_put_contents($output, $result['config']);
        } else {
            $this->output->writeln('服务: '.$result['server_name']);
            $this->output->writeln('更新时间: '.$result['posttime']);
            $this->output->writeln('文件名: '.$result['filename']);
            $this->output->writeln('层级: '.ConfigLevel::getName($result['level']));
            $this->output->writeln('文件内容:');
            $this->output->writeln($result['config']);
        }
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
