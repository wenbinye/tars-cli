<?php

declare(strict_types=1);

namespace wenbinye\tars\cli\commands;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use wenbinye\tars\cli\models\Server;

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
        $servers = $this->getTarsClient()->getServers($this->input->getArgument('server'));
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        if (count($servers) > 1) {
            $node = $helper->ask($this->input, $this->output, new ChoiceQuestion('Destroy the server', array_map(static function (Server $server): string {
                return $server->getNodeName();
            }, $servers)));
            foreach ($servers as $server) {
                if ($server->getNodeName() === $node) {
                    $this->removeServer($server);
                }
            }
        } else {
            $server = $servers[0];
            $question = new ConfirmationQuestion('Destroy server '.$server->getServer().' [y/N]:', false);
            if (!$helper->ask($this->input, $this->output, $question)) {
                return;
            }
            $this->removeServer($server);
        }
    }
}
