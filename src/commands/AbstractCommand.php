<?php

declare(strict_types=1);

namespace wenbinye\tars\cli\commands;

use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use wenbinye\tars\cli\Config;
use wenbinye\tars\cli\models\Server;
use wenbinye\tars\cli\TarsClient;
use wenbinye\tars\cli\Task;

abstract class AbstractCommand extends Command
{
    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var TarsClient
     */
    private $tarsClient;

    protected function configure(): void
    {
        $this->addOption('format', null, InputOption::VALUE_REQUIRED, 'output format, ascii|json');
        $this->addOption('config', null, InputOption::VALUE_REQUIRED, 'config file path');
        $this->addOption('debug', null, InputOption::VALUE_NONE, 'show debug');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;
        $this->handle();

        return 0;
    }

    protected function getTarsClient(): TarsClient
    {
        if (!$this->tarsClient) {
            $logger = new Logger('tars', [new ErrorLogHandler()]);
            $config = $this->input->getOption('config') ? Config::read($this->input->getOption('config'))
                : Config::getInstance();
            if (!$config->getEndpoint()) {
                throw new \InvalidArgumentException('API not config. Use config command set endpoint first.');
            }
            $this->tarsClient = new TarsClient($config, $logger, $this->input->getOption('debug'));
        }

        return $this->tarsClient;
    }

    protected function get($uri, array $query = [])
    {
        return $this->getTarsClient()->get($uri, $query);
    }

    protected function post($uri, $options = [])
    {
        return $this->getTarsClient()->post($uri, $options);
    }

    protected function postJson($uri, $data)
    {
        return $this->getTarsClient()->postJson($uri, $data);
    }

    protected function createTable(array $headers): Table
    {
        $table = new Table($this->output);
        $table->setHeaders($headers);

        return $table;
    }

    protected function readJson()
    {
        $data = $this->input->getOption('json');
        if ('-' === $data) {
            $data = file_get_contents('php://stdin');
        } elseif (!in_array($data[0], ['{', '['], true)) {
            $json = file_get_contents($data);
            if (!$json) {
                throw new \InvalidArgumentException("Cannot read file $data");
            }
            $data = $json;
        }

        return json_decode($data, true);
    }

    protected function applyPatch(int $patchId, Server $server): void
    {
        Task::builder()
            ->setTarsClient($this->getTarsClient())
            ->setServerId($server->getId())
            ->setCommand('patch_tars')
            ->setParameters([
                'patch_id' => $patchId,
                'bak_flag' => false,
                'update_text' => '',
            ])
            ->setOnSuccess(function ($statusInfo) use ($patchId, $server) {
                $this->output->writeln("> <info> $statusInfo</info>");
                $this->output->writeln("<info>Apply patch $patchId to $server successfully</info>");
            })
            ->setOnFail(function ($statusInfo) use ($patchId, $server) {
                $this->output->writeln("> <error> $statusInfo</error>");
                $this->output->writeln("<error>Fail to apply patch $patchId to $server</error>");
            })
            ->setOnRunning(function () {
                $this->output->writeln('<info>task is running</info>');
            })
            ->build()
            ->run();
    }

    protected function stopServer(Server $server): void
    {
        Task::builder()
            ->setTarsClient($this->getTarsClient())
            ->setServerId($server->getId())
            ->setCommand('stop')
            ->setOnSuccess(function ($statusInfo) use ($server) {
                $this->output->writeln("> <info>$statusInfo</info>");
                $this->output->writeln("<info>Server $server was stopped!</info>");
            })
            ->setOnFail(function ($statusInfo) use ($server) {
                $this->output->writeln("> <error>$statusInfo</error>");
                $this->output->writeln("<error>Fail to stop $server</error>");
            })
            ->setOnRunning(function () {
                $this->output->writeln('<info>task is running</info>');
            })
            ->build()->run();
    }

    protected function removeServer(Server $server): void
    {
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
            ->build()
            ->run();
    }

    abstract protected function handle(): void;
}
