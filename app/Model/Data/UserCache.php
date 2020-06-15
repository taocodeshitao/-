<?php

namespace App\Model\Data;


use App\Common\Cache;
use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Redis\Redis;

/**
 * 用户信息缓存操作
 * Class AddressCache
 *
 * @Bean()
 */
class UserCache
{

    /**
     * 获取用户地址信息
     * @param int $user_id 用户id
     * @return array
     */
    public  function getAddressCache(int $user_id):array
    {

        $key = sprintf(Cache::ADDRESS_LIST,$user_id);

        //获取用户地址列表缓存信息
        $data = Redis::hGet($key,'address');

        $data =  $data ? json_decode($data,true) : [];

        return $data;
    }

    /**
     * 保存地址缓存信息
     * @param int $user_id 用户id
     * @param array $data 缓存信息
     *
     * @return bool
     */
    public  function saveAddressCache(int $user_id,array $data):bool
    {

        $key = sprintf(Cache::ADDRESS_LIST,$user_id);

        //存储用户地址缓存信息
        Redis::hSet($key,'address',json_encode($data));

        Redis::expire($key,Cache::ADDRESS_TTL);

        return true;
    }

    /**
     * 删除缓存地址信息
     * @param int $user_id 用户id
     *
     * @return int
     */
    public  function delAddressCache(int $user_id):int
    {

        $key = sprintf(Cache::ADDRESS_LIST,$user_id);

        //存储用户地址缓存信息
        return Redis::hDel($key,'address');
    }

    /**
     * 获取收藏信息
     * @param int $user_id 用户id
     * @return mixed
     */
    public  function getCollectCache(int $user_id)
    {

        $key = sprintf(Cache::COLLECT_LIST,$user_id);

        //获取用户地址列表缓存信息
        $data = Redis::hGet($key,'collect');

        $data = $data ? json_decode($data,true) : [];

        return $data;

    }


    /**
     * 保存收藏缓存信息
     * @param int $user_id 用户id
     * @param array $data 缓存信息
     *
     * @return bool
     */
    public  function saveCollectCache(int $user_id,array $data):bool
    {

        $key = sprintf(Cache::COLLECT_LIST,$user_id);

        //存储用户地址缓存信息
        Redis::hSet($key,'collect',json_encode($data));

        Redis::expire($key,Cache::COLLECT_TTL);

        return true;
    }

    /**
     * 删除收藏信息
     * @param int $user_id 用户id
     *
     * @return int
     */
    public  function delCollectCache(int $user_id):int
    {

        $key = sprintf(Cache::COLLECT_LIST,$user_id);

        //存储用户地址缓存信息
        return Redis::del($key,'collect');
    }


    /**
     * 获取历史信息
     * @param int $user_id 用户id
     * @return mixed
     */
    public  function getHistoryCache(int $user_id)
    {

        $key = sprintf(Cache::HISTORY_LIST,$user_id);

        //获取用户地址列表缓存信息
        $data = Redis::hGet($key,'collect');

        $data = $data ? json_decode($data,true) : [];

        return $data;
    }


    /**
     * 保存历史缓存信息
     * @param int $user_id 用户id
     * @param string $sku 商品sku
     * @param array $data 缓存信息
     *
     * @return bool
     */
    public  function saveHistoryCache(int $user_id,string $sku,array $data):bool
    {

        $key = sprintf(Cache::HISTORY_LIST,$user_id,$sku);

        //存储用户游览缓存信息
        Redis::setex($key,json_encode($data),config('cache.history_ttl'));

        return true;
    }
}