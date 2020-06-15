<?php declare(strict_types=1);

namespace App\Model\Service;

use App\Exception\ApiException;
use App\Model\Dao\ActivityDao;
use App\Model\Dao\ArrondiDao;
use App\Model\Dao\ConfigDao;
use App\Model\Dao\NoticeDao;
use App\Model\Dao\SubgroupDao;
use App\Model\Dao\SubjectDao;
use App\Model\Dao\SystemDao;
use App\Model\Dao\EnterPriseDao;
use App\Model\Dao\WaresDao;
use App\Model\Data\ActivityCache;
use App\Model\Data\ArrondyCache;
use App\Model\Data\SystemCache;
use App\Utils\Check;
use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Bean\Annotation\Mapping\Inject;
use Swoft\Bean\BeanFactory;
use Swoft\Redis\Redis;
use Swoft\Stdlib\Helper\ArrayHelper;
use Swoft\Stdlib\Helper\JsonHelper;

/**
 * 系统配置逻辑
 * Class SystemService
 *
 * @Bean(scope=Bean::PROTOTYPE)
 */
class SystemService
{

    /**
     * @Inject()
     * @var EnterPriseDao
     */
    private  $enterPriseDao;

    /**
     * @Inject()
     * @var SystemDao
     */
    private  $systemDao;

    /**
     * @Inject()
     * @var ArrondyCache
     */
    private  $arrondyCache;

    /**
     * @Inject()
     * @var ArrondiDao
     */
    private $arrondiDao;


    /**
     * @Inject()
     * @var  SubjectDao
     */
    private $subjectDao;

    /**
     * @Inject()
     * @var NoticeDao
     */
    private $noticeDao;

    /**
     * @Inject()
     * @var SystemCache
     */
    private $systemCache;

    /**
     * @Inject()
     * @var ActivityCache
     */
    private $activityCache;

    /**
     * @Inject()
     * @var ConfigDao
     */
    private  $configDao;


    /**
     * 获取商城配置信息
     * @param string $mark 企业标志
     * @return array
     */
    public  function getMallInfo(string $mark):array
    {
        //验证商品信息
        $enterPrice_info = $this->_verifyEnterPrise($mark);

        //获取banner缓存信息
        $data['list'] = $this->getBannerList($enterPrice_info);

        //获取首页关键词
        $keyword = $this->systemCache->getConfigCache('site_index_recommend');

        //获取商城配置数据
        $data['keyword'] =$keyword;

        $data['mallName'] = $enterPrice_info['mall_name'];

        return $data;

    }

    /**
     * 获取专题栏目逻辑
     * @return array
     */
    public  function getSubjectList():array
    {
        $data = $this->systemCache->getSubjectCache();

        if(empty($data))
        {
            //获取栏目信息
            $data = $this->subjectDao->getList();

            //缓存栏目信息
            if($data) $this->systemCache->saveSubjectCache($data);
        }

        return $data;
    }


    /**
     * 获取限时活动商品信息
     * @return array
     * @throws ApiException
     */
    public function getLimitInfo():array
    {
        //获取所有限时活动基本信息
        $data = $this->activityCache->getAllBaseCache();

        if(empty($data)) return [];

        $temp= []; $now_time = time();

        /** @var ProductService $productService */
        $productService = BeanFactory::getBean(ProductService::class);

        //获取活动商品信息
        foreach ($data as $k=>$v)
        {
            if(!is_array($v))  $v= json_decode($v,true);

            if($v['status']!=1) continue;

            if($v['begin_time']>$now_time || $v['end_time']<=$now_time) continue;

            //获取商品的sku
            $sku_list= $this->activityCache->getSkuCache($v['code'],$v['id'],0,9);

            if(empty($sku_list)) throw new ApiException('活动商品信息异常');

            $v['products'] = $productService->_associateActivityProduct($v['id'],$v['code'],$sku_list);

            foreach ($v['products'] as $kk=> &$vv) unset($vv['stock_eable'],$vv['stock']);

            array_push($temp,$v);
        }

        return $temp;
    }


    /**
     * 获取自定义专区信息
     * @return array
     */
    public  function getCustomize():array
    {
        //获取自定义专区栏目信息
        $data = $this->arrondyCache->getCustomizeCache();

        if(empty($data))
        {
            $data = $this->arrondiDao->getList();

            if(empty($data)) return [];

            $this->arrondyCache->saveCustomizeCache($data);
        }

        /** @var ProductService $productService */
        $productService = BeanFactory::getBean(ProductService::class);

        //重组专区推荐商品数据
        foreach ($data as $k=>$v)
        {
            $sku_list = $this->arrondiDao->getCustomizeListByAid($v['aid'],3);

            $data[$k]['products']= [];

            //获取商品信息
            if(!empty($sku_list))
            {
                $sku_list = ArrayHelper::getColumn($sku_list,'code');

                $data[$k]['products'] = $productService->_associateProduct($sku_list);
            }
            unset($data[$k]['aid']);
        }

        return $data;
    }

