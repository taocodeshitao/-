<?php

namespace App\Model\Service;

use App\Common\Cache;
use App\Common\StatusEnum;
use App\Exception\ApiException;
use App\Listener\Event;
use App\Model\Dao\OrderDao;
use App\Model\Dao\OrderRefundDao;
use App\Model\Dao\PaymentDao;
use App\Utils\Check;
use EasyWeChat\Factory;
use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Bean\Annotation\Mapping\Inject;
use Swoft\Bean\BeanFactory;
use Swoft\Db\DB;
use Swoft\Redis\Redis;
use Swoft\Stdlib\Helper\ArrayHelper;
use Swoft\Stdlib\Helper\JsonHelper;


/**
 * 微信逻辑
 * Class WechatService
 * @package App\Model\Service
 * @Bean(scope=Bean::PROTOTYPE)
 */
class WechatService
{


    /**
     * @Inject()
     * @var PaymentDao
     */
    private $paymentDao;


    /**
     * 生成微信统一支付订单
     * @param $out_trade_no
     * @param int $money
     * @param int $pay_sign
     * @param int $user_id
     * @return array|\EasyWeChat\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     */
    public  function unifyOrder($out_trade_no,int $money,int $pay_sign,int $user_id)
    {
        //获取微信配置信息
        $app = Factory::payment(config('wechat'));

        //获取交易类型
        $trade_type = $this->paymentDao->getTradeType($pay_sign);

        //获取openid  - 当pay_sign=2 是jsapi支付
        $open_id = null;

        if($pay_sign==2)  $open_id = Redis::get(sprintf(Cache::USER_OPENID,$user_id));

        $result = $app->order->unify(
            [
                'body' => '测试福卡商品',
                'out_trade_no' => $out_trade_no,
                'total_fee' => $money,
                'openid' =>$open_id,
                'spbill_create_ip'=>config('host'),
                'trade_type' => $trade_type,
            ]
        );

        return $result;
    }


    /**
     * 获取open_id
     * @param int $user_id
     * @param string $auth_code
     * @return bool
     */
    public  function getOpenId(int $user_id,string  $auth_code)
    {

        //调用接口获取openid
        $openid_url = config('wechat.open_id_url') . "appid=".config('wechat.app_id')."&secret=".config('wechat.app_secret') . "&code=" . $auth_code . "&grant_type=authorization_code";

        $data = get_curl($openid_url);

        $open_id = $data['openid'];

        if(!empty($open_id))
        {
            //存储openid
            $key = sprintf(Cache::USER_OPENID,$user_id);

            Redis::setex($key,Cache::OPENID_TTL,$open_id);
        }

        return true;
    }


    /**
     * 获取jssdk
     * @param string $prepay_id
     * @return array
     */
    public  function getJssdk(string $prepay_id)
    {
        //获取微信配置信息
        $app = Factory::payment(config('wechat'));

        $result  =$app->jssdk->sdkConfig($prepay_id);

        $result['timeStamp'] = $result['timestamp'];

        unset($result['timestamp']);

        return $result;
    }

    /**
     * 获取支付结果
     * @param int $payment_id
     * @return array
     * @throws ApiException
     */
    public  function getPayResult(int $payment_id)
    {
        Check::checkBoolean($payment_id,'系统繁忙');

        /** @var PaymentDao $paymentDao */
        $paymentDao = \Swoft::getBean(PaymentDao::class);

        $payment_info = $paymentDao->findById($payment_id);

        if(empty($payment_info)) throw new ApiException('系统繁忙');

        if($payment_info['state']==0) throw new ApiException('暂未支付');

        return ['pay_status'=>$payment_info['state']];
    }

    /**
     * 处理微信支付回调信息
     * @param array $message
     * @return string
     */
    public  function handlePayCallBack(array  $message)
    {
        try {

            DB::beginTransaction();
            //验证返回信息
            $this->_verifyNoticeStatus($message);

            //查询并验证订单
            $order_sn = $message['out_trade_no'];

            $order_list = $this->_verifyOrder($order_sn);

            //验证订单支付金额
            $payment_id = $this->_verifyPrice($order_sn, $message['total_fee']);

            //处理订单信息
            $this->handleOrder($order_list, $payment_id, $message);

            DB::commit();

            return $this->callBackResponseData('SUCCESS','OK');

        } catch (ApiException $e) {

            DB::rollBack();

            $response = $this->callBackResponseData('FAIL',$e->getMessage());

            if($e->getCode()==StatusEnum::CALL_BACK_CODE)
            {
                $response = $this->callBackResponseData('SUCCESS','OK');
            }
            return $response;
        }
    }


