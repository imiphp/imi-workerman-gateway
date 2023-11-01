<?php

declare(strict_types=1);

namespace Imi\WorkermanGateway\Workerman\Server\Business;

use GatewayWorker\BusinessWorker;
use GatewayWorker\Lib\Gateway;
use Imi\Bean\Annotation\Bean;
use Imi\ConnectionContext;
use Imi\Event\Event;
use Imi\Log\Log;
use Imi\RequestContext;
use Imi\Server\Protocol;
use Imi\Server\Server;
use Imi\Util\Socket\IPEndPoint;

#[Bean(name: 'WorkermanGatewayTcpBusinessServer')]
class TcpBusinessServer extends \Imi\Workerman\Server\Tcp\Server
{
    /**
     * {@inheritDoc}
     */
    protected string $workerClass = BusinessWorker::class;

    /**
     * {@inheritDoc}
     */
    public function __construct(string $name, array $config)
    {
        parent::__construct($name, $config);
        Event::one('IMI.WORKERMAN.SERVER.WORKER_START', function (): void {
            $this->bindBusinessEvents();
        });
    }

    /**
     * {@inheritDoc}
     */
    protected function bindEvents(): void
    {
        parent::bindEvents();
        // @phpstan-ignore-next-line
        $this->worker->onMessage = null;
        // @phpstan-ignore-next-line
        $this->worker->onClose = null;
        // @phpstan-ignore-next-line
        $this->worker->onConnect = null;
    }

    /**
     * {@inheritDoc}
     */
    public function getProtocol(): string
    {
        return Protocol::TCP;
    }

    protected function bindBusinessEvents(): void
    {
        $worker = $this->worker;
        $refClass = new \ReflectionClass($worker);

        $property = $refClass->getProperty('_eventOnConnect');
        $property->setAccessible(true);
        $property->setValue($worker, function (string $clientId): void {
            RequestContext::muiltiSet([
                'server'   => $this,
                'clientId' => $clientId,
            ]);
            ConnectionContext::muiltiSet([
                '__clientAddress' => $_SERVER['REMOTE_ADDR'],
                '__clientPort'    => $_SERVER['REMOTE_PORT'],
            ]);
            Event::trigger('IMI.WORKERMAN.SERVER.CONNECT', [
                'server'   => $this,
                'clientId' => $clientId,
            ], $this);
            RequestContext::destroy();
        });

        $property = $refClass->getProperty('_eventOnClose');
        $property->setAccessible(true);
        $property->setValue($worker, function (string $clientId): void {
            RequestContext::muiltiSet([
                'server'   => $this,
                'clientId' => $clientId,
            ]);
            Event::trigger('IMI.WORKERMAN.SERVER.CLOSE', [
                'server'   => $this,
                'clientId' => $clientId,
            ], $this);
            RequestContext::destroy();
        });

        $property = $refClass->getProperty('_eventOnMessage');
        $property->setAccessible(true);
        $property->setValue($worker, function (string $clientId, $data): void {
            try
            {
                RequestContext::muiltiSet([
                    'server'   => $this,
                    'clientId' => $clientId,
                ]);

                Event::trigger('IMI.WORKERMAN.SERVER.TCP.MESSAGE', [
                    'server'   => $this,
                    'clientId' => $clientId,
                    'data'     => $data,
                ], $this);
                RequestContext::destroy();
            }
            catch (\Throwable $th)
            {
                // @phpstan-ignore-next-line
                if (true !== $this->getBean('TcpErrorHandler')->handle($th))
                {
                    Log::error($th);
                }
            }
        });
    }

    /**
     * {@inheritDoc}
     */
    public function send($clientId, string $data): bool
    {
        return Server::sendRaw($data, $clientId, $this->getName()) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function getClientAddress($clientId): IPEndPoint
    {
        $session = Gateway::getSession($clientId);

        return new IPEndPoint($session['__clientAddress'], $session['__clientPort']);
    }
}
