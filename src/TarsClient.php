<?php

declare(strict_types=1);

namespace wenbinye\tars\cli;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use wenbinye\tars\cli\exception\BadResponseException;
use wenbinye\tars\cli\exception\RequestException;
use wenbinye\tars\cli\models\Adapter;
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

    /**
     * @var array
     */
    private $templates;

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
                $data = json_decode((string) $response->getBody(), true);
                if (!isset($data['ret_code'])) {
                    throw new BadResponseException('No ret_code in response');
                }
                if (200 !== $data['ret_code']) {
                    throw new RequestException($data['err_msg'] ?? 'Unknown error', $data['ret_code']);
                }
                $this->result = $data['data'] ?? null;

                return $response;
            }));
            $this->httpClient = new Client([
                'handler' => $handler,
                'base_uri' => $this->config->getEndpoint().'/api/',
            ]);
        }

        return $this->httpClient;
    }

    public function getConfig(): Config
    {
        return $this->config;
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
                'content-type' => 'application/json',
            ],
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
            return $this->getServerById((int) $serverIdOrName);
        }

        if ($serverIdOrName instanceof ServerName) {
            $serverName = $serverIdOrName;
        } else {
            $serverName = $this->getServerName($serverIdOrName);
        }

        return $this->getServers((string) $serverName)[0];
    }

    protected function getServerById(int $serverId): Server
    {
        $this->get('server', ['id' => $serverId]);

        return Server::fromArray($this->getLastResult());
    }

    public function getServerName(string $serverIdOrName): ServerName
    {
        if (is_numeric($serverIdOrName)) {
            return $this->getServerById((int) $serverIdOrName)->getServer();
        }

        if (false !== strpos($serverIdOrName, '.')) {
            return ServerName::fromString($serverIdOrName);
        }
        $matches = array_filter($this->getServerNames(), static function (ServerName $server) use ($serverIdOrName) {
            return $serverIdOrName === $server->getServerName();
        });
        if (empty($matches)) {
            throw new \InvalidArgumentException("Cannot find server $serverIdOrName");
        }
        if (count($matches) > 1) {
            throw new \InvalidArgumentException("There are more than one server match '$serverIdOrName'. They are: \n".implode("\n", $matches));
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

    public function getAllServers(string $app): array
    {
        return array_map([Server::class, 'fromArray'], $this->get('server_list', ['tree_node_id' => '1'.$app]));
    }

    /**
     * @return Server[]
     */
    public function getServers(string $serverName): array
    {
        if (is_numeric($serverName)) {
            return [$this->getServerById($serverName)];
        } elseif (false === strpos($serverName, '.')) {
            throw new \InvalidArgumentException('');
        } else {
            [$appName, $serverName] = explode('.', $serverName, 2);
            $servers = array_values(array_filter($this->getAllServers($appName), static function (Server $server) use ($serverName): bool {
                return $server->getServerName() === $serverName;
            }));
            if (empty($servers)) {
                throw new \InvalidArgumentException("Server {$appName}.{$serverName} does not exist");
            }

            return $servers;
        }
    }

    /**
     * @return Adapter[]
     */
    public function getAdapters(int $serverId): array
    {
        return array_map([Adapter::class, 'fromArray'], $this->get('adapter_conf_list', ['id' => $serverId]));
    }

    public function getAvailablePort(string $node): ?int
    {
        return $this->get('auto_port', ['node_name' => $node])[0]['port'] ?? null;
    }

    public function getAdapter(int $adapterId): Adapter
    {
        $ret = $this->get('adapter_conf', ['id' => $adapterId]);
        if (isset($ret['id'])) {
            return Adapter::fromArray($ret);
        }
        throw new \InvalidArgumentException("Cannot find adapter $adapterId");
    }

    public function getTemplates(): array
    {
        if (!$this->templates) {
            $this->templates = $this->get('query_profile_template');
        }

        return $this->templates;
    }

    public function saveTemplate(array $template): array
    {
        $result = $this->postJson('add_profile_template', $template);
        $this->templates = null;

        return $result;
    }

    public function getTemplate($templateIdOrName): ?array
    {
        $templates = $this->getTemplates();
        foreach ($templates as $template) {
            if ($template['id'] == $templateIdOrName || $template['template_name'] === $templateIdOrName) {
                return $template;
            }
        }

        return null;
    }
}
