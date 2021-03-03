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
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use wenbinye\tars\cli\Config;
use wenbinye\tars\cli\exception\NotStartException;
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
     * @var StyleInterface
     */
    protected $io;

    /**
     * @var TarsClient
     */
    private $tarsClient;

    protected function configure(): void
    {
        $this->addOption('format', null, InputOption::VALUE_REQUIRED, 'output format, ascii|json', 'ascii');
        $this->addOption('config', null, InputOption::VALUE_REQUIRED, 'config file path');
        $this->addOption('debug', null, InputOption::VALUE_NONE, 'show debug');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;
        $this->io = new SymfonyStyle($this->input, $this->output);

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
        $retries = 10;
        while ($retries > 0) {
            try {
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
                    ->setOnNotStart(function ($statusInfo) use ($patchId, $server) {
                        throw new NotStartException("Fail to apply patch $patchId to $server: ".$statusInfo);
                    })
                    ->setOnRunning(function () {
                        $this->output->writeln('<info>task is running</info>');
                    })
                    ->build()
                    ->run();
                break;
            } catch (NotStartException $e) {
                --$retries;
                if ($retries <= 0) {
                    throw new \RuntimeException('patch failed', 0, $e);
                }
                sleep(5);
                $this->output->writeln("<error>retry patch, error: {$e->getMessage()}</error>");
            }
        }
    }

    protected function runOnServer(Server $server, string $cmd): void
    {
        Task::builder()
            ->setTarsClient($this->getTarsClient())
            ->setServerId($server->getId())
            ->setCommand($cmd)
            ->setOnSuccess(function ($statusInfo) use ($server, $cmd) {
                $this->io->text((string) $statusInfo);
                $this->io->success("Server $server was {$cmd}ed!");
            })
            ->setOnFail(function ($statusInfo) use ($server, $cmd) {
                $this->io->error((string) $statusInfo);
                $this->io->error("Fail to $cmd $server");
            })
            ->setOnRunning(function () {
                $this->io->text('task is running');
            })
            ->build()
            ->run();
    }

    protected function startServer(Server $server): void
    {
        $this->runOnServer($server, 'start');
    }

    protected function restartServer(Server $server): void
    {
        $this->runOnServer($server, 'restart');
    }

    protected function stopServer(Server $server): void
    {
        $this->runOnServer($server, 'stop');
    }

    protected function removeServer(Server $server): void
    {
        $this->runOnServer($server, 'undeploy_tars');
    }

    abstract protected function handle(): void;

    protected function writeTable(array $header, array $rows, ?string $footer = null): void
    {
        if (!empty($rows)) {
            if ($this->isAscii()) {
                $table = $this->createTable($header);
                $table->addRows($rows);
                $table->render();
                if (isset($footer)) {
                    $this->output->writeln("<info>$footer</info>");
                }
            } else {
                $this->output->writeln(json_encode(
                    array_map(static function ($row) use ($header) {
                        return array_combine($header, $row);
                    }, $rows),
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
                ));
            }
        }
    }

    protected function isAscii(): bool
    {
        return 'ascii' === $this->input->getOption('format');
    }
}