    /**
     * 获取固定专区信息
     * @return array
     */
    public  function getArrondy():array
    {
        //获取专区栏目信息

        $data = $this->arrondyCache->getArrondyCache();

        if(empty($data))
        {
            $data = $this->arrondiDao->getList([1,2]);

            if(empty($data)) return [];

            $this->arrondyCache->saveArrondyCache($data);
        }

        /** @var ProductService $productService */
        $productService = BeanFactory::getBean(ProductService::class);

        /** @var WaresDao $waresDao */
        $waresDao = BeanFactory::getBean(WaresDao::class);

        //重组专区推荐商品数据
        foreach ($data as $k=>$v)
        {
            $orderDesc = $v['arrondi_id']==2 ? 'created_at':'sales';

            //获取商品信息
            $sku_list = $waresDao->getListByOrder($orderDesc,9);

            if(!empty($sku_list))
            {
                $sku_list = ArrayHelper::getColumn($sku_list,'code');

                $data[$k]['products'] = $productService->_associateProduct($sku_list);
            }

            unset($data[$k]['aid']);
        }

        return $data;
    }

    /**
     * 获取通知消息
     * @return array
     */
    public  function getNotice():array
    {
        //获取通知信息
        $data = $this->systemCache->getNoticeCache();

        if(empty($data))
        {
            //获取系统通知信息
            $data = $this->noticeDao->getOne();

            if(empty($data)) return [];

            $data['sign'] = explode('#',$data['sign']);

            //缓存栏目信息
            $this->systemCache->saveNoticeCache($data);
        }
        //获取系统关闭时间
        $data['close_time'] = $this->systemCache->getConfigCache('site_auto_close');

        return $data;
    }


    /**
     * 获取热门搜索
     * @return array|mixed
     */
    public  function getHotSearch()
    {
        //获取热门搜索信息
        $hot_list = $this->systemCache->getConfigCache('site_hot_search');

        if($hot_list) $hot_list = explode(',',$hot_list);

        $data['list'] = $hot_list;

        return $data;
    }

    /**
     * 获取分类信息
     * @return array
     */
    public  function getCategory()
    {
        //获取分类信息
        $category_list = $this->systemCache->getCategoryCache();

        $items = array();

        if(empty($category_list))
        {
            //获取分类信息
            /** @var SubgroupDao $subgroupDao */
            $subgroupDao = \Swoft::getBean(SubgroupDao::class);

            $category_list = $subgroupDao->getList();

            if(empty($category_list)) return  [];

            //保存分类信息
            $this->systemCache->saveCategoryCache($category_list);

            //初始化分类信息
            foreach($category_list as $v)
            {
                $items[$v['id']] = $v;
            }

        }else{
            //初始化分类信息
            foreach($category_list as $v)
            {
                $v = json_decode($v,true);

                $items[$v['id']] = $v;
            }
        }

        $data['list'] = array();

        //获取分类子分类信息
        foreach($items as $k => $item)
        {
            if(isset($items[$item['pid']]))
            {
                $items[$item['pid']]['son'][] = &$items[$k];

            }else{
                $data['list'][] = &$items[$k];
            }
        }

        return $data;
    }
/******************************************************************************************/

    /**
     * 验证企业信息
     * @param string $mark
     * @return null|object|\Swoft\Db\Eloquent\Model|static
     */
   private  function _verifyEnterPrise(string  $mark)
   {
       //验证参数
       Check::checkNull($mark,'非法操作');

       //验证企业是否存在
       $enterPrice_info = $this->enterPriseDao->findBySign($mark);

       Check::checkBoolean($enterPrice_info,'非法操作');

       Check::checkIntEqual(intval($enterPrice_info['state']),1,'商城已关闭');

       return $enterPrice_info;
   }


    /**
     * 获取banner列表
     * @param $enterPrice_info
     * @return array
     */
   private  function getBannerList($enterPrice_info)
   {
       //获取企业商城banner
       $en_banner_list = $this->systemCache->getEnterpriseBannerCache($enterPrice_info['id']);

       if(empty($en_banner_list))
       {
           //获取企业banner
           if(!empty($enterPrice_info['banner']))
           {
               $en_banner_ids  = explode(',',$enterPrice_info['banner']);

               //获取banner列表
               $en_banner_list = $this->systemDao->getBannerList($en_banner_ids);

               if($en_banner_list)  $this->systemCache->saveEnterpriseBannerCache($enterPrice_info['id'],$en_banner_list);
           }else{

               $en_banner_list = [];
           }
        }

       //获取官方的banner
       $au_banner_list = $this->systemCache->getBannerCache();

       if(empty($au_banner_list))
       {
           //获取官方banner
           $au_banner_ids =$this->configDao->getConfig('site_ad')['value'];

           if(!empty($au_banner_ids))
           {
               $au_banner_list = $this->systemDao->getBannerList(explode(',',$au_banner_ids));

               $this->systemCache->saveBannerCache($au_banner_list);
           }else{

               $au_banner_list = [];
           }
       }

       return ArrayHelper::merge($en_banner_list,$au_banner_list);
   }

}