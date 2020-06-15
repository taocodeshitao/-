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

use App\Model\Service\MonitorLogic;
use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Bean\Annotation\Mapping\Inject;
use Swoft\Db\Exception\DbException;
use Swoft\Process\Process;
use Swoft\Process\UserProcess;

/**
 * Class MonitorProcess
 *
 * @since 2.0
 *
 * @Bean()
 */
class MonitorProcess extends UserProcess
{
    /**
     * @Inject()
     *
     * @var MonitorLogic
     */
    private $logic;

    /**
     * @param Process $process
     *
     * @throws DbException
     */
    public function run(Process $process): void
    {

        //Task::async('test','testadd',['shitao',1]);
        //echo "前置进程监听器:".PHP_EOL;
        //$this->logic->monitor($process);
        while (true) {
            echo "1";
        }
    }





}
