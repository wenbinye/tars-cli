<?php


namespace wenbinye\tars\cli;


use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use wenbinye\tars\cli\exception\BadResponseException;
use wenbinye\tars\cli\exception\RequestException;
use wenbinye\tars\cli\models\Server;
use wenbinye\tars\cli\models\ServerName;

class TarsClient implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var Client
     */
    private $httpClient;
    /**
     * @var bool
     */
    private $debug;

    /**
     * @var mixed
     */
    private $result;
    /**
     * @var Config
     */
    private $config;

    public function __construct(Config $config, LoggerInterface $logger, bool $debug = false)
    {
        $this->config = $config;
        $this->setLogger($logger);
        $this->debug = $debug;
    }

    protected function getHttpClient(): Client
    {
        if (!$this->httpClient) {
            $handler = HandlerStack::create();
            if ($this->debug) {
                $handler->push(Middleware::log($this->logger, new MessageFormatter(MessageFormatter::DEBUG)));
            }
            $handler->push(Middleware::mapRequest(function (RequestInterface $req) {
                $uri = $req->getUri();
                parse_str($uri->getQuery(), $query);
                $query['ticket'] = $this->config->getToken();
                return $req->withUri($uri->withQuery(http_build_query($query)));
            }));
            $handler->push(Middleware::mapResponse(function (ResponseInterface $response) {
                $data = json_decode((string)$response->getBody(), true);
                if (!isset($data['ret_code'])) {
                    throw new BadResponseException("No ret_code in response");
                }
                if ($data['ret_code'] !== 200) {
                    throw new RequestException($data['err_msg'] ?? 'Unknown error', $data['ret_code']);
                }
                $this->result = $data['data'] ?? null;
                return $response;
            }));
            $this->httpClient = new Client([
                'handler' => $handler,
                'base_uri' => $this->config->getEndpoint() . '/api/'
            ]);
        }
        return $this->httpClient;
    }

    public function get($uri, array $query = [])
    {
        $this->getHttpClient()->get($uri, ['query' => $query]);

        return $this->getLastResult();
    }

    public function post($uri, array $options = [])
    {
        $this->getHttpClient()->post($uri, $options);

        return $this->getLastResult();
    }

    public function postJson($uri, $data)
    {
        $this->getHttpClient()->post($uri, [
            'json' => $data,
            'headers' => [
                'content-type' => 'application/json'
            ]
        ]);

        return $this->getLastResult();
    }

    private function getLastResult()
    {
        return $this->result;
    }

    public function getServer($serverIdOrName): Server
    {
        if (is_numeric($serverIdOrName)) {
            $this->get("server", ['id' => $serverIdOrName]);
            return $this->createServer($this->getLastResult());
        }

        $serverName = $this->getServerName($serverIdOrName);
        foreach ($this->getServers($serverName->getApplication()) as $server) {
            if ($server->getServerName() === $serverName->getServerName()) {
                return $server;
            }
        }
        throw new \InvalidArgumentException("Cannot find server match $serverIdOrName");
    }

    public function getServerName(string $serverIdOrName): ServerName
    {
        if (is_numeric($serverIdOrName)) {
            return $this->getServer((int)$serverIdOrName)->getServer();
        }

        if (strpos($serverIdOrName, '.') !== false) {
            return ServerName::fromString($serverIdOrName);
        }
        $matches = array_filter($this->getServerNames(), static function (ServerName $server) use ($serverIdOrName) {
            return $serverIdOrName === $server->getServerName();
        });
        if (empty($matches)) {
            throw new \InvalidArgumentException("Cannot find server $serverIdOrName");
        }
        if (count($matches) > 1) {
            throw new \InvalidArgumentException("There are more than one server match '$serverIdOrName'. They are: \n"
                . implode("\n", $matches));
        }
        return current($matches);
    }

    /**
     * @return ServerName[]
     */
    public function getServerNames(): array
    {
        $servers = [];
        foreach ($this->get('tree') as $app) {
            if (!empty($app['children'])) {
                foreach ($app['children'] as $server) {
                    $servers[] = new ServerName($app['name'], $server['name']);
                }
            }
        }
        return $servers;
    }

    /**
     * @param string $app
     * @return Server[]
     */
    public function getServers(string $app): array
    {
        $servers = [];
        foreach ($this->get('server_list', ['tree_node_id' => '1' . $app]) as $info) {
            $servers[] = $this->createServer($info);
        }
        return $servers;
    }

    /**
     * @param array $info
     * @return Server
     */
    protected function createServer(array $info): Server
    {
        $server = new Server();
        $server->setId($info['id']);
        $server->setServer(new ServerName($info['application'], $info['server_name']));
        $server->setServerType($info['server_type']);
        $server->setEnableSet($info['enable_set']);
        $server->setNodeName($info['node_name']);
        $server->setTemplateName($info['template_name'] ?? '');
        $server->setAsyncThreadNum((int) ($info['async_thread_num'] ?? 0));
        if (!empty($info['patch_version'])) {
            $server->setPatchVersion($info['patch_version']);
            $server->setPatchTime(Carbon::parse($info['patch_time']));
        } else {
            $server->setPatchVersion(0);
        }
        $server->setPosttime(Carbon::parse($info['posttime']));
        $server->setProcessId((int) ($info['process_id'] ?? 0));
        $server->setSettingState($info['setting_state'] ?? '');
        $server->setPresentState($info['present_state'] ?? '');
        return $server;
    }
}