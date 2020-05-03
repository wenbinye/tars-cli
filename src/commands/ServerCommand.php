<?php


namespace wenbinye\tars\cli\commands;


use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ServerCommand extends AbstractCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->addOption("all", "a", InputOption::VALUE_NONE, "Display all application include tars");
        $this->addOption("app", null, InputOption::VALUE_REQUIRED, "Display server of the app");
        $this->addArgument('server', InputArgument::OPTIONAL, "Server id or name");
        $this->setName("server");
    }

    protected function handle(): void
    {
        if ($this->input->getOption("app")
            || $this->input->getArgument('server')) {
            $this->listAppServers();
        } else {
            $this->listServers();
        }
    }

    private function listServers(): void
    {
        $table = $this->createTable(['Server']);
        $includeTars = $this->input->getOption("all");

        foreach ($this->getTarsClient()->getServerNames() as $server) {
            if (!$includeTars && $server->getApplication() === 'tars') {
                continue;
            }
            $table->addRow([(string)$server]);
        }
        $table->render();
    }

    private function listAppServers(): void
    {
        $serverId = $this->input->getArgument('server');
        if (!empty($serverId)) {
            $servers = [$this->getTarsClient()->getServer($serverId)];
        } else {
            $app = $this->input->getOption("app");
            $servers = $this->getTarsClient()->getServers($app);
        }
        $table = $this->createTable(['ID', 'Server', 'Node', 'Setting', 'Present', 'PID', 'Patch', 'Patched At']);
        foreach ($servers as $server) {
            $table->addRow([$server->getId(),
                (string)$server,
                $server->getNodeName(),
                $this->stateDesc($server->getSettingState()),
                $this->stateDesc($server->getPresentState()),
                $server->getProcessId(),
                $server->getPatchVersion(),
                $server->getPatchTime() ? $server->getPatchTime()->toDateTimeString() : '']);
        }
        $table->render();
    }

    private function stateDesc(string $state): string
    {
        if (!empty($state)) {
            $tag = $state === 'active' ? 'info' : 'error';
            return "<$tag>$state</$tag>";
        }
        return $state;
    }
}