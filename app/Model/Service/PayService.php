<?php
/**
 * description PayService.php
 * Created by PhpStorm.
 * User: zengqb
 * DateTime: 2020/1/10 14:58
 */

namespace App\Model\Service;
use App\Exception\ApiException;
use App\Listener\Event;
use App\Model\Dao\OrderDao;
use App\Model\Dao\PaymentDao;
use App\Utils\Check;
use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Bean\Annotation\Mapping\Inject;
use Swoft\Bean\BeanFactory;
use Swoft\Db\DB;

/**
 * 支付
 * Class PayService
 * @package App\Model\Service
 *
 * @Bean(scope=Bean::PROTOTYPE)
 */
class PayService
{

    /**
     * @Inject()
     * @var OrderDao
     */
    private  $orderDao;


    /**
     * 订单支付
     * @param int $user_id
     * @param int $user_integral
     * @param array $params
     * @return array
     * @throws ApiException
     */
    public  function pay(int $user_id,int $user_integral,array $params):array
    {

        $order_sn_list = explode(',',$params['order_list']);

        Check::checkBoolean($order_sn_list,'系统繁忙');

        $count = count($order_sn_list);

        //根据订单的数量拆分订单支付流程
        $type = $count==1 ? 1 : 2;

        //支付标识
        $pay_sign = $params['pay_sign'];

        switch ($type)
        {
            // 单个订单支付
            case 1:  $data = $this->singleOrderPay($user_id,$user_integral,$order_sn_list[0],$pay_sign);break;

            // 多个订单支付
            case 2:  $data = $this->combinedOrderPay($user_id,$user_integral,$order_sn_list,$pay_sign);break;

            default: throw new ApiException('系统繁忙');
        }

        return $data;
    }


    /**
     *
     * 单个订单支付
     * @param int $user_id
     * @param int $user_integral
     * @param string $order_sn
     * @param int $pay_sign 支付标志
     * @return array
     * @throws ApiException
     */
    private  function  singleOrderPay(int $user_id,int $user_integral,string  $order_sn,int $pay_sign)
    {
        $data =[];

        //获取订单信息
        $order_info = $this->orderDao->findBySn($order_sn,$user_id);

        //验证订单的信息
        $this->_verifyOrder($order_info);

        //验证订单是积分支付或是积分+现金
        if($order_info['total_integral']<=$user_integral)
        {
            //积分支付
            $this->payOrderByIntegral($user_id,$order_info);

            $data['type'] = 1;

        }else{
            //现在补差支付
            //重置业务单号(防止同批次生成多个订单,微信支付时单号重复)
            $merge_sn = getOrderSn();

            //更新订单业务单号
            $result = $this->orderDao->updateData(['id'=>$order_info['id']],['merge_sn'=>$merge_sn]);

            if(!$result) throw new ApiException('支付失败');

            $data['type'] = 2;

            $data['payData'] = $this->payOrderByMin($user_id,$merge_sn,$order_sn,$order_info['total_integral'],$user_integral,$pay_sign);
        }

        return $data;
    }


    /**
     * 合并订单支付
     * @param int $user_id
     * @param $user_integral
     * @param $order_sn_list
     * @param int $pay_sign
     * @return array
     * @throws ApiException
     */
    private  function  combinedOrderPay(int $user_id,$user_integral,$order_sn_list,int $pay_sign):array
    {

        $data =[];$pay_order_integral=0;$merge_sn ='';

        //获取订单信息
        $order_list = $this->orderDao->getListBySn($order_sn_list,$user_id);

        if(empty($order_list)) throw new ApiException('系统繁忙');

        //验证订单信息
        foreach ($order_list as $k=>$v)
        {
            $this->_verifyOrder($v);
            $pay_order_integral +=$v['total_integral'];
            $merge_sn = $v['merge_sn'];
        }

        //订单支付
        if($pay_order_integral<=$user_integral)
        {
            //积分支付
            foreach ($order_list as $k=>$v) $this->payOrderByIntegral($user_id,$v);
            $data['type'] = 1;
        }else{
            //补差支付
            $data['payData'] = $this->payOrderByMin($user_id,$merge_sn,$merge_sn,$pay_order_integral,$user_integral,$pay_sign);
            $data['type'] = 2;
        }

        return $data;
    }


