<?php

declare(strict_types=1);

namespace wenbinye\tars\cli;

class Config
{
    private const TARS_CONFIG_JSON = '/.tars/config.json';
    private static $INSTANCE;

    /**
     * @var string
     */
    private $endpoint;

    /**
     * @var string
     */
    private $token;

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

    public static function getInstance(): self
    {
        if (!self::$INSTANCE) {
            $config = new self();
            $file = self::getHomeDir().self::TARS_CONFIG_JSON;
            if (file_exists($file)) {
                $data = json_decode(file_get_contents($file), true);
                if (!empty($data) && is_array($data)) {
                    foreach ($data as $key => $value) {
                        $config->{$key} = $value;
                    }
                }
            }
            self::$INSTANCE = $config;
        }

        return self::$INSTANCE;
    }

    public static function save(Config $config): void
    {
        $configFile = self::getHomeDir().self::TARS_CONFIG_JSON;
        $dir = dirname($configFile);
        if (!is_dir($dir) && !mkdir($dir) && !is_dir($dir)) {
            throw new \RuntimeException("Cannot create config directory $dir");
        }
        file_put_contents($configFile, json_encode(get_object_vars($config),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    private static function getHomeDir()
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
