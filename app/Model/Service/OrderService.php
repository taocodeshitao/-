<?php

namespace App\Model\Service;


use App\Common\Cache;
use App\Exception\ApiException;
use App\Listener\Event;
use App\Model\Dao\ActivityDao;
use App\Model\Dao\ActivityDotStockDao;
use App\Model\Dao\ActivityDotStockLogDao;
use App\Model\Dao\ActivityMemberApi;
use App\Model\Dao\ActivityMemberApiDao;
use App\Model\Dao\ActivityMemberDao;
use App\Model\Dao\ActivityProductDao;
use App\Model\Dao\ActivityStockLogDao;
use App\Model\Dao\ActivityWaresDao;
use App\Model\Dao\AddressDao;
use App\Model\Dao\GradeApiConfigDao;
use App\Model\Dao\GradeDao;
use App\Model\Dao\OrderAddressDao;
use App\Model\Dao\OrderDao;
use App\Model\Dao\OrderLog;
use App\Model\Dao\OrderLogDao;
use App\Model\Dao\OrderLogisticDao;
use App\Model\Dao\OrderProductDao;
use App\Model\Dao\OrderSupplierDao;
use App\Model\Dao\OrderWaresDao;
use App\Model\Dao\ProductDao;
use App\Model\Dao\WaresDao;
use App\Model\Data\ProductCache;
use App\Utils\Check;
use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Bean\Annotation\Mapping\Inject;
use Swoft\Bean\BeanFactory;
use Swoft\Co;
use Swoft\Db\DB;
use Swoft\Redis\Redis;
use Swoft\Stdlib\Helper\ArrayHelper;
use Swoft\Stdlib\Helper\JsonHelper;
use Swoft\Task\Task;

/**
 * 订单逻辑
 * Class OrderService
 * @package App\Model\Service
 *
 * @Bean(scope=Bean::PROTOTYPE)
 */
class OrderService
{

    /**
     * @Inject()
     * @var ProductDao
     */
    private $productDao;

    /**
     * @Inject()
     * @var OrderProductDao
     */
    private $orderProductDao;


    /**
     * @Inject()
     * @var ActivityStockLogDao
     */
    private $activityStockLogDao;


    /**
     * @Inject()
     * @var OrderSupplierDao
     */
    private $orderSupplierDao;

    /**
     * @Inject()
     * @var OrderLogDao
     */
    private $orderLogDao;

    /**
     * @Inject()
     * @var OrderDao
     */
    private $orderDao;

    /**
     * @Inject()
     * @var OrderAddressDao
     */
    private $orderAddressDao;


    /**
     * @Inject()
     * @var OrderLogisticDao
     */
    private $orderLogisticDao;

    /**
     * @Inject()
     * @var ActivityDotStockLogDao
     */
    private $activityDotStockLogDao;

    /**
     * @Inject()
     * @var ActivityMemberDao
     */
    private $activityMemberDao;

    /**
     * @Inject()
     * @var ActivityDao
     */
    private $activityDao;

    /**
     * @Inject()
     * @var ActivityProductDao
     */
    private $activityProductDao;

    /**
     * @Inject()
     * @var GradeDao
     */
    private $gradeDao;

    /**
     * @Inject()
     * @var GradeApiConfigDao
     */
    private $gradeApiConfigDao;

    /**
     * @Inject()
     * @var ActivityMemberApiDao
     */
    private $activityMemberApi;

    /**
     * @Inject()
     * @var ActivityDotStockDao
     */
    private $activityDotStockDao;


    /**
     * 订单列表（网点端）
     * @param int $uid
     * @param array $params
     * @param int $integral
     * @return array
     * @throws \Swoft\Db\Exception\DbException
     */
    public function ordersList(array $params)
    {
        $token=$params['dot_token'];

        //获取登录信息
        $dot_data = Redis::get(config('jwt.REDIS_DATEBASE_PREFIX').':DOT:LOGIN:'.$token);
        $dot_data = json_decode($dot_data,true);

        $dot_id=$dot_data['dot_id'];

        $orderlist = $this->orderDao->getOrdersPage($dot_id, $params['page'],$params);

        return $orderlist;
    }

    /**
     * 订单列表（会员端）
     * @param int $uid
     * @param array $params
     * @param int $integral
     * @return array
     * @throws \Swoft\Db\Exception\DbException
     */
    public function memebrOrdersList(array $params)
    {
        $token=$params['member_token'];

        //获取会员的登录信息
        $member_data = Redis::get(config('jwt.REDIS_DATEBASE_PREFIX').':MEMBER:LOGIN:'.$token);
        $member_data = json_decode($member_data,true);
        if (empty($member_data)) throw new ApiException('会员信息查询失败');

        $orderlist = $this->orderDao->getMemberOrdersPage($member_data['member_id'], $params['page'],$params);

        return $orderlist;
    }


