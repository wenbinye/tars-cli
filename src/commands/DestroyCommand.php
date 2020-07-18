<?php

declare(strict_types=1);

namespace wenbinye\tars\cli\commands;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use wenbinye\tars\cli\Task;

class DestroyCommand extends AbstractCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('destroy');
        $this->setDescription('Destroy server deployment');
        $this->addArgument('server', InputArgument::REQUIRED, 'The server id or name');
    }

    protected function handle(): void
    {
        $server = $this->getTarsClient()->getServer($this->input->getArgument('server'));
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Destroy server '.$server->getServer().' [y/N]:', false);
        if (!$helper->ask($this->input, $this->output, $question)) {
            return;
        }
        Task::builder()
            ->setTarsClient($this->getTarsClient())
            ->setServerId($server->getId())
            ->setCommand('undeploy_tars')
            ->setOnSuccess(function ($statusInfo) use ($server) {
                $this->output->writeln("> <info>$statusInfo</info>");
                $this->output->writeln("<info>Server $server was destroyed!</info>");
            })
            ->setOnFail(function ($statusInfo) use ($server) {
                $this->output->writeln("> <error>$statusInfo</error>");
                $this->output->writeln("<error>Fail to destroy $server</error>");
            })
            ->setOnRunning(function () {
                $this->output->writeln('<info>task is running</info>');
            })
            ->build()->run();
    }
}
