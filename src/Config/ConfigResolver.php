<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim-Console/blob/0.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Console\Config;

use InvalidArgumentException;
use RuntimeException;
use Slim\Console\Exception\CannotResolveConfigException;

class ConfigResolver
{
    public const CONFIG_FILENAME = 'slim-console.config';

    public const FORMAT_PHP = 'php';

    public const FORMAT_JSON = 'json';

    /**
     * @var string[]
     */
    protected $supportedFormats = [
        self::FORMAT_PHP,
        self::FORMAT_JSON,
    ];

    /**
     * @var string
     */
    protected $rootDir;

    /**
     * @param string $rootDir
     */
    public function __construct(string $rootDir)
    {
        $this->rootDir = $rootDir;
    }

    /**
     * Resolve configuration. Environment takes precedence over configuration file.
     *
     * @return Config
     *
     * @throws CannotResolveConfigException
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function resolve(): Config
    {
        try {
            return Config::fromEnvironment();
        } catch (InvalidArgumentException $e) {
            return $this->attemptResolvingConfigFromSupportedFormats();
        }
    }

    /**
     * @return Config
     *
     * @throws CannotResolveConfigException
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    protected function attemptResolvingConfigFromSupportedFormats(): Config
    {
        foreach ($this->supportedFormats as $format) {
            $path = $this->rootDir . DIRECTORY_SEPARATOR . self::CONFIG_FILENAME . ".{$format}";
            if (file_exists($path)) {
                return $this->attemptParsingConfigFromFile($path, $format);
            }
        }

        throw new CannotResolveConfigException();
    }

    /**
     * @param string $path
     * @param string $format
     *
     * @return Config
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    protected function attemptParsingConfigFromFile(string $path, string $format): Config
    {
        switch ($format) {
            case self::FORMAT_PHP:
                $parsed = require $path;

                if (!is_array($parsed)) {
                    throw new InvalidArgumentException('Slim Console configuration should be an array.');
                }

                return Config::fromArray($parsed);

            case self::FORMAT_JSON:
                $contents = file_get_contents($path);
                $parsed = json_decode($contents);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new InvalidArgumentException('Invalid JSON parsed from Slim Console configuration. ' . json_last_error_msg());
                } elseif (!is_array($parsed)) {
                    throw new InvalidArgumentException('Slim Console configuration should be an array.');
                }

                return Config::fromArray($parsed);

            default:
                throw new RuntimeException("Invalid configuration format `{$format}`.");
        }
    }
}
