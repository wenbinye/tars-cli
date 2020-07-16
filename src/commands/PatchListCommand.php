<?php

declare(strict_types=1);

namespace wenbinye\tars\cli\commands;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

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
    }

    protected function handle(): void
    {
        $this->listPatches();
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
}