    /**
     * 订单详情（网点）
     * @param int $uid
     * @param array $params
     * @param int $integral
     * @return array
     * @throws \Swoft\Db\Exception\DbException
     */
    public function orderInfo(array $params){
        $order_id = $params['order_id'];
        if (empty($order_id)) throw new ApiException('订单ID为空误');


        $orderlist = $this->orderDao->findById($order_id);

        if (empty($orderlist)) throw new ApiException('订单信息异常');

        if ($orderlist['type']==2){
            $orderlist['receive_time']= DB::table('activity_dot_stock_log')->where('order_id',$order_id)->select('out_of_stock_time as receive_time')->first()['receive_time'];
        }

        //已发货查询出物流信息
        if ($orderlist['express_status']==2&&$orderlist['type']==1){
            /** @var OrderLogisticDao $orderLogisticDao */
            $orderLogisticDao = \Swoft::getBean(OrderLogisticDao::class);

            //查询订单商品物流信息
            $express_list = $orderLogisticDao->getListByOrderId($order_id);

            $api_token =  Redis::get(config('jwt.REDIS_DATEBASE_PREFIX').':BLH:API:BLH_API_TOKEN');
            $api_token_data = json_decode($api_token,true);
            $post_data['token'] =  $api_token_data['token'];
            $post_data['nums']=$express_list['express_code'];

            if (empty($api_token_data['token'])||empty($express_list['express_code'])) throw new ApiException('系统繁忙');


            $result = post_curl_func(config('express_order_url'),JsonHelper::encode($post_data));

            if($result['errCode']!='0000') throw new ApiException('系统繁忙');

            $orderlist['logistics_info'] = $result['data'];
        }

        return $orderlist;

    }

    /**
     * 订单详情（会员）
     * @param int $uid
     * @param array $params
     * @param int $integral
     * @return array
     * @throws \Swoft\Db\Exception\DbException
     */
    public function memberOrderInfo(array $params){
        $order_id = $params['order_id'];
        if (empty($order_id)) throw new ApiException('订单ID为空误');


        $orderlist = $this->orderDao->findById($order_id);
        if (empty($orderlist)) throw new ApiException('订单信息异常');
        if ($orderlist['type']==2){
            $orderlist['express_time']= DB::table('activity_dot_stock_log')->where('order_id',$order_id)->select('out_of_stock_time as receive_time')->first()['receive_time'];
        }

        //已发货查询出物流信息
        if ($orderlist['express_status']==2&&$orderlist['type']==1){
            /** @var OrderLogisticDao $orderLogisticDao */
            $orderLogisticDao = \Swoft::getBean(OrderLogisticDao::class);

            //查询订单商品物流信息
            $express_list = $orderLogisticDao->getListByOrderId($order_id);

            $api_token =  Redis::get(config('jwt.REDIS_DATEBASE_PREFIX').':BLH:API:BLH_API_TOKEN');
            $api_token_data = json_decode($api_token,true);
            $post_data['token'] =  $api_token_data['token'];
            $post_data['nums']=$express_list['express_code'];

            //if (empty($params['token'])||empty($express_list['express_code'])) throw new ApiException('系统繁忙');

            $result = post_curl_func(config('express_order_url'),JsonHelper::encode($post_data));

            if($result['errCode']!='0000') throw new ApiException('系统繁忙');

            $orderlist['logistics_info'] = $result['data'];
        }

        return $orderlist;
    }

    /**
     * 发放商品
     */
    public function grantProduct($params){
        $order_id = $params['order_id'];

        if (empty($order_id)) throw new ApiException('订单ID为空误');
        $token=$params['dot_token'];

        //获取登录信息
        $dot_data = Redis::get(config('jwt.REDIS_DATEBASE_PREFIX').':DOT:LOGIN:'.$token);
        $dot_data = json_decode($dot_data,true);


        $orderlist = $this->orderDao->findById($order_id);
        if (empty($orderlist)) throw new ApiException('订单信息异常');

        //修改订单状态
        $this->orderDao->updateById($order_id,['express_status'=>2,'status'=>2,'updated_at'=>time(),'confirm_order_user_id'=>$dot_data['id']]);

        $this->activityDotStockLogDao->updateOrderTime($order_id);
        //$this->orderDao($order_id,['express_status'=>2,'status'=>2,'updated_at'=>time(),'confirm_order_user_id'=>$dot_data['id']]);

        $this->activityStockLogDao->updateOrderSn($dot_data['id'],$dot_data['id']);

        return [];
    }

    /**
     * 验证活动会员是否满足兑换条件
     * @param $activity_id
     * @param $product_id
     * @param int $grade_id
     */
    public function checkActivity($activity_id,$product_id,$uniqueuserid,$grade_id=0){

        $activity_data =$this->activityDao->getOndByCode($activity_id);
        //查询活动下商品的的档次
        $getActivityProductByIdData=$this->activityProductDao->getActivityProductById($activity_id,$product_id);

        if (empty($getActivityProductByIdData)) throw new ApiException('活动商品不存在');

        //导入
        if ($activity_data['member_source']==1){

            //查询活动一共有几个档次
            $activityMemberGrade =$this->activityMemberDao->getActivityGrade($activity_id,$grade_id,$uniqueuserid);
            if (empty($activityMemberGrade)) throw new ApiException('您没有领取机会');

            $grade_id_data = array_column($activityMemberGrade,'grade_id');
            //验证是该商品是否在活动档次中
            $grade_status =$this->deep_in_array($getActivityProductByIdData['grade_id'], $grade_id_data);
            if (!$grade_status) throw new ApiException('您没有领取该商品的权限');
        }

        //接口
        if ($activity_data['member_source']==2){

            $grade_data =  $this->gradeDao->getOndByCode($activity_id);
            if (empty($grade_data)) throw new ApiException('线下活动档次不存在');

            $grade_id_data = array_column($grade_data,'id');
            $grade=[];
            foreach ($grade_id_data as $val){
                $gradeApiData = $this->gradeApiConfigDao->getOneGradeById($val);

                $where_str='?uniqueuserid='.$uniqueuserid;
                $url='';
                foreach ($gradeApiData as $key=>$v){
                    $url=$v['url'];
                    $where_str=$where_str.$v['api_where'].'='.$v['api_val'].'$';
                }
                $where_str = rtrim($where_str, '&');
                $result =post_curl_func($url,$where_str);
                if ($result){
                    $grade[]=$val ;
                }
            }
            $grade_status =$this->deep_in_array($getActivityProductByIdData['grade_id'], $grade);
            if (!$grade_status) throw new ApiException('您没有领取该商品的权限');

        }
    }

