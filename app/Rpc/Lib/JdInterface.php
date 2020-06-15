<?php declare(strict_types=1);


namespace App\Rpc\Lib;

interface JdInterface
{

    /**
     * @param array $products 二维数组[{'code':xx, 'num':xx}]
     * @param int $province 省id
     * @param int $city 城市id
     * @param int $county 区县id
     * @return array [
     *      'code'      => 1,
     *      'message'   => 'success',
     *      'data'      => [
     *          ['code' => '传入的id',
     *          'result' => '有货'],
     *          ['code' => '传入的id',
     *          'result' => '无货'],
     *      ]
     * ]
     */
    public function getJdStore(array $products, int $province, $city = 0, $county = 0): array;

}