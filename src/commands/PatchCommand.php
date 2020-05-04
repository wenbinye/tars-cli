<?php

declare(strict_types=1);

namespace wenbinye\tars\cli\commands;

use Symfony\Component\Console\Input\InputOption;

class PatchCommand extends AbstractCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('patch');
        $this->setDescription('Lists server patches and applies the server patch');
        $this->addArgument('server', InputOption::VALUE_REQUIRED, 'The server name or id');
        $this->addOption('page', null, InputOption::VALUE_REQUIRED, 'Page', 0);
        $this->addOption('page-size', null, InputOption::VALUE_REQUIRED, 'Page size', 50);
        $this->addOption('apply', null, InputOption::VALUE_REQUIRED, 'Apply patch version');
    }

    protected function handle(): void
    {
        if ($this->input->getOption('apply')) {
            $this->applyPatch();
        } else {
            $this->listPatches();
        }
    }

    private function listPatches(): void
    {
        $server = $this->getTarsClient()->getServerName($this->input->getArgument('server'));
        $ret = $this->getTarsClient()->get('server_patch_list', [
            'application' => $server->getApplication(),
            'module_name' => $server->getServerName(),
            'curr_page' => $this->input->getOption('page'),
            'page_size' => $this->input->getOption('page-size'),
        ]);
        if (!empty($ret['rows'])) {
            $table = $this->createTable(['Version', 'Server', 'Created At']);
            foreach ($ret['rows'] as $row) {
                $table->addRow([$row['id'], $row['server'], $row['posttime']]);
            }
            $table->render();
        }
        $this->output->writeln("<info>Total {$ret['count']} patches.</info>");
    }

    private function applyPatch(): void
    {
        $server = $this->getTarsClient()->getServer($this->input->getArgument('server'));
        $patchVersion = $this->input->getOption('apply');
        $taskNo = $this->getTarsClient()->postJson('add_task', [
            'serial' => true,
            'items' => [
                [
                    'server_id' => $server->getId(),
                    'command' => 'patch_tars',
                    'parameters' => [
                        'patch_id' => $patchVersion,
                        'bak_flag' => false,
                        'update_text' => '',
                    ],
                ],
            ],
        ]);
        if (empty($taskNo)) {
            $this->output->writeln("<error>Fail to apply patch version $patchVersion</error>");
        }
        $retries = 15;
        while ($retries-- > 0) {
            $ret = $this->getTarsClient()->get('task', ['task_no' => $taskNo]);
            $status = $ret['items'][0]['status_info'] ?? null;
            if (!$status) {
                throw new \InvalidArgumentException("Cannot get task status $taskNo");
            }
            if ('EM_I_SUCCESS' === $status) {
                $this->output->writeln('<info>'.$ret['items'][0]['execute_info'].'</info>');
                $this->output->writeln("<info>Apply patch $patchVersion to $server successfully</info>");

                return;
            }

            if ('EM_I_FAILED' === $status) {
                $this->output->writeln('<error>'.$ret['items'][0]['execute_info'].'</error>');
                $this->output->writeln("<error>Fail to apply patch $patchVersion to $server</error>");

                return;
            }

            if ('EM_I_RUNNING' !== $status) {
                $this->output->writeln("<error>Unknown status $status</error>");

                return;
            }
            $this->output->writeln('<info>task is running</info>');
            sleep(2);
        }
        $this->output->writeln("<error>Cannot get task status $taskNo</error>");
    }
}
