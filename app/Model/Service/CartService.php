<?php
/**
 * description CartService.php
 * Created by PhpStorm.
 * User: zengqb
 * DateTime: 2019/12/25 17:11
 */

namespace App\Model\Service;


use App\Exception\ApiException;
use App\Listener\Event;
use App\Model\Data\CartCache;
use App\Model\Data\ProductCache;
use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Bean\BeanFactory;
use Swoft\Co;
use Swoft\Stdlib\Helper\ArrayHelper;

/**
 * 兑换带
 * Class CartService
 *
 * @Bean(scope=Bean::PROTOTYPE)
 */
class CartService
{

    /**
     * 添加商品数量
     * @param int $user_id
     * @param string $sku
     * @param int $num
     * @return bool
     * @throws ApiException
     */
    public  function addProduct(int $user_id,string $sku,int $num):bool
    {
        if(!preg_match("/^[1-9][0-9]*$/",$num))
        {
            throw new ApiException('商品数量异常');
        }
        //判断用户兑换袋中的商品是否已满50个
        /** @var CartCache $cartCache */
        $cartCache = \Swoft::getBean(CartCache::class);
        $count = $cartCache->getCount($user_id);
        if($count>49) throw new ApiException('兑换袋数量已超过上限');

        //判断商品是否活动商品,若是活动商品是否是还有库存
        $data =[
            'user_id' =>$user_id,
            'sku'=>$sku,
            'num' =>$num
        ];

        \Swoft::triggerByArray(Event::CART_PRODUCT_ADD,null,$data);

        return true;
    }


    /**
     * 移除商品
     * @param int $user_id 用户id
     * @param string $sku_list 商品集合
     * @return bool
     */
    public  function removeProduct(int $user_id,string $sku_list):bool
    {

        $sku_list =explode(',',$sku_list);

        $data =[
            'user_id' =>$user_id,
            'sku_list'=>$sku_list,
        ];

        \Swoft::triggerByArray(Event::CART_PRODUCT_REMOVE,null,$data);

        return true;
    }


    /**
     * 更新商品数量
     * @param int $user_id
     * @param string $sku
     * @param int $num
     * @return bool
     * @throws ApiException
     */
    public  function updateProduct(int $user_id,string $sku,int $num):bool
    {
        if(!preg_match("/^[1-9][0-9]*$/",$num))
        {
            throw new ApiException('商品数量异常');
        }
        $data =[
            'user_id' =>$user_id,
            'sku'=>$sku,
            'num' =>$num
        ];

        \Swoft::triggerByArray(Event::CART_PRODUCT_UPDATE_NUM,null,$data);

        return true;
    }

    /**
     * 用户兑换袋商品列表
     * @param int $user_id
     * @return array
     */
    public  function getCartList(int $user_id)
    {
        /** @var CartCache $cartCache */
        $cartCache = \Swoft::getBean(CartCache::class);

        $cart_list = $cartCache->getProductCache($user_id);

        if(empty($cart_list)) return [];

        $sku_list = [];

        //获取sku列表
        foreach ($cart_list as $k=>$value) array_push($sku_list,$k);

        /** @var ProductService $productService */
        $productService = BeanFactory::getBean(ProductService::class);

        $data = $productService->_associateProduct($sku_list);

        foreach ($data as $k=>$v) $data[$k]['num'] =$cart_list[$v['sku']];

        return ['list'=>$data];
    }


    /**
     * 用户兑换袋商品列表
     * @param int $user_id
     * @return array
     */
    public  function getCartList1(int $user_id)
    {
        /** @var CartCache $cartCache */
        $cartCache = \Swoft::getBean(CartCache::class);

        $cart_list = $cartCache->getProductCache($user_id);

        if(empty($cart_list)) return [];

        $data = [];

        $sku_list = ArrayHelper::getColumn($cart_list,'code');

        $data['list'] = [];

        //获取组合商品信息
        if(!empty($sku_list)) $data['list'] = $this->_associateProduct($sku_list);

        $activityService = BeanFactory::getBean(ActivityService::class);
        $productCache = BeanFactory::getBean(ProductCache::class);

        foreach ($cart_list as $sku=>$v)
        {
            $requests = [
                //获取活动商品的信息
                'activity' => function() use($sku,$activityService) {
                    return $activityService->getActivityProduct($sku);
                },
                //获取商品基本的信息
                'base' => function() use($sku,$productCache) {
                    $product_info = $productCache->getDetailCacheByOne($sku);
                    $data['sku'] = $sku;
                    $data['name'] = $product_info['title'];
                    $data['price'] = $product_info['integral'];
                    $data['image'] = $product_info['cover'];
                    $data['status'] = $product_info['status'];
                    $data['type'] = $product_info['typeid'];
                    return $data;
                },
            ];

            $response= Co::multi($requests);

            $temp['sku'] = $sku;
            $temp['name'] = $response['base']['name'];
            $temp['image'] = $response['base']['image'];
            $temp['type'] =  $response['base']['type'];
            $temp['status'] = $response['base']['status'];
            $temp['price'] = $response['base']['price'];
            $temp['num'] = $v;
            if($response['activity'])
            {
                $temp['stock'] =  $response['activity']['stock_eable'];
                $temp['limit'] =  $response['activity']['limit'];
                $temp['price'] =  $response['activity']['price'];
            }
            array_push($data,$temp);
        }

        return $data;
    }
}