<?php

declare(strict_types=1);

namespace wenbinye\tars\cli\commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use wenbinye\tars\cli\Config;

class ConfigureCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('configure')
            ->setDescription('Configures API parameters');
        $this->addOption('config', null, InputOption::VALUE_REQUIRED, 'config file path');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $config = Config::getInstance();
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $config->setEndpoint($helper->ask($input, $output, $this->createQuestion('Web API URL', 'http://localhost:3000')));
        $config->setToken($helper->ask($input, $output, $this->createQuestion('API Token')));
        Config::save($config, $input->getOption('config'));

        return 0;
    }

    protected function createQuestion(string $prompt, $default = null): Question
    {
        if (!empty($default)) {
            $prompt .= " (default $default)";
        }
        $question = new Question($prompt.': ', $default);
        $question->setValidator(static function ($value) {
            if (empty($value)) {
                throw new \InvalidArgumentException('Should not be empty');
            }

            return $value;
        });

        return $question;
    }
}