    /**
     * 检测值是不是在二维数组中
     * @param $value
     * @param $array
     * @return bool
     */
   public function deep_in_array($value, $array)
    {
        foreach ($array as $item) {
            if (!is_array($item)) {
                if ($item == $value) {
                    return true;
                } else {
                    continue;
                }
            }
            if (in_array($value, $item)) {
                return true;
            } else if (deep_in_array($value, $item)) {
                return true;
            }
        }
        return false;
    }


    /**
     * 确认订单（线下）
     */
    public function createOrderLower($params){

        $token=$params['member_token'];
        if (empty($params['activity_id'])) throw new ApiException('活动ID错误');
        if (empty($params['product_id'])) throw new ApiException('商品ID错误');
        if (empty($params['dot_id'])) throw new ApiException('网点ID错误');

        //获取需要下单的信息
        $activity_data=[
            'activity_id'=>$params['activity_id'],
            'product_id'=>$params['product_id'],
            'dot_id'=>$params['dot_id'],
            'region_id'=>$params['region_id'],
            'grade_id'=>$params['grade_id'],
        ];
        if (empty($activity_data)) throw new ApiException('活动信息不存在');

        //获取会员的登录信息
        $member_data = Redis::get(config('jwt.REDIS_DATEBASE_PREFIX').':MEMBER:LOGIN:'.$token);
        $member_data = json_decode($member_data,true);
        if (empty($member_data)) throw new ApiException('会员信息查询失败');

        //验证活动方式并检测是否满足领取商品活动档次
        $this->checkActivity($activity_data['activity_id'],$activity_data['product_id'],$member_data['unique_code']);

        //验证库存网点库存
       $dotStockData =  $this->activityDotStockDao->getDotStock($activity_data['activity_id'],$activity_data['dot_id'],$activity_data['region_id'],$activity_data['product_id']);

       if ($dotStockData['surplus_stock_num']<=0) throw new ApiException('剩余库存不足',1003);

        $order_sn = $this->getSn();
        try {
            DB::beginTransaction();
            //查询商品属性
            $product_data = $this->productDao->getProductById($activity_data['product_id']);

            $order_add=[
                'activity_id'=>$activity_data['activity_id'],
                'type'=>2,
                'member_id'=>$member_data['member_id'],
                'sn'=>$order_sn,
                'pay_type'=>1,
                'nature'=>$product_data['nature'],
                'status'=>1,
                'pay_status'=>1,
                'express_status'=>1,
                'dot_id'=>$activity_data['dot_id'],
                'created_at'=>time(),
                'mobile'=>"",
            ];
            $order_id = $this->orderDao->addData($order_add);

            //添加商品
            $order_product_add=[
                'order_id'=>$order_id,
                'product_id'=>$activity_data['product_id'],
                'product_name'=>$product_data['name'],
                'settlement_price'=>$product_data['settlement_price'],
                'number'=>1,
                'created_at'=>time(),
            ];

            $this->orderProductDao->addData($order_product_add);

            $order_log=[
                'order_id'=>$order_id,
                'content'=>'客户提交订单',
                'type'=>1,
                'member_id'=>$member_data['member_id'],
                'admin_user_id'=>0,
                'created_at'=>time(),
            ];
            $this->orderLogDao->addData($order_log);

            //记录领取信息
            $activity_member_api_log=[
                'activity_id'=>$activity_data['activity_id'],
                'dot_id'=>$activity_data['dot_id'],
                'region_id'=>$activity_data['region_id'],
                'grade_id'=>$activity_data['grade_id'],
                'unique_code'=>$member_data['unique_code'],
                'order_id'=>$order_id,
            ];

            //记录会员的领取信息
            $this->addMemberReceive($activity_data['activity_id'],$activity_member_api_log,$activity_data['product_id']);

            //更新商品的库存
            $this->activityDotStockDao->getReduceStock($dotStockData['id']);

           // Task::async('asyn','reduce',[$activity_data['activity_id'],$activity_data['product_id'],$activity_data['dot_id'],$activity_data['grade_id']]);

            //添加库存消耗日志
            $activityStockLogdata=[
                'activity_dot_stock_id'=>$dotStockData['id'],
                'product_id'=>$activity_data['product_id'],
                'dot_id'=>$activity_data['dot_id'],
                'activity_id'=>$activity_data['activity_id'],
                'type'=>4,
                //'num'=>1,
                'order_id'=>$order_id,
                'created_at'=>time()
            ];
            $this->activityDotStockLogDao->addData($activityStockLogdata);


            //添加列表日志
            $activityStockLogdata=[
                'product_id'=>$activity_data['product_id'],
                'dot_id'=>$activity_data['dot_id'],
                'activity_id'=>$activity_data['activity_id'],
                'type'=>8,
                'num'=>1,
                'is_comfirmation'=>1,
                'confirmation_time'=>time(),
                'created_at'=>time()
            ];
            $this->activityStockLogDao->addData($activityStockLogdata);

            DB::commit();
            $activity_data12 = $this->activityDao->getOndByCode($activity_data['activity_id']);
            if ($activity_data12['member_source']==1){
                //查询会员还能领多少次
                $receive_num = $this->activityMemberDao->getReceiveNum($activity_data['grade_id'],$activity_data['activity_id'],$member_data['unique_code'],$activity_data['dot_id']);
            }else{
                $receive_num=0;
            }
            return ['order_sn'=>$order_sn,'receive_num'=>$receive_num];
        } catch (ApiException $e) {

            DB::rollBack();

            throw new ApiException($e->getMessage());
        }
    }


