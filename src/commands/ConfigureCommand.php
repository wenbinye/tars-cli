<?php

declare(strict_types=1);

namespace wenbinye\tars\cli\commands;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use wenbinye\tars\cli\Config;

class ConfigureCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this->setName('configure')
            ->setDescription('Configures API parameters');
        $this->addOption('config', null, InputOption::VALUE_REQUIRED, 'config file path');
        $this->addOption('debug', null, InputOption::VALUE_NONE, 'show debug');
    }

    protected function handle(): void
    {
        $input = $this->input;
        $output = $this->output;
        $config = Config::getInstance();
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $config->setEndpoint($helper->ask($input, $output,
            $this->createQuestion('Web API URL', $config->getEndpoint() ?: 'http://localhost:3000')));
        $config->setToken($helper->ask($input, $output,
            $this->createQuestion('API Token', $config->getToken() ?: '', false)));
        $config->setTemplate($helper->ask($input, $output,
            $this->createQuestion('Default template', $config->getTemplate() ?: 'tars.tarsphp.default')));

        $config->setNode($helper->ask($input, $output,
            new ChoiceQuestion('Default node:', $this->getNodes(), $config->getNode())));
        Config::save($config, $input->getOption('config'));
    }

    protected function createQuestion(string $prompt, $default = null, bool $required = true): Question
    {
        if (!empty($default)) {
            $prompt .= " (default $default)";
        }
        $question = new Question($prompt.': ', $default);
        if ($required) {
            $question->setValidator(static function ($value) use ($prompt) {
                if (empty($value)) {
                    throw new \InvalidArgumentException($prompt.' should not be empty');
                }

                return $value;
            });
        }

        return $question;
    }

    private function getNodes(): array
    {
        return $this->getTarsClient()->get('node_list');
    }
}
