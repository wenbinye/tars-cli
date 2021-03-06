<?php

declare(strict_types=1);

namespace wenbinye\tars\cli\commands;

use Symfony\Component\Console\Input\InputArgument;
use wenbinye\tars\cli\Task;

class ServerRestartCommand extends AbstractCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('server:restart');
        $this->setDescription('Restarts the server');
        $this->addArgument('server', InputArgument::REQUIRED, 'The server id or name');
    }

    protected function handle(): void
    {
        $server = $this->getTarsClient()->getServer($this->input->getArgument('server'));
        Task::builder()
            ->setTarsClient($this->getTarsClient())
            ->setServerId($server->getId())
            ->setCommand('restart')
            ->setOnSuccess(function ($statusInfo) use ($server) {
                $this->output->writeln("<info>$statusInfo</info>");
                $this->output->writeln("<info>Server $server was restarted!</info>");
            })
            ->setOnFail(function ($statusInfo) use ($server) {
                $this->output->writeln("<error>$statusInfo</error>");
                $this->output->writeln("<error>Fail to restart $server</error>");
            })
            ->setOnRunning(function () {
                $this->output->writeln('<info>task is running</info>');
            })
            ->build()->run();
    }
}