    /**
     * 处理微信退款回调信息
     * @param array $message
     * @return string
     */
    public  function handleRefundCallBack(array  $message)
    {
        try {

            DB::beginTransaction();
            //验证返回信息
            $this->_verifyNoticeStatus($message);

            //查询并验证订单
            $refund_order_sn = $message['out_refund_no'];

            $refund_order_info = $this->_verifyRefundOrder($refund_order_sn);

            //验证订单支付金额
            $this->_verifyRefundPrice($refund_order_info['payment_id'], $message['refund_fee']);

            //处理订单信息
            $this->handleRefundOrder($refund_order_info,$message);

            DB::commit();

            return $this->callBackResponseData('SUCCESS','OK');

        } catch (ApiException $e) {

            DB::rollBack();

            $response = $this->callBackResponseData('FAIL',$e->getMessage());

            if($e->getCode()==StatusEnum::CALL_BACK_CODE)
            {
                $response = $this->callBackResponseData('SUCCESS','OK');
            }
            return $response;
        }
    }


    /**
     * 验证通知状态
     * @param array $message
     * @return bool
     * @throws ApiException
     */
    public function _verifyNoticeStatus(array $message):bool
    {
        if ($message['return_code'] === 'SUCCESS' && $message['result_code'] === 'SUCCESS')
        {
            // 用户是否支付或退款成功
            if (ArrayHelper::getValue($message, 'result_code') === 'SUCCESS'){

                return true;

            }elseif(ArrayHelper::getValue($message, 'result_code') === 'FAIL'){
                //支付或退款失败
                throw new ApiException('支付失败',StatusEnum::CALL_BACK_CODE);
            }
        } else {
            //通知信失败
            throw new ApiException('通信失败');
        }
    }


    /**
     * 验证订单信息
     * @param string $order_sn
     * @return array
     * @throws ApiException
     */
    public function _verifyOrder(string  $order_sn)
    {

        /** @var OrderDao $orderDao */
        $orderDao = \Swoft::getBean(OrderDao::class);
        $order_list =$orderDao->findByMergeSn($order_sn);

        if(empty($order_list)) throw new ApiException('订单不存在',200);

        foreach ($order_list as $k=>$v)
        {
            if($v['state']!==0 && $v['is_pay']!==0) throw new ApiException('订单已支付',StatusEnum::CALL_BACK_CODE);
        }

        return $order_list;
    }

    /**
     * 验证退款订单信息
     * @param string $order_sn
     * @return array
     * @throws ApiException
     */
    public function _verifyRefundOrder(string  $order_sn)
    {
        /** @var OrderRefundDao $orderRefundDao */
        $orderRefundDao = \Swoft::getBean(OrderRefundDao::class);

        //验证退款订单
        $refund_order_info =$orderRefundDao->findBySn($order_sn);

        if(empty($refund_order_info)) throw new ApiException('订单不存在',200);

        return $refund_order_info;
    }


    /**
     * 验证支付金额
     * @param $order_sn
     * @param $total_fee
     * @return mixed
     * @throws ApiException
     */
    private  function _verifyPrice($order_sn,$total_fee)
    {
        $payment_info = $this->paymentDao->findBySn($order_sn);

        if(empty($payment_info) || $payment_info['state']==1) throw new ApiException('支付信息错误',StatusEnum::CALL_BACK_CODE);

        if($payment_info['total_fee']!=$total_fee) throw new ApiException('支付金额错误');

        return $payment_info['id'];
    }


    /**
     * 验证支付退款金额信息
     * @param $payment_id
     * @param $refund_total_fee
     * @return mixed
     * @throws ApiException
     */
    private  function _verifyRefundPrice($payment_id,$refund_total_fee)
    {
        //验证订单支付记录
        $payment_info = $this->paymentDao->findById($payment_id);

        if(empty($payment_info) || $payment_info['state']==1) throw new ApiException('订单已退款',StatusEnum::CALL_BACK_CODE);

        if($payment_info['total_fee']!=$refund_total_fee) throw new ApiException('退款金额错误');

        return true;
    }