    /**
     * 线上创建订单
     */
    public function createOrderUpper($params){

        $token=$params['member_token'];
        $mobile=$params['mobile'];

        //if (empty($params['dot_id'])) throw new ApiException('档次ID为空');
        if (empty($params['grade_id'])) throw new ApiException('区域ID为空');
        if (empty($params['product_id'])) throw new ApiException('商品ID为空');


        $activity_data=[
            'activity_id'=>$params['activity_id'],
            'product_id'=>$params['product_id'],
            'grade_id'=>$params['grade_id'],
            //'dot_id'=>$params['dot_id']
        ];

        //获取会员的登录信息
        $member_data = Redis::get(config('jwt.REDIS_DATEBASE_PREFIX').':MEMBER:LOGIN:'.$token);
        $member_data = json_decode($member_data,true);
        if (empty($member_data)) throw new ApiException('会员信息查询失败');

        //验证活动方式并检测是否满足领取商品活动档次(导入的方式)
        $this->checkActivity($activity_data['activity_id'],$activity_data['product_id'],$member_data['unique_code']);

        check::checkMobile($mobile,'手机号格式错误');

        //验证会员是否已经领取过当前等级商品
        $product_status = $this->activityMemberDao->getActivityProductStatus($activity_data['activity_id'],$activity_data['grade_id'],$member_data['unique_code']);

        if (empty($product_status)) throw new ApiException('您没有可以再次领取当前档次的权益了');

        try {
            DB::beginTransaction();

            //查询商品属性和itemid
            $product_data = $this->productDao->getProductInfo($activity_data['product_id']);

            $order_sn = $this->getSn();
            if ($product_data['nature']==4||$product_data['nature']==1){

                $province=$params['province'];
                $city=$params['city'];
                $county=$params['county'];
                $street=empty($params['street'])?0:$params['street'];
                $remark=$params['remark'];
                $shouhuo_name=$params['shouhuo_name'];
                $address=$params['address'];

                //检测收货信息
                if (empty($city)||empty($province)||empty($county)||empty($mobile)||empty($shouhuo_name)||empty($address)) throw new ApiException('请完整填写收货信息');

                $api_order_data = [
                    'isvirtual' => $product_data['nature'],
                    'orderId' => $order_sn,
                    'sendcms' => 1,
                    'products' => [],
                    'shouhuo_phone' => $mobile,
                    'shouhuo_name' => $shouhuo_name,
                    'provinceId' => $province,
                    'cityId' => $city,
                    'countyId' => $county,
                    'townId' => $street,
                    'shouhuo_addr' => $address,
                    'addr_type' => 1,
                    'note' => $remark
                ];

                $product_data1 = [];
                $order_product_data=[[
                    'itemid'=>$product_data['itemid'],
                    'number'=>1
                    ]
                ];

                foreach ($order_product_data as $key=>$val)
                {
                    $product_data1[] = [
                        'itemId' => $val['itemid'],
                        'number' => $val['number'],
                    ];
                }

                $api_order_data['products'] = $product_data1;

                $api_order = $this->jdCreateOrder($api_order_data);
            }elseif ($product_data['nature']==3||$product_data['nature']==2){//虚拟订单

                $api_order_data = [
                    'isvirtual' => $product_data['nature'],
                    'orderId' => $order_sn,
                    'sendcms' => 1,
                    'products' => [],
                    'shouhuo_phone' => $mobile,
                    'addr_type' => 1,
                ];

                $product_data1 = [];
                $order_product_data=[[
                    'itemid'=>$product_data['itemid'],
                    'number'=>1
                ]
                ];

                foreach ($order_product_data as $key=>$val)
                {
                    $product_data1[] = [
                        'itemId' => $val['itemid'],
                        'number' => $val['number'],
                    ];
                }

                $api_order_data['products'] = $product_data1;

                $api_order = $this->jdCreateOrder($api_order_data);
                $remark='';
            }else{
                $api_order['order_sn']=0;
                $remark='';
            }

            $order_add1=[
                'activity_id'=>$activity_data['activity_id'],
                'type'=>1,
                'member_id'=>$member_data['member_id'],
                'sn'=>$order_sn,
                'pay_type'=>1,
                'nature'=>$product_data['nature'],
                'status'=>1,
                'pay_status'=>1,
                'express_status'=>1,
                'created_at'=>time(),
                'mobile'=>$mobile,
                'remark'=>$remark
            ];
            $order_id = $this->orderDao->addData($order_add1);

            //添加商品
            $order_product_add=[
                'order_id'=>$order_id,
                'product_id'=>$activity_data['product_id'],
                'product_name'=>$product_data['name'],
                'settlement_price'=>$product_data['settlement_price'],
                'number'=>1,
                'created_at'=>time(),
            ];

            $this->orderProductDao->addData($order_product_add);

            //添加供应商订单关联数据
            $order_supplier_data=[
                'order_id'=>$order_id,
                'supplier_id'=>$product_data['supplier_id'],
                'api_sn'=>$api_order['order_sn'],
                'created_at'=>time(),
                ];
            $this->orderSupplierDao->addData($order_supplier_data);

            $params['order_id']=$order_id;

            if ($product_data['nature']==1||$product_data['nature']==4||$product_data['nature']==10){
                //添加收货地址信息
                $this->addOrderReceive($params);
            }

            //添加订单日志
            $order_log=[
                'order_id'=>$order_id,
                'content'=>'客户提交订单',
                'type'=>1,
                'member_id'=>$member_data['member_id'],
                'admin_user_id'=>0,
                'created_at'=>time(),
            ];
            $this->orderLogDao->addData($order_log);

            //京东订单自动确认
            if ($product_data['nature']==4){
                $order_data['order_sn']=$api_order['order_sn'];
                $this->confrimJdOrder($order_data);

                //记录审核订单
                $order_log1=[
                    'order_id'=>$order_id,
                    'content'=>'系统自动推送运营系统',
                    'type'=>5,
                    'member_id'=>$member_data['member_id'],
                    'admin_user_id'=>0,
                    'created_at'=>time(),
                ];
                $this->orderLogDao->addData($order_log1);
            }

            //记录领取信息
            $activity_member_api_log=[
                'activity_id'=>$activity_data['activity_id'],
                'grade_id'=>$activity_data['grade_id'],
                'unique_code'=>$member_data['unique_code'],
                'order_id'=>$order_id,
            ];

            $this->addMemberReceive($activity_data['activity_id'],$activity_member_api_log,$activity_data['product_id']);

            DB::commit();

            $activity_data11 = $this->activityDao->getOndByCode($activity_data['activity_id']);

            if ($activity_data11['member_source']==1){
                //查询会员还能领多少次
                $receive_num = $this->activityMemberDao->getReceiveNum($activity_data['grade_id'],$activity_data['activity_id'],$member_data['unique_code']);
            }else{
                $receive_num=0;
            }
            return ['order_sn'=>$order_sn,'receive_num'=>$receive_num];
        } catch (ApiException $e) {

            DB::rollBack();

            throw new ApiException($e->getMessage());
        }
    }

