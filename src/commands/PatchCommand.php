<?php

declare(strict_types=1);

namespace wenbinye\tars\cli\commands;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class PatchCommand extends AbstractCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('patch');
        $this->setDescription('Uploads patch file');
        $this->addOption('comment', null, InputOption::VALUE_REQUIRED, 'Patch comment');
        $this->addOption('apply', null, InputOption::VALUE_NONE, 'Apply patch after upload');
        $this->addArgument('server', InputArgument::REQUIRED, 'The server app name or server full name or server id');
        $this->addArgument('file', InputArgument::REQUIRED, 'Patch file');
    }

    protected function handle(): void
    {
        $patchFile = $this->input->getArgument('file');
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
            if ($this->input->getOption('apply')) {
                $command = $this->getApplication()->get('patch');
                $args = [
                    'command' => $command->getName(),
                    '--apply' => $ret['id'],
                    'server' => (string) $serverName,
                ];
                $command->run(new ArrayInput($args), $this->output);
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
}
