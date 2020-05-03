<?php


namespace wenbinye\tars\cli\commands;


use Symfony\Component\Console\Input\InputOption;

class ServerDeployCommand extends AbstractCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName("server:deploy");
        $this->addOption("json", null, InputOption::VALUE_REQUIRED, "server definition in json file");
        $this->addOption("template", null, InputOption::VALUE_NONE, "output deploy template");
    }

    protected function handle(): void
    {
        if ($this->input->getOption("template")) {
            echo json_encode($this->createDeployTemplate(), JSON_PRETTY_PRINT), "\n";
        } else {
            $data = $this->input->getOption('json');
            if ($data === '-') {
                $data = file_get_contents("php://stdin");
            }
            $ret = $this->postJson("deploy_server", json_decode($data, true));
            if (isset($ret['server_conf']['id'])) {
                $this->output->writeln("<info>Server deployed successfully!</info>");
            } else {
                $this->output->writeln("<error>Fail to deploy server</error>");
            }
        }
    }

    protected function createDeployTemplate(): array
    {
        $node = $this->get('node_list')[0] ?? null;
        $port = $this->get('auto_port', ['query' => ['node_name' => $node]])[0]['port'] ?? null;
        return [
            "application" => "appName",
            "server_name" => "serverName",
            "server_type" => "tars_php",
            "template_name" => "tars.tarsphp.default",
            "node_name" => $node,
            "enable_set" => false,
            "set_name" => "",
            "set_area" => "",
            "set_group" => "",
            "operator" => "",
            "developer" => "",
            "adapters" => [
                [
                    "obj_name" => "obj",
                    "bind_ip" => $node,
                    "port" => $port,
                    "port_type" => "tcp",
                    "protocol" => "not_tars",
                    "thread_num" => 5,
                    "max_connections" => 100000,
                    "queuecap" => 50000,
                    "queuetimeout" => 20000
                ]
            ]
        ];
    }
}