    /**
     * 添加收货地址信息
     */
    public function addOrderReceive($addOrderReceive){
        $area_ids = $addOrderReceive['province'].','.$addOrderReceive['city'].','.$addOrderReceive['county'].','.$addOrderReceive['street'];

        $area_str = $this->getVopAreaStr($area_ids);

        $area_str1=$area_str[$addOrderReceive['province']].'-'.$area_str[$addOrderReceive['city']].'-'.$area_str[$addOrderReceive['county']].'-';

       if (!empty($addOrderReceive['street'])){
           $area_str1=$area_str1.$area_str[$addOrderReceive['street']];
        }

        $order_receive_data['order_id']=$addOrderReceive['order_id'];
        $order_receive_data['consignee_name']=$addOrderReceive['shouhuo_name'];
        $order_receive_data['consignee_mobile']=$addOrderReceive['mobile'];
        $order_receive_data['province']=$addOrderReceive['province'];
        $order_receive_data['city']=$addOrderReceive['city'];
        $order_receive_data['county']=$addOrderReceive['county'];
        $order_receive_data['street']=$addOrderReceive['street'];
        $order_receive_data['address']=$addOrderReceive['address'];
        $order_receive_data['address_info']=$area_str1.'-'.$addOrderReceive['address'];
        $order_receive_data['created_at']=time();

        $res = $this->orderAddressDao->addData($order_receive_data);

        if (!$res) throw new ApiException('新增订单地址失败');

    }

    /**
     * 获取京东地址详情
     *
     */
    public function getVopAreaStr($area_ids){
        //查询地址信息
        $api_token = Redis::get(config('jwt.REDIS_DATEBASE_PREFIX').':BLH:API:BLH_API_TOKEN');
        $api_token_data = json_decode($api_token,true);

        //2.请求参数
        $post_data=[
            'token'=>$api_token_data['token'],
            'area_ids'=>$area_ids
        ];

        $result =post_curl_func(env('VOP_PROVINCE_URL'),JsonHelper::encode($post_data));

        if($result['errCode']!='0000') throw new ApiException('获取京东地址详情失败');
        $result_data = $result['data'];

        $area_id_ary = explode(',',$area_ids);
        $area_data = [];
        foreach ($area_id_ary as $k=>$v)
        {
            if($v) $area_data[$v] = $result_data[$v];
        }

        return $area_data;
    }


