<?php declare(strict_types=1);

namespace App\Model\Data;

use App\Common\Cache;
use App\Model\Dao\ActivityDao;
use App\Model\Dao\ActivityWaresDao;
use EasyWeChat\Kernel\Support\Arr;
use Swoft\Bean\Annotation\Mapping\Bean;

use Swoft\Bean\BeanFactory;
use Swoft\Redis\Redis;
use Swoft\Stdlib\Helper\ArrayHelper;
use Swoft\Stdlib\Helper\JsonHelper;

/**
 * 活动配置缓存操作
 * Class ActivityCache
 *
 * @Bean()
 */
class ActivityCache
{

    /**
     * 获取所有活动基本缓存信息
     * @return array
     */
    public  function getAllBaseCache():array
    {

        $data = Redis::hGetAll(Cache::ACTIVITY_LIMIT_BASE);

        if(empty($data))
        {
            /** @var ActivityDao $activityDao */
            $activityDao = \Swoft::getBean(ActivityDao::class);

            $data = $activityDao->getList();

            if($data)
            {
                foreach ($data as $v)   $this->saveBaseCache($v['code'],$v);
            }
        }

        return $data;
    }

    /**
     * 获取活动基本缓存信息
     * @param string $code  活动编号
     * @return array
     */
    public  function getBaseCache(string  $code):array
    {
        $data = Redis::hGet(Cache::ACTIVITY_LIMIT_BASE,$code);

        if(empty($data))
        {
            /** @var ActivityDao $activityDao */
            $activityDao  = BeanFactory::getBean(ActivityDao::class);

            $data = $activityDao ->getDetailByCode($code);

            if(!empty($data)) $this->saveBaseCache($code,$data);
        }else{

            $data = json_decode($data,true);
        }

        return $data;
    }

    /**
     * 保存活动缓存信息
     * @param string $code
     * @param $data
     * @return int
     */
    public  function saveBaseCache(string  $code ,$data)
    {

       return  Redis::hSet(Cache::ACTIVITY_LIMIT_BASE,$code,json_encode($data));

    }

    /**
     * 获取活动多个商品基本信息缓存数据
     * @param string $code 活动编号
     * @param array $sku_list 商品sku集合
     * @return array
     */
    public  function getListProductCache(string $code,array $sku_list):array
    {

        $key =sprintf(Cache::ACTIVITY_LIMIT_PRODUCT,$code);

        $data = Redis::hMGet($key,$sku_list);

        return $data;
    }


    /**
     * 获取活动单个商品基本信息缓存数据
     * @param string $code
     * @param string $sku
     * @param int $activity_id
     * @return array
     */
    public  function getBaseCacheByOne(string $code,string $sku,int $activity_id):array
    {
        $key =sprintf(Cache::ACTIVITY_LIMIT_PRODUCT,$code);

        $data = Redis::hGet($key,$sku);

        if(empty($data))
        {
            /** @var ActivityWaresDao $activityWaresDao */
            $activityWaresDao = \Swoft::getBean(ActivityWaresDao::class);
            //获取商品信息并存入缓存
            $data = $activityWaresDao->findDetailBysku($activity_id,$sku);

            if(empty($data))  return [];

            $this->saveBaseCacheByOne($code,$sku,$data);

        }else{

            $data  = $data ? json_decode($data,true):[];
        }

        return $data;
    }

    /**
     * 保存单个活动商品信息
     * @param string $code
     * @param string $sku
     * @param $data
     * @return array
     */
    public  function saveBaseCacheByOne(string $code,string $sku,$data)
    {
        $key =sprintf(Cache::ACTIVITY_LIMIT_PRODUCT,$code);

        return  Redis::hSet($key,$sku,json_encode($data));
    }

    /**
     * 获取活动的商品编号数据
     * @param string $code 活动编号
     * @param int $start 开始位置
     * @param int $stop 结束位置
     * @return array
     */
    public  function getSkuCache(string $code,$activity_id,int $start=0,int $stop=-1):array
    {

        $key =sprintf(Cache::ACTIVITY_LIMIT_SORT,$code);

        $data = Redis::zRange($key,$start,$stop);

        if(empty($data))
        {
            /** @var ActivityWaresDao $activityWaresDao */
            $activityWaresDao = \Swoft::getBean(ActivityWaresDao::class);

            $sku_list  = $activityWaresDao->getSkuList($activity_id);

            if(empty($sku_list)) return [];

            $this->saveSkuCache($code,$sku_list);

            $data = ArrayHelper::getColumn($sku_list,'sku');
        }

        return $data;
    }


    /**
     * 保存活动商品编号
     * @param string $code
     * @param array $data
     * @return bool
     */
    public  function saveSkuCache(string $code,array $data):bool
    {
        $key = sprintf(Cache::ACTIVITY_LIMIT_SORT,$code);

        foreach ($data as $v)
        {
            Redis::zAdd($key,[$v['sku']=>$v['sort']]);
        }

        return true;
    }


    /**
     * 获取单个商品活动库存缓存信息
     * @param  string $code  活动编码
     * @param  string $sku  商品code
     * @return mixed
     */
    public  function getInventoryCache(string $code,string $sku)
    {

        $key = Cache::ACTIVITY_LIMIT_INVENTORY.$code.':'.$sku;

        $data = Redis::lLen($key);

        return $data;
    }

}