<?php
declare(strict_types=1);

namespace App\Http\Controller;

use Swoft\Http\Server\Annotation\Mapping\Controller;
use Swoft\Http\Server\Annotation\Mapping\RequestMapping;

/**
 * @Controller(prefix="test")
 */
class TestRunTimeController
{

    /**
     * 闭包递归，计算阶乘
     *
     * @RequestMapping(route="factorial/{number}")
     *
     * @param int $number
     *
     * @return array
     */
    public function factorial(int $number): array
    {
        $factorial = function ($arg) use (&$factorial) {
            if ($arg == 1) {
                return $arg;
            }
            return $arg * $factorial($arg - 1);
        };
        return [$factorial($number)];
    }

    /**
     * 计算 1-1000 和，最后休眠 1s
     *
     * @RequestMapping(route="sum")
     */
    public function sumAndSleep(): array
    {
        $sum = 0;
        for ($i = 1; $i <= 1000; $i++) {
            $sum = $sum + $i;
        }
        sleep(1);
        return [$sum];
    }
}