    /**
     * 生成订单号
     */
    public function getSn(){
        $seed = array(0,1,2,3,4,5,6,7,8,9);
        $str = '';
        for($i=0;$i<8;$i++) {
            $rand = rand(0,count($seed)-1);
            $temp = $seed[$rand];
            $str .= $temp;
            unset($seed[$rand]);
            $seed = array_values($seed);
        }
        return $str;
    }

    /**
     * 京东订单预暂库存
     */
    public function jdCreateOrder($order_data){

        $api_token = Redis::get(config('jwt.REDIS_DATEBASE_PREFIX').':BLH:API:BLH_API_TOKEN');
        $api_token_data = json_decode($api_token,true);

        //2.请求参数
        $order_data['token'] = $api_token_data['token'];

        $result =post_curl_func(config('vop_order_url'),JsonHelper::encode($order_data));

        //添加请求日志（文件）
        $log_data=[
            'title'=>'创建订单',
            'data'=>json_encode($order_data),
            'reData'=>json_encode($result)
        ];
        $this->addErpLog($log_data);

        if($result['errCode']!='0000') throw new ApiException('下单失败');


        return $result['data'];
    }

    /**
     * 记录会员的领取信息
     * @param $member_source 1白名单，2接口
     * @param $data
     */
    public function addMemberReceive($activity_id,$data,$product_id){

        $activity_data =  $this->activityDao->getOndByCode($activity_id);
        //接口
        if ($activity_data['member_source']==2){
            $data=[
                'activity_id'=>$data['activity_id'],
                'grade_id'=>$data['grade_id'],
                'status'=>1,
                'dot_id'=>0,
                'product_id'=>$product_id,
                'receive_time'=>time(),
                'order_id'=>$data['order_id'],
                'unique_code'=>$data['unique_code']
            ];
            $this->activityMemberDao->addData($data);
            //$this->activityMemberApi->addData($data);
        }

        //导入
        if ($activity_data['member_source']==1){

            $this->activityMemberDao->updateReceiveStatus($data['activity_id'],$data['grade_id'],$data['unique_code'],$data['order_id'],$product_id);
        }
    }

    /**
     * 代客下单确认订单
     *
     */
    public function dotConfirmOrderInfo($params){

        $activity_code=$params['activity_code'];

        //获取需要下单的信息
        $activity_code = Redis::get(config('jwt.REDIS_DATEBASE_PREFIX').':ACTIVITY:INFO:'.$activity_code);
        $activity_data = json_decode($activity_code,true);

        if (empty($activity_data)) throw new ApiException('活动信息不存在');

       return $this->activityProductDao->getProductActivitById($activity_data['activity_id'],$activity_data['product_id']);
    }


    /**
     * 代客下单提交订单
     */
    public function dotCreateOrder($params){
        $type=$params['type'];
        $activity_code=$params['activity_code'];
        //2线下
        if ($type==2) {
            $data = $this->dotCreateOrderlower($params);
        }

        //1线上
        if ($type==1) {
            $data = $this->dotCreateOrderTop($params);
        }

        //下单完成删除二维码缓存的信息
        Redis::del(config('jwt.REDIS_DATEBASE_PREFIX').':ACTIVITY:INFO:'.$activity_code);

        return $data;
    }

    /**
     * 代客下单提交订单（线下）
     */
    public function dotCreateOrderlower($params){

        $activity_code=$params['activity_code'];
        $token=$params['member_token'];

        //获取需要下单的信息
        $activity_code = Redis::get(config('jwt.REDIS_DATEBASE_PREFIX').':ACTIVITY:INFO:'.$activity_code);
        $activity_data = json_decode($activity_code,true);
        if (empty($activity_data)) throw new ApiException('活动信息不存在');

        $dotStockData =  $this->activityDotStockDao->getDotStock($activity_data['activity_id'],$activity_data['dot_id'],$activity_data['region_id'],$activity_data['product_id']);


        //获取会员的登录信息
        $member_data = Redis::get(config('jwt.REDIS_DATEBASE_PREFIX').':MEMBER:LOGIN:'.$token);
        $member_data = json_decode($member_data,true);
        if (empty($member_data)) throw new ApiException('会员信息查询失败');

        $order_sn = $this->getSn();
        try {
            DB::beginTransaction();
            //查询商品属性
            $product_data = $this->productDao->getProductById($activity_data['product_id']);

            $order_add=[
                'activity_id'=>$activity_data['activity_id'],
                'type'=>2,
                'member_id'=>$member_data['member_id'],
                'sn'=>$order_sn,
                'pay_type'=>1,
                'nature'=>$product_data['nature'],
                'status'=>1,
                'pay_status'=>1,
                'express_status'=>1,
                'dot_id'=>$activity_data['dot_id'],
                'created_at'=>time(),
                'mobile'=>'',
            ];
            $order_id = $this->orderDao->addData($order_add);

            //添加商品
            $order_product_add=[
                'order_id'=>$order_id,
                'product_id'=>$activity_data['product_id'],
                'product_name'=>$product_data['name'],
                'settlement_price'=>$product_data['settlement_price'],
                'number'=>1,
                'created_at'=>time(),
            ];

            $this->orderProductDao->addData($order_product_add);

            //扣减库存
            $this->activityDotStockDao->decrementStock($activity_data['activity_id'],$activity_data['dot_id'],$activity_data['product_id']);

            //新增已兑换库存
            $this->activityDotStockDao->incrementStock($activity_data['activity_id'],$activity_data['dot_id'],$activity_data['product_id']);

            //记录订单日志
            $order_log=[
                'order_id'=>$order_id,
                'content'=>'客户提交订单',
                'type'=>1,
                'member_id'=>$member_data['member_id'],
                'admin_user_id'=>0,
                'created_at'=>time(),
            ];
            $this->orderLogDao->addData($order_log);

            //记录领取信息
            $activity_member_api_log=[
                'activity_id'=>$activity_data['activity_id'],
                'dot_id'=>$activity_data['dot_id'],
                'region_id'=>$activity_data['region_id'],
                'grade_id'=>$activity_data['grade_id'],
                'unique_code'=>$member_data['unique_code'],
                'order_id'=>$order_id,
            ];

            $this->addMemberReceive($activity_data['activity_id'],$activity_member_api_log,$activity_data['product_id']);

            //添加库存消耗日志
            $activityStockLogdata=[
                'activity_dot_stock_id'=>$dotStockData['id'],
                'product_id'=>$activity_data['product_id'],
                'dot_id'=>$activity_data['dot_id'],
                'activity_id'=>$activity_data['activity_id'],
                'type'=>4,
                'order_id'=>$order_id,
                'created_at'=>time()
            ];
            $this->activityDotStockLogDao->addData($activityStockLogdata);

            //库存变动记录
            $activityStockLogData=[
                'type'=>8,
                'product_id'=>$activity_data['product_id'],
                'dot_id'=>$activity_data['dot_id'],
                'activity_id'=>$activity_data['activity_id'],
                "order_id"=>$order_id,
                'is_comfirmation'=>1,
                "num"=>1,
                'created_at'=>time()
            ];
            var_dump($activityStockLogData);
            $this->activityStockLogDao->addData($activityStockLogData);

            //下单完成删除二维码缓存的信息
            //Redis::del(config('jwt.REDIS_DATEBASE_PREFIX').':ACTIVITY:INFO:'.$activity_code);

            DB::commit();

            return ['order_sn'=>$order_sn];
        } catch (ApiException $e) {

            DB::rollBack();

            throw new ApiException($e->getMessage());
        }

    }


