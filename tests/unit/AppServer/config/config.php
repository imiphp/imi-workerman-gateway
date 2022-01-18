<?php

declare(strict_types=1);

use function Imi\env;

return [
    // 项目根命名空间
    'namespace'    => 'Imi\WorkermanGateway\Test\AppServer',

    // 配置文件
    'configs'    => [
        'beans'        => __DIR__ . '/beans.php',
    ],

    // 扫描目录
    'beanScan'    => [
    ],

    // 组件命名空间
    'components'    => [
        'Workerman'        => 'Imi\Workerman',
        'Swoole'           => \defined('SWOOLE_VERSION') ? 'Imi\Swoole' : '',
        'WorkermanGateway' => 'Imi\WorkermanGateway',
        'Macro'            => 'Imi\Macro',
    ],

    // 日志配置
    'logger' => [
        'channels' => [
            'imi' => [
                'handlers' => [
                    [
                        'class'     => \Imi\Log\Handler\ConsoleHandler::class,
                        'formatter' => [
                            'class'     => \Imi\Log\Formatter\ConsoleLineFormatter::class,
                            'construct' => [
                                'format'                     => null,
                                'dateFormat'                 => 'Y-m-d H:i:s',
                                'allowInlineLineBreaks'      => true,
                                'ignoreEmptyContextAndExtra' => true,
                            ],
                        ],
                    ],
                    [
                        'class'     => \Monolog\Handler\RotatingFileHandler::class,
                        'construct' => [
                            'filename' => \dirname(__DIR__) . '/logs/log.log',
                        ],
                        'formatter' => [
                            'class'     => \Monolog\Formatter\LineFormatter::class,
                            'construct' => [
                                'dateFormat'                 => 'Y-m-d H:i:s',
                                'allowInlineLineBreaks'      => true,
                                'ignoreEmptyContextAndExtra' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],

    // 主服务器配置
    'mainServer'    => \defined('SWOOLE_VERSION') ? [
        'namespace'    => 'Imi\WorkermanGateway\Test\AppServer\WebSocketServer',
        'type'         => \Imi\WorkermanGateway\Swoole\Server\Type::BUSINESS_WEBSOCKET,
        // 'host'         => env('SERVER_HOST', '127.0.0.1'),
        // 'port'         => 13002,
        'mode'         => \SWOOLE_BASE,
        'configs'      => [
            'worker_num'    => 2,
        ],
        'workermanGateway' => [
            'registerAddress'      => '127.0.0.1:13004',
            'worker_coroutine_num' => swoole_cpu_num(),
            'channel'              => [
                'size' => 1024,
            ],
        ],
    ] : [],

    // 子服务器（端口监听）配置
    'subServers'        => \defined('SWOOLE_VERSION') ? [
        'http'     => [
            'namespace' => 'Imi\WorkermanGateway\Test\AppServer\ApiServer',
            'type'      => Imi\Swoole\Server\Type::HTTP,
            'host'      => env('SERVER_HOST', '127.0.0.1'),
            'port'      => 13000,
        ],
    ] : [],

    // Workerman 服务器配置
    'workermanServer' => [
        'http' => [
            'namespace' => 'Imi\WorkermanGateway\Test\AppServer\ApiServer',
            'type'      => Imi\Workerman\Server\Type::HTTP,
            'host'      => env('SERVER_HOST', '127.0.0.1'),
            'port'      => 13000,
            'configs'   => [
                'registerAddress' => '127.0.0.1:13004',
            ],
        ],
        'register' => [
            'namespace'   => 'Imi\WorkermanGateway\Test\AppServer\Register',
            'type'        => Imi\WorkermanGateway\Workerman\Server\Type::REGISTER,
            'host'        => env('SERVER_HOST', '127.0.0.1'),
            'port'        => 13004,
            'configs'     => [
            ],
        ],
        'gateway' => [
            'namespace'   => 'Imi\WorkermanGateway\Test\AppServer\Gateway',
            'type'        => Imi\WorkermanGateway\Workerman\Server\Type::GATEWAY,
            'socketName'  => 'websocket://127.0.0.1:13002',
            'configs'     => [
                'lanIp'           => '127.0.0.1',
                'startPort'       => 12900,
                'registerAddress' => '127.0.0.1:13004',
            ],
        ],
        'websocket' => [
            'namespace'   => 'Imi\WorkermanGateway\Test\AppServer\WebSocketServer',
            'type'        => Imi\WorkermanGateway\Workerman\Server\Type::BUSINESS_WEBSOCKET,
            'shareWorker' => '\\' === \DIRECTORY_SEPARATOR ? 'http' : null,
            'configs'     => [
                'registerAddress' => '127.0.0.1:13004',
                'count'           => 2,
            ],
        ],
    ],

    // 数据库配置
    'db'    => [
        // 默认连接池名
        'defaultPool'    => 'maindb',
    ],

    // redis 配置
    'redis' => [
        // 默认连接池名
        'defaultPool'   => 'redis',
        'connections'   => [
            'redis' => [
                'host'        => env('REDIS_SERVER_HOST', '127.0.0.1'),
                'port'        => env('REDIS_SERVER_PORT', 6379),
                'password'    => env('REDIS_SERVER_PASSWORD'),
            ],
        ],
    ],

    // 锁
    'lock'  => [
        'default' => 'redisConnectionContextLock',
        'list'    => [
            'redisConnectionContextLock' => [
                'class'     => 'RedisLock',
                'options'   => [
                    'poolName'  => 'redis',
                ],
            ],
        ],
    ],

    'workerman' => [
        'imi' => [
            'beans' => [
                'ServerUtil' => Imi\WorkermanGateway\Workerman\Server\Util\GatewayServerUtil::class,
            ],
        ],
    ],

    'swoole' => [
        'imi' => [
            'beans' => [
                'ServerUtil' => Imi\WorkermanGateway\Swoole\Server\Util\GatewayServerUtil::class,
            ],
        ],
    ],
];
