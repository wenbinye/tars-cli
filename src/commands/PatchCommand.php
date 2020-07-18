<?php

declare(strict_types=1);

namespace wenbinye\tars\cli\commands;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use wenbinye\tars\cli\Task;

class PatchCommand extends AbstractCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('patch');
        $this->setDescription('Uploads patch file or apply the patch');
        $this->addOption('comment', null, InputOption::VALUE_REQUIRED, 'Patch comment');
        $this->addOption('apply', null, InputOption::VALUE_REQUIRED, 'Apply patch id');
        $this->addOption('no-apply', null, InputOption::VALUE_OPTIONAL, 'Do not apply the uploaded patch');
        $this->addArgument('server', InputArgument::REQUIRED, 'The server app name or server full name or server id');
        $this->addArgument('file', InputArgument::OPTIONAL, 'Patch file, required if upload patch');
    }

    protected function handle(): void
    {
        if ($this->input->getOption('apply')) {
            $this->applyPatch($this->input->getOption('apply'),
                $this->input->getArgument('server'));
        } else {
            $this->uploadPatch();
        }
    }

    private function uploadPatch(): void
    {
        $patchFile = $this->input->getArgument('file');
        if (!$patchFile) {
            throw new \InvalidArgumentException('file 参数不能为空');
        }
        $server = $this->input->getArgument('server');
        if ($this->lookLikeApp($server)) {
            $server .= '.'.explode('_', basename($patchFile), 2)[0];
        }
        $serverName = $this->getTarsClient()->getServerName($server);
        $ret = $this->getTarsClient()->post('upload_patch_package', [
            'multipart' => $this->buildMultipart([
                'application' => $serverName->getApplication(),
                'module_name' => $serverName->getServerName(),
                'task_id' => time(),
                'comment' => $this->input->getOption('comment') ?? '',
                'md5' => md5_file($patchFile),
                'suse' => fopen($patchFile, 'rb'),
            ]),
        ]);
        if (isset($ret['id'])) {
            $this->output->writeln("<info>Upload patch to $serverName version {$ret['id']} successfully</info>");
            if ($this->input->getOption('no-apply')) {
                return;
            }
            $this->applyPatch($ret['id'], $serverName);
        } else {
            $this->output->writeln("<error>Upload patch to $serverName fail</error>");
        }
    }

    private function applyPatch($patchId, $serverName): void
    {
        $server = $this->getTarsClient()->getServer($serverName);

        Task::builder()
            ->setTarsClient($this->getTarsClient())
            ->setServerId($server->getId())
            ->setCommand('patch_tars')
            ->setParameters([
                'patch_id' => $patchId,
                'bak_flag' => false,
                'update_text' => '',
            ])
            ->setOnSuccess(function ($statusInfo) use ($patchId, $server) {
                $this->output->writeln("> <info> $statusInfo</info>");
                $this->output->writeln("<info>Apply patch $patchId to $server successfully</info>");
            })
            ->setOnFail(function ($statusInfo) use ($patchId, $server) {
                $this->output->writeln("> <error> $statusInfo</error>");
                $this->output->writeln("<error>Fail to apply patch $patchId to $server</error>");
            })
            ->setOnRunning(function () {
                $this->output->writeln('<info>task is running</info>');
            })
            ->build()->run();
    }

    protected function buildMultipart(array $multipart): array
    {
        $data = [];
        foreach ($multipart as $key => $value) {
            $data[] = ['name' => $key, 'contents' => $value];
        }

        return $data;
    }

    private function lookLikeApp(string $server): bool
    {
        return !is_numeric($server) && false === strpos($server, '.');
    }
}