    /**
     * 代客下单提交订单（线上）
     */
    public function dotCreateOrderTop($params){

        $token=$params['member_token'];
        $mobile=$params['mobile'];
        $activity_code=$params['activity_code'];

        //获取需要下单的信息
        $activity_code = Redis::get(config('jwt.REDIS_DATEBASE_PREFIX').':ACTIVITY:INFO:'.$activity_code);
        $activity_data = json_decode($activity_code,true);
        if (empty($activity_data)) throw new ApiException('活动信息不存在');
        $activity_data_res = $this->activityDao->getOndByCode($activity_data['activity_id']);
        //$grade_id=;

        $gradedata=$this->gradeDao->getById($activity_data['grade_id']);
        if ($activity_data_res['activity_moeny']-$activity_data_res['use_activity_moeny']<$gradedata['grade_money']){
            throw new ApiException('当前活动的预算已经消耗完，请您的客户经理联系分行活动管理员处理。');
        }

        //获取会员的登录信息
        $member_data = Redis::get(config('jwt.REDIS_DATEBASE_PREFIX').':MEMBER:LOGIN:'.$token);
        $member_data = json_decode($member_data,true);
        if (empty($member_data)) throw new ApiException('会员信息查询失败');

        check::checkMobile($mobile,'手机号格式错误');

        try {
            DB::beginTransaction();

            //查询商品属性和itemid
            $product_data = $this->productDao->getProductInfo($activity_data['product_id']);

            $order_sn = $this->getSn();
            if ($product_data['nature']==4||$product_data['nature']==1){
                $province=$params['province'];
                $city=$params['city'];
                $county=$params['county'];
                $street=$params['street'];
                $remark=$params['remark'];
                $shouhuo_name=$params['shouhuo_name'];
                $address=$params['address'];


                //检测收货信息
                if (empty($city)||empty($province)||empty($county)||empty($mobile)||empty($shouhuo_name)||empty($address)) throw new ApiException('请完整填写收货信息');

                $api_order_data = [
                    'isvirtual' => $product_data['nature'],
                    'orderId' => $order_sn,
                    'sendcms' => 1,
                    'products' => [],
                    'shouhuo_phone' => $mobile,
                    'shouhuo_name' => $shouhuo_name,
                    'provinceId' => $province,
                    'cityId' => $city,
                    'countyId' => $county,
                    'townId' => $street,
                    'shouhuo_addr' => $address,
                    'addr_type' => 1,
                    'note' => $remark
                ];

                $product_data1 = [];
                $order_product_data=[[
                    'itemid'=>$product_data['itemid'],
                    'number'=>1
                ]
                ];

                foreach ($order_product_data as $key=>$val)
                {

                    $product_data1[] = [
                        'itemId' => $val['itemid'],
                        'number' => $val['number'],
                    ];
                }

                $api_order_data['products'] = $product_data1;

                $api_order = $this->jdCreateOrder($api_order_data);

            }elseif ($product_data['nature']==2||$product_data['nature']==3){
                $api_order_data = [
                    'isvirtual' => $product_data['nature'],
                    'orderId' => $order_sn,
                    'sendcms' => 1,
                    'products' => [],
                    'shouhuo_phone' => $mobile,
                    'addr_type' => 1,
                ];

                $product_data1 = [];
                $order_product_data=[[
                    'itemid'=>$product_data['itemid'],
                    'number'=>1
                ]
                ];

                foreach ($order_product_data as $key=>$val)
                {

                    $product_data1[] = [
                        'itemId' => $val['itemid'],
                        'number' => $val['number'],
                    ];
                }

                $api_order_data['products'] = $product_data1;

                $api_order = $this->jdCreateOrder($api_order_data);
                $remark='';
            }else{
                $api_order['order_sn']=0;
                $remark='';
            }

            $order_add=[
                'activity_id'=>$activity_data['activity_id'],
                'type'=>1,
                'member_id'=>$member_data['member_id'],
                'sn'=>$order_sn,
                'pay_type'=>1,
                'nature'=>$product_data['nature'],
                'status'=>1,
                'pay_status'=>1,
                'express_status'=>1,
                'created_at'=>time(),
                'mobile'=>$mobile,
                'remark'=>$remark
            ];
            $order_id = $this->orderDao->addData($order_add);

            //添加商品
            $order_product_add=[
                'order_id'=>$order_id,
                'product_id'=>$activity_data['product_id'],
                'product_name'=>$product_data['name'],
                'settlement_price'=>$product_data['settlement_price'],
                'number'=>1,
                'created_at'=>time(),
            ];

            $this->orderProductDao->addData($order_product_add);

            //添加供应商订单关联数据
            $order_supplier_data=[
                'order_id'=>$order_id,
                'supplier_id'=>$product_data['supplier_id'],
                'api_sn'=>$api_order['order_sn'],
                'created_at'=>time(),
            ];
            $this->orderSupplierDao->addData($order_supplier_data);

            $params['order_id']=$order_id;
            if ($product_data['nature']==1||$product_data['nature']==4||$product_data['nature']==10){

                //添加收货地址信息
                $this->addOrderReceive($params);

            }

            //添加订单日志
            $order_log=[
                'order_id'=>$order_id,
                'content'=>'客户提交订单',
                'type'=>1,
                'member_id'=>$member_data['member_id'],
                'admin_user_id'=>0,
                'created_at'=>time(),
            ];
            $this->orderLogDao->addData($order_log);

            //记录领取信息
            $activity_member_api_log=[
                'activity_id'=>$activity_data['activity_id'],
                'grade_id'=>$activity_data['grade_id'],
                'unique_code'=>$member_data['unique_code'],
                'order_id'=>$order_id,
            ];
            $this->addMemberReceive($activity_data['activity_id'],$activity_member_api_log,$activity_data['product_id']);

            //创建订单成功京东订单自动审核
            if ($product_data['nature']==4){
                $order_data['order_sn']=$api_order['order_sn'];
                $this->confrimJdOrder($order_data);

                $order_log1=[
                    'order_id'=>$order_id,
                    'content'=>'系统自动推送运营系统',
                    'type'=>5,
                    'member_id'=>$member_data['member_id'],
                    'admin_user_id'=>0,
                    'created_at'=>time(),
                ];
                $this->orderLogDao->addData($order_log1);

            }
            //Redis::del(config('jwt.REDIS_DATEBASE_PREFIX').':ACTIVITY:INFO:'.$activity_code);
            $this->activityDao->updateMoeny($activity_data['activity_id'],$activity_data_res['use_activity_moeny']+$gradedata['grade_money']);

            DB::commit();
            return ['order_sn'=>$order_sn];
        } catch (ApiException $e) {

            DB::rollBack();

            throw new ApiException($e->getMessage());
        }
    }

