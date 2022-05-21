<?php

declare(strict_types=1);

namespace ActiveCollab\ActiveCollabJobs\Factory;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Http\HttpClientOptions;
use Psr\Log\LoggerInterface;

class FirebaseProjectFactory
{
    private Factory $firebase_factory;

    private ?HttpClientOptions $http_client_options;

    private ?LoggerInterface $http_request_logger;

    private ?LoggerInterface $http_debug_logger;


    public function __construct(Factory $firebase_factory)
    {
        $this->firebase_factory = $firebase_factory;
        $this->http_client_options = null;
        $this->http_debug_logger = null;
        $this->http_request_logger = null;
    }

    public function setHttpRequestLogger(?LoggerInterface $logger): self
    {
        $this->http_request_logger = $logger;
        return $this;
    }

    public function setHttpRequestDebugLogger(?LoggerInterface $logger): self
    {
        $this->http_debug_logger = $logger;
        return $this;
    }

    public function setHttpClientOptions(HttpClientOptions $options): self
    {
        $this->http_client_options = $options;
        return $this;
    }



    /**
     * Example for creating factory
     * [ 'credentials' => $data, 'logger'=>$logger ]
     * $data can be path to config.json or see Kreait\Firebase\ServiceAccount - it allows individual config
     *
     * @param array $config
     * @return Factory
     */
    public function createFactory($config = []): Factory
    {
        $factory = clone $this->firebase_factory;

        if ($config['credentials'] ?? null) {
            $factory = $factory
                ->withServiceAccount($config['credentials'])
                ->withDisabledAutoDiscovery();
        }

        if ($this->http_request_logger) {
            $factory = $factory->withHttpLogger($this->http_request_logger);
        }

        if ($this->http_debug_logger) {
            $factory = $factory->withHttpDebugLogger($this->http_debug_logger);
        }

        if ($this->http_client_options) {
            $factory = $factory->withHttpClientOptions($this->http_client_options);
        }

        return $factory;
    }


    public function createMessaging(array $config = [])
    {
        return $this->createFactory($config)->createMessaging();
    }
}
