<?php
/**
 * description ProductService.php
 * Created by PhpStorm.
 * User: zengqb
 * DateTime: 2019/12/23 11:43
 */

namespace App\Model\Service;


use App\Common\Cache;
use App\Exception\ApiException;
use App\Model\Dao\AddressDao;
use App\Model\Dao\ArrondiDao;
use App\Model\Dao\AssembleWaresDao;
use App\Model\Dao\CollectDao;
use App\Model\Dao\SubjectDao;
use App\Model\Dao\WaresDao;
use App\Model\Data\ActivityCache;
use App\Model\Data\ProductCache;
use Firebase\JWT\JWT;
use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Bean\Annotation\Mapping\Inject;
use Swoft\Bean\BeanFactory;
use Swoft\Co;
use Swoft\Redis\Redis;
use Swoft\Stdlib\Helper\ArrayHelper;
use Swoft\Stdlib\Helper\JsonHelper;
use Swoft\Task\Task;

/**
 * 商品逻辑操作
 * Class ProductService
 *
 * @Bean(scope=Bean::PROTOTYPE)
 */
class ProductService
{

    /**
     * @Inject()
     * @var ActivityCache
     */
    private  $activityCache;

    /**
     * @Inject()
     * @var ProductCache
     */
    private  $productCache;


    /**
     * @Inject()
     * @var WaresDao
     */
    private $productDao;



    /**
     * 获取全部商品列表
     * @param array $params
     * @return array
     * @throws \Swoft\Db\Exception\DbException
     */
    public function getAllList(array $params)
    {
        //自定义专区商品信息
        if(isset($params['c_arrondy_id']) && $params['c_arrondy_id'])
        {
            $data = $this->getCustomizeList($params);

        }elseif(isset($params['subject_id']) && $params['subject_id']){

            $data = $this->getSubjectList($params);
            
        }else{

            //全部商品列表信息
            $pageIndex = $params['pageIndex'];

            //获取商品shu列表
            $sku_list = $this->productDao->getPageList($pageIndex,$params);

            $data['list'] = [];

            if(empty($sku_list)) return $data;

            $sku_list = ArrayHelper::getColumn($sku_list,'code');

            //获取组合商品信息
            $data['list'] = $this->_associateProduct($sku_list);


        }

        return $data;
    }


    /**
     * 获取限时活动商品列表
     * @param string $code
     * @return array
     * @throws ApiException
     */
    public  function getLimitList(string  $code)
    {
        //验证活动信息
        $data = $this->activityCache->getBaseCache($code);

        if(empty($data)) return [];

        if($data['status']!=1 || $data['begin_time']>time() || $data['end_time']<=time()) return [];

        //验证活动商品信息
        $sku_list = $this->activityCache->getSkuCache($code,$data['id']);

        $data['list'] = $this->_associateActivityProduct($data['id'],$code,$sku_list);

        return $data;
    }


    /**
     * 获取固定专区商品列表
     * @param int $arrondy_id
     * @return array
     */
    public  function getArrondyList(int  $arrondy_id)
    {
        /** @var ArrondiDao $arrondyDao */
        $arrondyDao =\Swoft::getBean(ArrondiDao::class);
        //获取商品列表信息
        $data = $arrondyDao->findById($arrondy_id);

        if(empty($data)) return [];

        $orderDesc = $arrondy_id == 1 ? 'sales' : 'created_at';

        //获取商品shu列表
        $sku_list = $this->productDao->getListByOrder($orderDesc,$data['restrict']);

        $sku_list = ArrayHelper::getColumn($sku_list,'code');

        $data['list'] = [];

        //获取组合商品信息
        if(!empty($sku_list)) $data['list'] = $this->_associateProduct($sku_list);

        unset($data['aid'],$data['restrict'],$data['keyword']);

        return $data;
    }


    /**
     * 获取自定义专区商品列表
     * @param array $params
     * @return array
     */
    public  function getCustomizeList(array $params)
    {
        $pageIndex = $params['pageIndex'];
        $arrondy_id = $params['c_arrondy_id'];

        /** @var ArrondiDao $arrondyDao */
        $arrondyDao =\Swoft::getBean(ArrondiDao::class);
        //获取商品列表信息
        $arrondy_infos  = $arrondyDao->findById($arrondy_id);

        if(empty($arrondy_infos)) return [];

        $data = [];

        $data['keyword'] = $arrondy_infos['keyword'];

        /** @var AssembleWaresDao $assembleDao */
        $assembleDao = \Swoft::getBean(AssembleWaresDao::class);
        //获取商品shu列表
        $sku_list = $assembleDao->getPageListByAid($arrondy_infos['aid'],$pageIndex,$params,$arrondy_infos['restrict']);

        $sku_list = ArrayHelper::getColumn($sku_list,'code');

        $data['list'] = [];

        //获取组合商品信息
        if(!empty($sku_list)) $data['list'] = $this->_associateProduct($sku_list);

        return $data;
    }

