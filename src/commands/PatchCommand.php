<?php

declare(strict_types=1);

namespace wenbinye\tars\cli\commands;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

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
            $servers = $this->getTarsClient()->getServers($this->input->getArgument('server'));
            foreach ($servers as $server) {
                $this->applyPatch((int) $this->input->getOption('apply'), $server);
            }
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
            $server .= '.'.$this->extractServerName($patchFile);
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
            foreach ($this->getTarsClient()->getServers($server) as $server) {
                $this->applyPatch((int) $ret['id'], $server);
            }
        } else {
            $this->output->writeln("<error>Upload patch to $serverName fail</error>");
        }
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

    private function extractServerName(string $file): string
    {
        if (preg_match('#^(.*)_\d+\.#', basename($file), $matches)) {
            return $matches[1];
        }
        throw new \InvalidArgumentException("Cannot extract server name from '$file', not match regexp .*_\d+\.");
    }
}