    /**
     * 京东订单确认订单
     * @param $order_data
     * @return mixed
     * @throws ApiException
     */
    public function confrimJdOrder($order_data){

        $api_token = Redis::get(config('jwt.REDIS_DATEBASE_PREFIX').':BLH:API:BLH_API_TOKEN');
        $api_token_data = json_decode($api_token,true);

        //2.请求参数
        $order_data['token'] = $api_token_data['token'];

        $result =post_curl_func(env('VOP_CONFRIM_JD_ORDER_URL'),JsonHelper::encode($order_data));

        //添加请求日志（文件）
        $log_data=[
            'title'=>'确认订单',
            'data'=>json_encode($order_data),
            'reData'=>json_encode($result)
        ];
        $this->addErpLog($log_data);

        if($result['errCode']!='0000') throw new ApiException('京东订单确认失败');

        return $result['data'];
    }

    /**
     * 添加接口日志
     */
    private function addErpLog($data){
        $text = '--------'.date("Y-m-d H:i:s")."  ".$data['title']."-----------";
        $text .= "\r\n 发送的数据:".$data['data'];
        $text .= "\r\n 返回的数据:".$data['reData'];
        $text .= "\r\n----------------------------------------------------------------------\r\n\r\n";
        $path = '/zhlyapi/log/erp/'.date("Y")."/".date("m")."/";

        if(!is_dir($path)) {
            // 创建目录
            if(!mkdir($path,0777,true)){
                return false;
            }
        }
        $filename = $path.date("d").".log";
        $text = iconv("UTF-8","UTF-8//IGNORE",$text);

        $handler = fopen($filename ,'a');
        @fwrite($handler, $text);
        @fclose($handler);
    }

}