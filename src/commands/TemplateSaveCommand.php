<?php

declare(strict_types=1);

namespace wenbinye\tars\cli\commands;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class TemplateSaveCommand extends AbstractCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('template:save');
        $this->setDescription('Add or update template');
        $this->addOption('id', null, InputOption::VALUE_REQUIRED, 'The template id');
        $this->addOption('name', null, InputOption::VALUE_REQUIRED, 'The template name');
        $this->addOption('parent', null, InputOption::VALUE_REQUIRED, 'The parent name');
        $this->addArgument('template_file', InputArgument::REQUIRED, 'The file name of template profile');
    }

    protected function handle(): void
    {
        $templateId = $this->input->getOption('id');
        $name = $this->input->getOption('name');

        $file = $this->input->getArgument('template_file');
        $profile = file_get_contents('-' === $file ? 'php://stdin' : $file);
        if (false === $profile) {
            throw new \InvalidArgumentException("File $file cannot read");
        }
        if ($templateId || $name) {
            $template = $this->getTarsClient()->getTemplate($templateId ?: $name);
            if ($templateId && !$template) {
                throw new \InvalidArgumentException("模板 $templateId 不存在");
            }
            if ($template) {
                if ($name) {
                    $template['template_name'] = $name;
                }
                $template['profile'] = $profile;
                $this->updateTemplate($template);

                return;
            }
        }

        $result = $this->getTarsClient()->postJson('add_profile_template', [
            'template_name' => $name,
            'parents_name' => $this->input->getOption('parent'),
            'profile' => $profile,
        ]);
        if ($result) {
            $this->output->writeln("<info>添加模板 {$result['template_name']} 成功</info>");
        } else {
            $this->output->writeln("<error>添加模板 {$name} 失败</error>");
        }
    }

    protected function updateTemplate(array $template): void
    {
        $result = $this->getTarsClient()->postJson('update_profile_template', [
            'id' => $template['id'],
            'template_name' => $template['template_name'],
            'parents_name' => $this->input->getOption('parent') ?: $template['parents_name'],
            'profile' => $template['profile'],
        ]);
        if ($result) {
            $this->output->writeln("<info>更新模板 {$result['template_name']} 成功</info>");
        } else {
            $this->output->writeln("<error>更新模板 {$template['template_name']} 失败</error>");
        }
    }
}