    /**
     * 获取专题商品列表
     * @param array $params
     * @return array
     */
    public  function getSubjectList(array $params)
    {
        $pageIndex = $params['pageIndex'];
        $subject_id = $params['subject_id'];

        /** @var SubjectDao $subjectDao */
        $subjectDao = \Swoft::getBean(SubjectDao::class);

        //获取商品列表信息
        $subject_infos  = $subjectDao->findById($subject_id,['keyword','aid']);

        if(empty($subject_infos)) return [];

        $data = [];

        $data['keyword'] = $subject_infos['keyword'];

        /** @var AssembleWaresDao $assembleDao */
        $assembleDao = \Swoft::getBean(AssembleWaresDao::class);
        //获取商品shu列表
        $sku_list = $assembleDao->getPageListByAid($subject_infos['aid'],$pageIndex,$params);

        $sku_list = ArrayHelper::getColumn($sku_list,'code');

        $data['list'] = [];

        //获取组合商品信息
        if(!empty($sku_list)) $data['list'] = $this->_associateProduct($sku_list);

        return $data;
    }

    /**
     * 商品详情
     * @param string $sku
     * @param string|null $code
     * @param string $token
     * @return mixed
     * @throws ApiException
     */
    public  function getProductDetails(string $sku,string $code=null,string $token=null)
    {
        $user_id = $this->_verifyUserToken($token);
        //获取商品基本信息
        $product_info = $this->productCache->getDetailCacheByOne($sku);

        if(empty($product_info)) throw new ApiException('该商品已不存在');

        $data['product']['sku'] = $sku;
        $data['product']['name'] = $product_info['title'];
        $data['product']['price'] = $product_info['integral'];
        $data['product']['market_price'] = $product_info['market_price'];
        $data['product']['app_introduce'] = $product_info['app_introduce'];
        $data['product']['introduce'] = $product_info['product_infos'];
        $data['product']['image'] = $product_info['cover'];
        $data['product']['image_list'] = explode(',',$product_info['images']);
        $data['product']['status'] = $product_info['status'];
        $data['product']['type'] = $product_info['typeid'];
        $type = $data['product']['type'];
        $product_id = $product_info['id'];
        $activityService = BeanFactory::getBean(ActivityService::class);
        $collectDao = BeanFactory::getBean(CollectDao::class);
        $requests = [
                  //获取活动的信息
                 'activity' => function() use($sku,$code,$activityService){return $activityService->getActivityProductByCode($sku,$code);},
                  //获取地址和库存信息
                 'address' => function() use($user_id,$type) {return $this->getUserAddress($type,$user_id);},
                  //获取收藏信息
                 'collect' =>function() use($user_id,$product_id,$collectDao){
                     if(!$user_id) return ;
                     return $collectDao->findById($user_id,$product_id);}
        ];
        $response= Co::multi($requests);

        $data['collect'] =  empty($response['collect']) ? 0 :1;

        $data['activity'] = $response['activity'];
        $data['address'] = $response['address'];

        //添加用户浏览记录
        if($user_id) Task::async('asyn','addHistory',[$user_id,$product_id,$product_info['itemize_id']]);

        return $data;
    }


    /**
     * 获取猜你喜欢商品列表
     * @param string|null $user_token
     * @return array
     */
    public  function  getLovelyList(string $user_token=null)
    {
        //获取用户id
        $user_id = $this->_verifyUserToken($user_token);

        $sku_list = [];

        if($user_id)
        {
            //获取用户历史访问商品分类最新的5个分类
            $category_list = Redis::zRange(sprintf(Cache::HISTORY_CATEGORY,$user_id),0,4);

            //用户从历史分类中选择10个商品,如果用户历史没有访问或者用户没有登录则从主表中选择最新10个商品
            if(!empty($category_list))  $sku_list = $this->productDao->getListByItemize($category_list);
        }
        $count = count($sku_list);

        //如果用户分类为获取超过10个商品则主表中再取剩余的
        if($count<10)
        {
            $temp = $this->productDao->getListByItemize(null,(10-$count));

            $sku_list = array_merge($sku_list,$temp);
        }

        $sku_list = ArrayHelper::getColumn($sku_list,'code');

        $data['list'] = [];

        //获取组合商品信息
        if(!empty($sku_list)) $data['list'] = $this->_associateProduct($sku_list);

        return $data;
    }


