<?php

declare(strict_types=1);

namespace wenbinye\tars\cli\commands;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class TemplateCommand extends AbstractCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('template');
        $this->setDescription('Query profile template');
        $this->addOption('parent', null, InputOption::VALUE_REQUIRED, 'Parent template name');
        $this->addOption('out', null, InputOption::VALUE_REQUIRED, 'Output file name');
        $this->addArgument('name', InputArgument::OPTIONAL, 'Template name');
    }

    protected function handle(): void
    {
        if ($this->input->getArgument('name')) {
            $this->showTemplate();
        } else {
            $this->listTemplates();
        }
    }

    private function listTemplates(): void
    {
        $templates = $this->getTarsClient()->get('query_profile_template', [
            'parents_name' => $this->input->getOption('parent'),
        ]);
        $table = $this->createTable(['ID', '模板',	'父模板名',	'最后修改时间']);
        foreach ($templates as $template) {
            $table->addRow([$template['id'], $template['template_name'], $template['parents_name'], $template['posttime']]);
        }
        $table->render();
    }

    private function showTemplate(): void
    {
        $templateId = $this->input->getArgument('name');
        $template = $this->getTarsClient()->getTemplate($templateId);
        if (!$template) {
            throw new \InvalidArgumentException("Template not found for $templateId");
        }
        $out = $this->input->getOption('out');
        if ($out) {
            file_put_contents('-' === $out ? 'php://stdout' : $out, $template['profile']);
        } else {
            $table = $this->createTable(['Name', 'Value']);
            $table->addRow(['ID', $template['id']]);
            $table->addRow(['模板', $template['template_name']]);
            $table->addRow(['父模板名', $template['parents_name']]);
            $table->addRow(['最后修改时间', $template['posttime']]);
            $table->addRow(['内容', $template['profile']]);
            $table->render();
        }
    }
}
