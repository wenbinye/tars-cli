<?php

namespace wenbinye\tars\cli\commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use wenbinye\tars\cli\Config;

class ConfigCommand extends Command
{
    protected function configure(): void
    {
        $this->setName("config")
        ->setDescription("Config API parameters");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $config = Config::getInstance();
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $config->setEndpoint($helper->ask($input, $output, $this->createQuestion("Web API URL", "http://localhost:3000")));
        $config->setToken($helper->ask($input, $output, $this->createQuestion("API Token")));
        Config::save($config);
        return 0;
    }

    /**
     * @param string $prompt
     * @param mixed $default
     * @return Question
     */
    protected function createQuestion(string $prompt, $default = null): Question
    {
        if (!empty($default)) {
            $prompt .= " (default $default)";
        }
        $question = new Question($prompt . ": ", $default);
        $question->setValidator(static function ($value) {
            if (empty($value)) {
                throw new \InvalidArgumentException("Should not be empty");
            }
            return $value;
        });
        return $question;
    }
}