    /**
     * 订单退款处理
     * @param $refund_order_info
     * @param $message
     * @return bool
     */
    private  function handleRefundOrder($refund_order_info,$message)
    {
        $type = 1;
        //全款退
        if($message['refund_fee']==$refund_order_info['total_price'])
        {
            $type = 2;

        }else{

            /** @var OrderRefundDao $orderRefundDao */
            $orderRefundDao = \Swoft::getBean(OrderRefundDao::class);

            //获取多次退款记录
            $refund_order_list = $orderRefundDao->getList($refund_order_info['order_id'],['payment_id']);

            $payment_id_list = ArrayHelper::getColumn($refund_order_list,'payment_id');

            //获取退款金额统计
            $total_refund_fee = ($this->paymentDao->getTotalFeeSum($payment_id_list))+$message['refund_fee'];

            //判断多次退款合计加上当前退款是否等于订单支付总金额
            if($total_refund_fee ==$refund_order_info['total_price']) $type =2;
        }

        //获取主订单信息
        /** @var OrderDao $orderDao */
        $orderDao = \Swoft::getBean(OrderDao::class);

        $order_info =$orderDao->findById($refund_order_info['order_id'],$refund_order_info['uid'],['id','version']);

        //更新支付记录
        \Swoft::trigger(Event::PAY_MENT_UPDATE,null,$refund_order_info['payment_id'],JsonHelper::encode($message));

        //更新订单信息
        \Swoft::trigger(Event::ORDER_REFUND_UPATE,null,$refund_order_info['order_id'],$order_info['version'],$type);

        //添加订单流水日志
        \Swoft::trigger(Event::ORDER_ADD_RECORD,null,$refund_order_info['order_id'],'微信退款：'.'¥'.$message['refund_fee']/100);

        return true;
    }

    /**
     * 更新订单信息
     * @param array $order_list
     * @param int $payment_id
     * @param array $message
     * @return bool
     */
    private  function handleOrder(array $order_list,int $payment_id,array $message)
    {
        //支付金额
        $total_fee = $message['total_fee'];

        //微信支付换算积分
        $pay_total_integral = (($total_fee)/100)*config('point_scale');

        //获取用户信息
        $user_id = $order_list[0]['uid'];

        //获取订单需要支付订单总积分
        $oder_total_integral = 0;
        foreach ($order_list as $k=>$v) $oder_total_integral += $v['total_integral'];

        //获取用户需要支付的积分
        $pay_residue_integral  = $oder_total_integral - $pay_total_integral;

        /** @var UserService $userService */
        $userService = BeanFactory::getBean(UserService::class);

        //更新订单状态和支付信息
        foreach ($order_list as $k=>&$v)
        {
            //比较订单积分与用户剩余积分
            if($v['total_integral']<=$pay_residue_integral)
            {
                //余额大于支付金额
                $v['integral'] =$v['total_integral'];
                $pay_residue_integral = $pay_residue_integral-$v['total_integral'];
            }else{
                //余额小于支付金额
                $v['integral'] =$pay_residue_integral;
                $v['price'] = (($v['total_integral']-$pay_residue_integral)/config('point_scale'))*100;
                $pay_residue_integral =0;
            }

            $balance = 0;

            //更新支付记录
            \Swoft::trigger(Event::PAY_MENT_UPDATE,null,$payment_id,JsonHelper::encode($message));

            //更新订单信息
            \Swoft::trigger(Event::ORDER_PAY_DIFFER,null,$v['id'],$v['version'],$v['price'],$v['integral'],$payment_id,$message['transaction_id']);

            //扣减用户积分信息
            if($v['integral']>0) $balance = $userService->updateUserIntegral($user_id,$v['integral'],1);

            //添加用户流水日志
            \Swoft::trigger(Event::USER_STREAM_ADD,null,$user_id,$v['integral'],$balance,3,'兑换消费',$v['sn']);

            //添加订单流水日志
            \Swoft::trigger(Event::ORDER_ADD_RECORD,null,$v['id'],'会员支付'.$v['integral'].'福豆、'.'¥'.$v['price']/100);
        }
        return true;
    }

    /**
     * 微信返回响应
     * @param string $code
     * @param string $msg
     * @return mixed
     */
    private  function callBackResponseData(string $code,string $msg)
    {
        $data['return_code'] = $code;

        $data['return_msg'] = $msg;

        return $data;
    }

}