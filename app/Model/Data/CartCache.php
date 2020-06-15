<?php

namespace App\Model\Data;


use App\Common\Cache;
use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Redis\Redis;

/**
 * 兑换袋缓存
 * Class CartData
 *
 * @Bean()
 */
class CartCache
{


    /**
     * 获取兑换袋中商品总数
     * @param int $user_id
     * @return int
     */
    public  function getCount(int $user_id):int
    {
        $key  = sprintf(Cache::CART_DATA,$user_id);

        return Redis::zCard($key);

    }

    /**
     * 添加兑换袋商品
     * @param int $user_id
     * @param string $sku
     * @param int $num
     */
    public function addProductCache(int $user_id, string $sku, int $num):void
    {
        $key  = sprintf(Cache::CART_DATA,$user_id);

        Redis::zIncrBy($key, $num, $sku);

    }


    /**
     * 更新兑换袋商品数量
     * @param int $user_id
     * @param string $sku
     * @param int $num
     */
    public function updateNumCache(int $user_id, string $sku, int $num): void
    {
        $key  = sprintf(Cache::CART_DATA,$user_id);

        Redis::zAdd($key,[$sku=>$num]);

    }


    /**
     * 清除兑换袋商品
     * @param int $user_id
     * @param array $sku_list
     */
    public function removeProductCache(int $user_id, array $sku_list): void
    {

        $key  = sprintf(Cache::CART_DATA,$user_id);

        Redis::pipeline(function() use($key,$sku_list)
        {
            foreach ($sku_list as $sku) Redis::zRem($key, $sku);

        });
    }


    /**
     * 获取兑换袋的商品
     * @param int $user_id
     * @return array
     */
    public  function getProductCache(int $user_id):array
    {
        $key  = sprintf(Cache::CART_DATA,$user_id);

        $data = Redis::zRangeByScore($key,'-inf','+inf',['WITHSCORES'=>true]);

        return $data ?? [];
    }
}