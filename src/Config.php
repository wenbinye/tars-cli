<?php

declare(strict_types=1);

namespace wenbinye\tars\cli;

class Config
{
    private const TARS_CONFIG_JSON = '.config/tars/config.json';

    private static $INSTANCE;

    /**
     * @var string
     */
    private $endpoint;

    /**
     * @var string
     */
    private $token;

    /**
     * @var string
     */
    private $template;

    /**
     * Config constructor.
     *
     * @param $endpoint
     * @param $token
     */
    public function __construct(string $endpoint = '', string $token = '', string $template = '')
    {
        $this->endpoint = $endpoint;
        $this->token = $token;
        $this->template = $template;
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    public function setEndpoint(string $endpoint): void
    {
        $this->endpoint = $endpoint;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function setTemplate(string $template): void
    {
        $this->template = $template;
    }

    public static function getInstance(): self
    {
        if (!self::$INSTANCE) {
            $file = self::getConfigFile();
            self::$INSTANCE = is_readable($file) ? self::read($file) : new self();
        }

        return self::$INSTANCE;
    }

    public static function read(string $file): self
    {
        $config = new self();
        if (!is_readable($file)) {
            throw new \InvalidArgumentException("Cannot load config from $file");
        }
        $data = json_decode(file_get_contents($file), true);
        if (!empty($data) && is_array($data)) {
            foreach ($data as $key => $value) {
                $config->{$key} = $value;
            }
        }

        return $config;
    }

    public static function save(Config $config, ?string $file = null): void
    {
        if (!isset($file)) {
            $file = self::getConfigFile();
        }
        $dir = dirname($file);
        if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
            throw new \RuntimeException("Cannot create config directory $dir");
        }
        file_put_contents($file, json_encode(get_object_vars($config),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    public static function getConfigFile(): string
    {
        return self::getHomeDir().'/'.self::TARS_CONFIG_JSON;
    }

    public static function getHomeDir()
    {
        $home = getenv('HOME');
        if (!empty($home)) {
            return $home;
        }
        if (!empty($_SERVER['HOMEDRIVE']) && !empty($_SERVER['HOMEPATH'])) {
            // home on windows
            $home = $_SERVER['HOMEDRIVE'].$_SERVER['HOMEPATH'];
            // If HOMEPATH is a root directory the path can end with a slash. Make sure
            // that doesn't happen.
            $home = rtrim($home, '\\/');

            return $home;
        }
        throw new \InvalidArgumentException('Cannot detect user home directory');
    }
}
