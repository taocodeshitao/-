<?php declare(strict_types=1);

namespace App\Model\Data;

use App\Common\Cache;
use Swoft\Bean\Annotation\Mapping\Bean;

use Swoft\Redis\Redis;

/**
 * 专区缓存操作
 * Class ActivityCache
 *
 * @Bean()
 */
class ArrondyCache
{

    /**
     * 获取首页固定专区缓存信息
     * @return array
     */
    public  function getArrondyCache():array
    {
        $data = Redis::hGet(Cache::SYSTEM_ARRONDY_DATA,'arrondy');

        $data =  $data ? json_decode($data,true) : [];

        return $data;
    }

    /**
     * 保存首页固定专区缓存信息
     * @param array $data
     * @return bool
     */
    public  function saveArrondyCache(array $data):bool
    {
        $key = Cache::SYSTEM_ARRONDY_DATA;

        Redis::hSet($key,'arrondy', json_encode($data));

        Redis::expire($key,Cache::ARRONDY_TTL);

        return true;
    }


    /**
     * 获取首页固定专区缓存信息
     * @return array
     */
    public  function getCustomizeCache():array
    {
        $data = Redis::hGet(Cache::SYSTEM_CUSTOMIZE_DATA,'arrondy');

        $data =  $data ? json_decode($data,true) : [];

        return $data;
    }

    /**
     * 保存首页固定专区缓存信息
     * @param array $data
     * @return bool
     */
    public  function saveCustomizeCache(array $data):bool
    {
        $key = Cache::SYSTEM_CUSTOMIZE_DATA;

        Redis::hSet($key,'arrondy', json_encode($data));

        Redis::expire($key,Cache::CUSTOMIZE_TTL);

        return true;
    }

}