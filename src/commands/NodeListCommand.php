<?php

declare(strict_types=1);

namespace wenbinye\tars\cli\commands;

use Carbon\Carbon;

class NodeListCommand extends AbstractCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('node:list');
        $this->setDescription('Lists nodes');
    }

    /**
     * [id] => 116
     * [node_name] => 192.168.0.216
     * [node_obj] => tars.tarsnode.NodeObj@tcp -h 192.168.0.216 -p 19385 -t 60000
     * [endpoint_ip] => 192.168.0.216
     * [endpoint_port] => 19385
     * [data_dir] => /usr/local/app/tars/tarsnode/data
     * [load_avg1] => 0.2
     * [load_avg5] => 0
     * [load_avg15] => 0
     * [last_reg_time] => 2020-08-15T02:54:02.000Z
     * [last_heartbeat] => 2020-11-03T03:29:14.000Z
     * [setting_state] => active
     * [present_state] => active
     * [tars_version] => 2.4.4.20200305
     * [template_name] =>
     * [modify_time] => 2020-11-03T03:29:14.000Z
     * [group_id] => -1.
     */
    protected function handle(): void
    {
        $ret = $this->getTarsClient()->get('list_tars_node', [
            'page_size' => 40,
        ]);
        $table = $this->createTable(['Node', 'Status', 'Created At', 'Last Active Time', 'Version', 'Load']);
        $tz = date_default_timezone_get();
        foreach ($ret['rows'] as $row) {
            $table->addRow([
                $row['node_name'],
                $row['setting_state'],
                Carbon::parse($row['last_reg_time'])->setTimezone($tz)->toDateTimeString(),
                Carbon::parse($row['last_heartbeat'])->setTimezone($tz)->toDateTimeString(),
                $row['tars_version'],
                $row['load_avg15'],
            ]);
        }
        $table->render();
    }

    private function listServers(): void
    {
        $table = $this->createTable(['Server']);
        $includeTars = $this->input->getOption('all');

        foreach ($this->getTarsClient()->getServerNames() as $server) {
            if (!$includeTars && 'tars' === $server->getApplication()) {
                continue;
            }
            $table->addRow([(string) $server]);
        }
        $table->render();
    }

    private function listAppServers(): void
    {
        $serverId = $this->input->getArgument('server');
        if (!empty($serverId)) {
            $servers = [$this->getTarsClient()->getServer($serverId)];
        } else {
            $app = $this->input->getOption('app');
            $servers = $this->getTarsClient()->getServers($app);
        }
        $table = $this->createTable(['ID', 'Server', 'Node', 'Setting', 'Present', 'PID', 'Patch', 'Patched At']);
        foreach ($servers as $server) {
            $table->addRow([$server->getId(),
                (string) $server,
                $server->getNodeName(),
                $this->stateDesc($server->getSettingState()),
                $this->stateDesc($server->getPresentState()),
                $server->getProcessId(),
                $server->getPatchVersion(),
                $server->getPatchTime() ? $server->getPatchTime()->toDateTimeString() : '', ]);
        }
        $table->render();
    }

    private function stateDesc(string $state): string
    {
        if (!empty($state)) {
            $tag = 'active' === $state ? 'info' : 'error';

            return "<$tag>$state</$tag>";
        }

        return $state;
    }
}
