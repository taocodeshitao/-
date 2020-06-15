<?php
use Swoft\Http\Server\HttpServer;
use Swoft\Rpc\Client\Client as ServiceClient;
use Swoft\Rpc\Client\Pool as ServicePool;
use Swoft\Task\Swoole\TaskListener;
use Swoft\Task\Swoole\FinishListener;
use Swoft\Rpc\Server\ServiceServer;
use Swoft\Server\SwooleEvent;
use Swoft\Db\Database;
use Swoft\Redis\RedisDb;
use Swoft\Limiter\Annotation\Mapping\RateLimiter;
return [

    'lineFormatter' => [
        'format' => '%datetime% [%level_name%] [%channel%] [%event%] [tid:%tid%] [cid:%cid%] [traceid:%traceid%] [spanid:%spanid%] [parentid:%parentid%] %messages%',
        'dateFormat' => 'Y-m-d H:i:s'
    ],
    'noticeHandler'      => [
        'logFile' => '@runtime/logs/notice-%d{Y-m-d-H}.log',
    ],
    'applicationHandler' => [
        'logFile' => '@runtime/logs/error-%d{Y-m-d}.log',
    ],
    'logger'            => [
        'flushRequest' => false,
        'enable'       => true,
        'json'         => false,
    ],
    'httpServer'        => [
        'class'    => HttpServer::class,
        'port'     => 8081,
       /* 'listener' => [
            'rpc' => bean('rpcServer')
        ],*/
        'process'  => [
            //'crontab' => bean(Swoft\Crontab\Process\CrontabProcess::class),
            //'monitor' => bean(\App\Process\MonitorProcess::class)//配置启动前置进程  启动方式：php bin/swoft http:start
            //'monitor' => bean(\App\Process\Worker1Process::class)//配置启动工进程 启动方式：php bin/swoft process:start
        ],
        'on'       => [
            SwooleEvent::TASK   => bean(TaskListener::class),
            SwooleEvent::FINISH => bean(FinishListener::class)
        ],
        /* @see HttpServer::$setting */
        'setting' => [
            'task_worker_num'       => 1,
            'task_enable_coroutine' => true,
            'worker_num'            => 4
        ]
    ],
    'httpDispatcher'    => [
        'middlewares'      => [
               \App\Http\Middleware\FavIconMiddleware::class,
               \App\Http\Middleware\CorsMiddleware::class,
        ],
        'afterMiddlewares' => [
            \Swoft\Http\Server\Middleware\ValidatorMiddleware::class
        ]
    ],
    'db'                => [
        'class'    => Database::class,
        'dsn'      => env('DB_DSN'),
        'username' => env('DB_USERNAME'),
        'password' => env('DB_PASSWORD'),
        'prefix'   => env('DB_PREFIX'),
        'charset'   => 'utf8',
    ],
    'db.pool' => [
        'class' => \Swoft\Db\Pool::class,
        'database' => \bean('db'),
        'minActive' => 10,
        'maxActive' => 20,
        'maxWait' => 0,
        'maxWaitTime' => 0,
        'maxIdleTime' => 60,
    ],
    'redis'             => [
        'class'    => RedisDb::class,
        'host'     => env('REDIS_HOST'),
        'port'     => env('REDIS_PORT'),
        'retryInterval' => env('REDIS_RETRY'),
        'timeout'       => env('REDIS_TIMEOUT'),
        'password' => env('REDIS_PASSWORD'),
        'database' => env('REDIS_DB'),
        'option'   => [
            'prefix' => '',
            'serializer' => Redis::SERIALIZER_NONE
        ]
    ]
];
