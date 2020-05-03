<?php


namespace wenbinye\tars\cli\commands;


use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class PatchUploadCommand extends AbstractCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName("patch:upload");
        $this->addOption("comment", null, InputOption::VALUE_REQUIRED, "Patch comment");
        $this->addOption("apply", null, InputOption::VALUE_NONE, "Apply patch after upload");
        $this->addArgument("server", InputArgument::REQUIRED, "The server name or id");
        $this->addArgument("file", InputArgument::REQUIRED, "Patch file");
    }

    protected function handle(): void
    {
        $patchFile = $this->input->getArgument('file');
        $serverName = $this->getTarsClient()->getServerName($this->input->getArgument('server'));
        $ret = $this->getTarsClient()->post('upload_patch_package', [
            'multipart' => [
                [
                    'name' => 'application',
                    'contents' => $serverName->getApplication(),
                ],
                [
                    'name' => 'module_name',
                    'contents' => $serverName->getServerName()
                ],
                [
                    'name' => 'task_id',
                    'contents' => time()
                ],
                [
                    'name' => "comment",
                    "contents" => $this->input->getOption("comment") ?? ''
                ],
                [
                    'name' => 'md5',
                    'contents' => md5_file($patchFile)
                ],
                [
                    'name' => 'suse',
                    'contents' => fopen($patchFile, 'rb'),
                ],
            ]
        ]);
        if (isset($ret['id'])) {
            $this->output->writeln("<info>Upload patch to $serverName version {$ret['id']} successfully</info>");
            if ($this->input->getOption('apply')) {
                $command = $this->getApplication()->get("patch");
                $args = [
                    'command' => $command->getName(),
                    '--apply' => $ret['id'],
                    'server' => (string) $serverName
                ];
                $command->run(new ArrayInput($args), $this->output);
            }
        } else {
            $this->output->writeln("<error>Upload patch to $serverName fail</error>");
        }
    }
}