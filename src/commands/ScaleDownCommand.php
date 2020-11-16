<?php

declare(strict_types=1);

namespace wenbinye\tars\cli\commands;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Question\ChoiceQuestion;

class ScaleDownCommand extends AbstractCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('scale:down');
        $this->setDescription('Scale down the server');
        $this->addArgument('server', InputArgument::REQUIRED, 'The server name');
        $this->addArgument('node', InputArgument::OPTIONAL, 'The node name');
    }

    protected function handle(): void
    {
        $serverName = $this->input->getArgument('server');
        if (is_numeric($serverName)) {
            $server = $this->getTarsClient()->getServer($serverName);
        } else {
            $servers = $this->getTarsClient()->getServers($serverName);
            $usedNodes = [];
            foreach ($servers as $server) {
                $usedNodes[] = $server->getNodeName();
            }
            $node = $this->input->getArgument('node');
            if (!$node) {
                /** @var QuestionHelper $helper */
                $helper = $this->getHelper('question');
                $node = $helper->ask($this->input, $this->output, new ChoiceQuestion('choose node: ', $usedNodes));
            }
            if (!in_array($node, $usedNodes, true)) {
                throw new \InvalidArgumentException("$node 不正确，必须是".implode(',', $usedNodes).'其中之一');
            }
            $server = null;
            foreach ($servers as $one) {
                if ($one->getNodeName() === $node) {
                    $server = $one;
                    break;
                }
            }
        }
        $this->stopServer($server);
        $this->removeServer($server);
    }
}
