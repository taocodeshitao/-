<?php
/**
 * description ProductCache.php
 * Created by PhpStorm.
 * User: zengqb
 * DateTime: 2019/12/23 18:46
 */

namespace App\Model\Data;


use App\Common\Cache;
use App\Model\Dao\WaresDao;
use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Redis\Redis;
use Swoft\Stdlib\Helper\JsonHelper;

/**
 * 商品缓存操作
 * Class ProductCache
 *
 * @Bean()
 */
class ProductCache
{

    /**
     * 获取多个商品详情
     * @param array $sku_list
     * @return array
     */
    public  function getDetailCache(array $sku_list)
    {
        $data = Redis::hMGet(Cache::PRODUCT_DETAILS,$sku_list);

        return $data;
    }

    /**
     * 缓存商品信息
     * @param string $sku
     * @param  $data
     * @return int
     */
    public  function saveDetailsByOne(string  $sku,$data)
    {

       return   Redis::hSet(Cache::PRODUCT_DETAILS,$sku,json_encode($data));
    }

    /**
     * 获取单个商品详情
     * @param string $sku
     * @return array
     */
    public  function getDetailCacheByOne(string $sku)
    {
        $data = Redis::hGet(Cache::PRODUCT_DETAILS,$sku);

        if(empty($data))
        {
            /** @var WaresDao $waresDao */
            $waresDao  =\Swoft::getBean(WaresDao::class);

            $data  = $waresDao->findDetailBySku($sku);

            if(empty($data)) return [];

            $this->saveDetailsByOne($sku,$data);

        }else{

            $data = $data ? json_decode($data,true):[];
        }

        return $data;
    }

}