    /**
     * 全积分支付
     * @param $user_id
     * @param $order_info
     * @return bool
     * @throws ApiException
     */
    private  function payOrderByIntegral($user_id,$order_info):bool
    {
        try {

            DB::beginTransaction();

            $total_price = $order_info['total_integral'];

            //更新用户积分信息
            /** @var UserService $userService */
            $userService = BeanFactory::getBean(UserService::class);
            $balance = $userService->updateUserIntegral($user_id,$total_price,1);

            //添加支付记录
            $payment_id = $this->addPayRecord('jifen','积分支付',$order_info['sn'],$user_id,$order_info['total_integral'],1);

            //更新订单信息
            \Swoft::trigger(Event::ORDER_PAY_INTEGRAL,null,$order_info['id'],$order_info['version'],$order_info['total_integral'],$payment_id);

            //添加用户流水日志
            \Swoft::trigger(Event::USER_STREAM_ADD,null,$user_id,$total_price,$balance,3,'兑换消耗',$order_info['sn']);

            //添加订单流水日志
            \Swoft::trigger(Event::ORDER_ADD_RECORD,null,$order_info['id'],'会员支付'.$order_info['total_integral'].'福豆');

            DB::commit();

            return true;

        } catch (\Exception $e) {

            DB::rollBack();

            throw new ApiException($e->getMessage());
        }
    }

    /**
     * 补差支付
     * @param int $user_id 用户id
     * @param string $pay_order_sn 业务单号
     * @param string $order_sn 订单单号(多个订单单号是合并的业务单号)
     * @param int $pay_order_integral 订单支付总积分
     * @param int $user_integral 用户积分
     * @param int $pay_sign 支付标识
     * @return array|\EasyWeChat\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     * @throws ApiException
     */
    private  function payOrderByMin(int $user_id,string  $pay_order_sn,string $order_sn,int $pay_order_integral,int $user_integral,int $pay_sign)
    {
        //获取补差金额
        $balance = $pay_order_integral - $user_integral;

        $min_money = (($balance / config('point_scale')))*100;

        /** @var WechatService $wechatService */
        $wechatService = BeanFactory::getBean(WechatService::class);

        //微信支付生成统一支付订单
        $result = $wechatService->unifyOrder($pay_order_sn,$min_money,$pay_sign,$user_id);


        if($result['return_code']!=='SUCCESS' || $result['result_code']!=='SUCCESS') throw new ApiException('支付失败');

        //添加支付记录
        $payment_id = $this->addPayRecord('wechat','微信支付',$order_sn,$user_id,$min_money,0,$pay_order_sn);

        $data = [];

        //微信H5支付
        if($pay_sign==1)  $data['mweb_url'] = $result['mweb_url'];

        if($pay_sign==2)  $data = $wechatService->getJssdk($result['prepay_id']);

        return ['config'=>$data,'payment_id'=>$payment_id];
    }

    /**
     * 验证订单
     * @param $order_list
     * @throws ApiException
     */
    private  function _verifyOrder($order_list):void
    {
        Check::checkBoolean($order_list,'系统繁忙');

        if($order_list['state']!=0) throw new ApiException('订单已支付,请勿重复操作');
    }


    /**
     * 添加支付记录
     * @param string $code 支付编码
     * @param string $name 支付名称
     * @param string $pay_order_sn
     * @param int $total_fee
     * @param int $user_id
     * @return int
     * @throws ApiException
     */
    private  function addPayRecord(string $code,string $name,string $order_sn,int $user_id,int $total_fee,int $state,string $pay_order_sn=null)
    {
        $data['code'] = $code;
        $data['name'] = $name;
        $data['state'] =$state;
        $data['uid'] =$user_id;
        $data['merge_sn'] =$pay_order_sn;
        $data['sn'] =$order_sn;
        $data['total_fee'] =$total_fee;
        $data['created_at'] =time();

        /** @var PaymentDao $paymentDao */
        $paymentDao = \Swoft::getBean(PaymentDao::class);

        $payment_id =$paymentDao->addData($data);

        if(empty($payment_id)) throw new ApiException('系统繁忙');

        return intval($payment_id);
    }

}