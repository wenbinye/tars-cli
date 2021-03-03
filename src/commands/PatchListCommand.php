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
        $pageSize = (int) $this->input->getOption('page-size');
        $ret = $this->getTarsClient()->get('server_patch_list', [
            'application' => $server->getApplication(),
            'module_name' => $server->getServerName(),
            'curr_page' => $this->input->getOption('page'),
            'page_size' => $pageSize,
        ]);
        $this->writeTable(
            ['Version', 'Server', 'Created At'],
            array_map(static function ($row) {
                return [$row['id'], $row['server'], $row['posttime']];
            }, array_slice($ret['rows'] ?? [], 0, $pageSize)),
            "Total {$ret['count']} patches"
        );
    }
}
