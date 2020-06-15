<?php
/**
 * Created by PhpStorm.
 * User: 石涛
 * Date: 2020/5/23
 * Time: 上午 9:50
 */
declare(strict_types=1);

namespace App\Aspect;

use App\Http\Controller\TestRunTimeController;
use Swoft\Aop\Annotation\Mapping\After;
use Swoft\Aop\Annotation\Mapping\Aspect;
use Swoft\Aop\Annotation\Mapping\Before;
use Swoft\Aop\Annotation\Mapping\PointBean;
use Swoft\Aop\Point\JoinPoint;

/**
 * @Aspect(order=1)
 *
 * @PointBean(include={TestRunTimeController::class})
 */
class CalculateRunTimeAspect {

    /** @var float 执行开始 */
    private $time_begin;

    /**
     * 前置通知
     *
     * @Before()
     */
    public function beforeAdvice()
    {
        // 起点时间
        $this->time_begin = microtime(true);
    }

    /**
     * 后置通知
     *
     * @After()
     *
     * @param JoinPoint $joinPoint
     */
    public function afterAdvice(JoinPoint $joinPoint)
    {
        /** @var float 执行结束 */
        $timeFinish = microtime(true);
        $method = $joinPoint->getMethod();
        $runtime = round(($timeFinish - $this->time_begin) * 1000, 3);
        echo "{$method} 方法，本次执行时间为: {$runtime}ms \n";
        echo PHP_EOL;
    }
}
