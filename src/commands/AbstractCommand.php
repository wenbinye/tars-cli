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
use wenbinye\tars\cli\TarsClient;

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
            $config = Config::getInstance();
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

    abstract protected function handle(): void;
}
