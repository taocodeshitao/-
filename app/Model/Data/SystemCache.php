<?php declare(strict_types=1);

namespace App\Model\Data;

use App\Common\Cache;
use App\Model\Dao\ConfigDao;
use Swoft\Bean\Annotation\Mapping\Bean;

use Swoft\Bean\BeanFactory;
use Swoft\Redis\Redis;

/**
 * 系统配置缓存操作
 * Class SystemData
 *
 * @Bean()
 */
class SystemCache
{


    /**
     * 获取官方商城banner缓存数据
     */
    public  function getBannerCache()
    {
        $data =  Redis::get(Cache::SYSTEM_BANNER);

        $data =  $data ? json_decode($data,true) : [];

        return $data;
    }

    /**
     * 获取企业商城banner缓存数据
     * @param int $enterprise_id
     * @return array
     */
    public  function getEnterpriseBannerCache(int $enterprise_id):array
    {
        $key = sprintf(Cache::SYSTEM_BANNER_ENTERPRISE,$enterprise_id);

        //获取商城banner缓存信息
        $data = Redis::hGet($key,strval($enterprise_id));

        $data =  $data ? json_decode($data,true) : [];

        return $data;
    }

    /**
     * 保存官方banner信息
     * @param array $data
     * @return bool
     */
    public  function saveBannerCache(array $data):bool
    {
        return Redis::set(Cache::SYSTEM_BANNER,json_encode($data));
    }


    /**
     * 保存企业banner信息
     * @param int $enterprise_id 企业id
     * @param array $data 缓存信息
     * @return int
     */
    public  function saveEnterpriseBannerCache(int $enterprise_id,array $data):int
    {
        return Redis::hSet(Cache::SYSTEM_BANNER_ENTERPRISE,strval($enterprise_id),json_encode($data));
    }


    /**
     * 获取栏目缓存信息
     * @return array
     */
    public  function getSubjectCache():array
    {
        $data = Redis::get(Cache::SYSTEM_SUBJECT);

        $data =  $data ? json_decode($data,true) : [];

        return $data;
    }

    /**
     * 保存栏目缓存信息
     * @param array $data
     * @return bool
     */
    public  function saveSubjectCache(array $data):bool
    {

        return  Redis::Set(Cache::SYSTEM_SUBJECT,json_encode($data));
    }


    /**
     * 获取通知缓存信息
     * @return array
     */
    public  function getNoticeCache():array
    {
        $data = Redis::hGet(Cache::SYSTEM_NOTICE,'notice');

        $data =  $data ? json_decode($data,true) : [];

        return $data;
    }

    /**
     * 保存通知缓存信息
     * @param array $data
     * @return int
     */
    public  function saveNoticeCache(array $data):int
    {
        return  Redis::hSet(Cache::SYSTEM_NOTICE,'notice', json_encode($data));
    }

    /**
     * 获取配置信息
     * @param string $config_name
     * @return mixed
     */
    public  function getConfigCache(string $config_name)
    {

        $data =  Redis::hGet(Cache::SYSTEM_CONFIG,$config_name);

        if(empty($data)){

            /** @var ConfigDao $configDao */
            $configDao =BeanFactory::getBean(ConfigDao::class);

            $data = $configDao->getConfig($config_name);

            if($data) $this->saveConfigCache($data);
        }

        return $data;
    }

    /**
     * 保存配置信息
     * @param array $data
     * @return int
     */
    public  function saveConfigCache(array $data)
    {
       return  Redis::hSet(Cache::SYSTEM_CONFIG,$data['name'],$data['value']);
    }

    /**
     * 获取分类信息
     * @return array|mixed
     */
    public  function getCategoryCache()
    {
        return  Redis::hGetAll(Cache::SYSTEM_CATEGORY);

    }

    /**
     * 保存分类信息
     * @return array|mixed
     */
    public  function saveCategoryCache(array $data)
    {
        foreach ($data as $value)
        {
            Redis::hSet(Cache::SYSTEM_CATEGORY,strval($value['id']),json_encode($value));
        }
        return true;
    }

}