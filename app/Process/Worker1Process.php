<?php declare(strict_types=1);
/**
 * This file is part of Swoft.
 *
 * @link     https://swoft.org
 * @document https://swoft.org/docs
 * @contact  group@swoft.org
 * @license  https://github.com/swoft-cloud/swoft/blob/master/LICENSE
 */

namespace App\Process;

use App\Model\Service\HaveProductService;
use Swoft\Bean\Annotation\Mapping\Inject;
use Swoft\Log\Helper\CLog;
use Swoft\Process\Annotation\Mapping\Process;
use Swoft\Process\Contract\ProcessInterface;
use Swoft\Task\Task;
use Swoole\Coroutine;
use Swoole\Process\Pool;

/**
 * Class Worker1Process
 *
 * @since 2.0
 *
 * @Process(workerId=0)
 */
class Worker1Process implements ProcessInterface
{

    /**
     * @Inject()
     * @var HaveProductService
     */
    protected $haveProductService;


    /**
     * @param Pool $pool
     * @param int  $workerId
     */
    public function run(Pool $pool, int $workerId): void
    {
        echo "进程启动成功";
        while (true) {

            // Task::async('test','testadd',['shitao',1]);
             CLog::info('worker-' . $workerId);

            Coroutine::sleep(3);
        }
    }
}
