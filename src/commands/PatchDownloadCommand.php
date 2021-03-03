<?php

declare(strict_types=1);

namespace wenbinye\tars\cli\commands;

use Symfony\Component\Console\Input\InputArgument;

class PatchDownloadCommand extends AbstractCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('patch:download');
        $this->setDescription('Download the server patch');
        $this->addArgument('patch_id', InputArgument::REQUIRED, 'The patch id');
        $this->addArgument('output', InputArgument::REQUIRED, 'Output file');
    }

    protected function handle(): void
    {
        $this->getTarsClient()->request('GET', 'download_package', [
            'query' => [
                'id' => $this->input->getArgument('patch_id'),
            ],
            'sink' => $this->input->getArgument('output'),
        ]);
    }
}
