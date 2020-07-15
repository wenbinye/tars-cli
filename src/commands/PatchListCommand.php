<?php

declare(strict_types=1);

namespace wenbinye\tars\cli\commands;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use wenbinye\tars\cli\Task;

class PatchListCommand extends AbstractCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('patch:list');
        $this->setDescription('Lists server patches and applies the server patch');
        $this->addArgument('server', InputArgument::REQUIRED, 'The server name or id');
        $this->addOption('page', null, InputOption::VALUE_REQUIRED, 'Page', 0);
        $this->addOption('page-size', null, InputOption::VALUE_REQUIRED, 'Page size', 50);
        $this->addOption('apply', null, InputOption::VALUE_REQUIRED, 'Apply patch version');
    }

    protected function handle(): void
    {
        if ($this->input->getOption('apply')) {
            $this->applyPatch();
        } else {
            $this->listPatches();
        }
    }

    private function listPatches(): void
    {
        $serverId = $this->input->getArgument('server');
        $server = $this->getTarsClient()->getServerName($serverId);
        $ret = $this->getTarsClient()->get('server_patch_list', [
            'application' => $server->getApplication(),
            'module_name' => $server->getServerName(),
            'curr_page' => $this->input->getOption('page'),
            'page_size' => $this->input->getOption('page-size'),
        ]);
        if (!empty($ret['rows'])) {
            $table = $this->createTable(['Version', 'Server', 'Created At']);
            foreach ($ret['rows'] as $row) {
                $table->addRow([$row['id'], $row['server'], $row['posttime']]);
            }
            $table->render();
        }
        $this->output->writeln("<info>Total {$ret['count']} patches.</info>");
    }

    private function applyPatch(): void
    {
        $server = $this->getTarsClient()->getServer($this->input->getArgument('server'));
        $patchVersion = $this->input->getOption('apply');

        Task::builder()
            ->setTarsClient($this->getTarsClient())
            ->setServerId($server->getId())
            ->setCommand('patch_tars')
            ->setParameters([
                'patch_id' => $patchVersion,
                'bak_flag' => false,
                'update_text' => '',
            ])
            ->setOnSuccess(function ($statusInfo) use ($patchVersion, $server) {
                $this->output->writeln("<info>$statusInfo</info>");
                $this->output->writeln("<info>Apply patch $patchVersion to $server successfully</info>");
            })
            ->setOnFail(function ($statusInfo) use ($patchVersion, $server) {
                $this->output->writeln("<error>$statusInfo</error>");
                $this->output->writeln("<error>Fail to apply patch $patchVersion to $server</error>");
            })
            ->setOnRunning(function () {
                $this->output->writeln('<info>task is running</info>');
            })
            ->build()->run();
    }
}