    /**
     * 获取商品列表信息(只包含活动商品)
     * @param int $activity_id
     * @param string $code
     * @param array $sku_list
     * @return mixed
     * @throws ApiException
     */
    public  function _associateActivityProduct(int $activity_id,string  $code,array  $sku_list)
    {

        if(empty($sku_list)) throw new ApiException('活动商品信息异常');

        $requests = [
            //获取活动商品基本信息
            'ac_product_base' => function() use($sku_list,$code) {return $this->activityCache->getListProductCache($code,$sku_list);},
            //获取商品基本信息
            'product_base' => function() use($sku_list) {return $this->productCache->getDetailCache($sku_list);},
        ];

        $response= Co::multi($requests);
        $product_base =$response['product_base'];
        $activity_product_base = $response['ac_product_base'];

        $temps = [];

        //组合商品的数据
        foreach ($sku_list as $k=>$v)
        {
            //商品基本信息缓存
            if(isset($product_base[$v]))
            {
                $base = JsonHelper::decode($product_base[$v],true);
            }else{
                $base = $this->productCache->getDetailCacheByOne($v);
            }

            //活动商品缓存
            if(isset($activity_product_base[$v]))
            {
                $product_info = JsonHelper::decode($activity_product_base[$v],true);
            }else{

                $product_info =$this->activityCache->getBaseCacheByOne($code,$v,$activity_id);
            }

            if(empty($product_info)) throw new ApiException('活动商品信息异常:'.$v);


            $temp['sku'] = $v;
            $temp['name'] = $base['title'];
            $temp['image'] = $base['cover'];
            $temp['type'] = $base['typeid'];
            $temp['status'] = $base['status'];
            $temp['price'] = $base['integral'];
            $temp['new_price'] = $product_info['integral'];
            $temp['stock_eable'] = $this->activityCache->getInventoryCache($code,$v);
            $temp['stock'] = $product_info['stock'];

            array_push($temps,$temp);
        }

        return $temps;
    }


    /**
     * 获取商品列表信息(包含普通和活动商品)
     * @param array $sku_list
     * @return array
     * @throws ApiException
     */
    public  function _associateProduct(array $sku_list)
    {
        if(empty($sku_list)) return [];

        /** @var ActivityService $activityService */
        $activityService = BeanFactory::getBean(ActivityService::class);

        //获取商品基本信息
        $product_base = $this->productCache->getDetailCache($sku_list);

        //组合商品信息
        $temps  = [];

        foreach ($sku_list as $k=>$v)
        {
            if(isset($product_base[$v]))
            {
                $base = json_decode($product_base[$v],true);

            }else{
                //获取商品信息并存入缓存
                $base =  $this->productCache->getDetailCacheByOne($v);

                if(empty($base)) throw new ApiException('商品信息异常'.$v);
            }

            $temp['sku'] = $v;
            $temp['name'] = $base['title'];
            $temp['status'] = $base['status'];
            $temp['image'] = $base['cover'];
            $temp['type'] = $base['typeid'];
            $temp['price'] = $base['integral'];
            $temp['market_price'] = $base['market_price'];

            //获取该商品的活动信息
            $ac_product_info  =$activityService->getActivityProduct($v);

            $temp['code']= '';
            if($ac_product_info)
            {
                $temp['code'] = $ac_product_info['code'];
                $temp['price'] = $ac_product_info['price'];
            }

            array_push($temps,$temp);

            unset($temp);
        }
        return $temps;
    }


    /******************************************************************************************************/

    /**
     * 获取用户地址信息
     * @param int $type
     * @param int|null $user_id
     * @return array|null|object|\Swoft\Db\Eloquent\Model|static
     */
    private  function getUserAddress(int $type,int $user_id=null)
    {
        //非京东商品
        if($type!==3)  return [];

        //默认地址
        $data['province_id'] = 0;
        $data['city_id'] = 0;
        $data['country_id'] = 0;
        $data['town_id'] = 0;

        if(!empty($user_id))
        {
            //获取用户默认的地址
            $addressDao = \Swoft::getBean(AddressDao::class);

            /** @var AddressDao $addressDao */
            $result = $addressDao->findOne(['uid'=>$user_id,'default'=>1]);

            if(!empty($result)){

                $result['area'] = str_replace(',','>',$result['area']);

                $data = $result;
            }
        }
        // 获取商品在该地址库存信息

        return $data;
    }


    /**
     * 验证用户是否登录并获取user_id
     * @param string $user_token
     * @return null
     */
    private  function _verifyUserToken(string $user_token)
    {
        $user_id = null;

        if(empty($user_token)) return $user_id;

        try {
            $auth = JWT::decode($user_token, config('jwt.secret_key'), [config('jwt.type')]);

            $user_id = $auth->user_id;

        } catch (\Exception $e) {

            $user_id = null;
        }

        return $user_id;
    }
}