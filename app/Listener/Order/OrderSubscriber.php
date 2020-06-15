<?php declare(strict_types=1);


namespace App\Listener\Order;

use App\Exception\ApiException;
use App\Listener\Event;
use App\Model\Dao\OrderDao;
use App\Model\Dao\OrderLogDao;
use Swoft\Bean\Annotation\Mapping\Inject;
use Swoft\Event\Annotation\Mapping\Subscriber;
use Swoft\Event\EventInterface;
use Swoft\Event\EventSubscriberInterface;

/**
 * 订单
 * Class OrderSubscriber
 *
 * @Subscriber()
 */
class OrderSubscriber implements EventSubscriberInterface
{

    /**
     * @Inject()
     * @var OrderDao
     */
    protected  $orderDao;

    public static function getSubscribedEvents():array
    {
        return [
                   Event::ORDER_ADD_RECORD  =>'handleOrderAddRecord',

                   Event::VOP_ORDER_CREATE =>'handleVopOrderEvent',

                   Event::ORDER_PAY_INTEGRAL =>'handleOrderIntegralEvent',

                   Event::ORDER_PAY_DIFFER  =>'handleOrderDifferEvent',

                   Event::ORDER_REFUND_UPATE  =>'handleOrderRefundEvent'
               ];
    }

    //更新京东订单
    public  function handleVopOrderEvent(EventInterface $event)
    {

        $data['from_order_sn'] = $event->getParam(1);
        $data['updated_at']  = time();

        $result =  $this->orderDao->updateData(['id'=>$event->getParam(0)],$data);

        if($result===false) throw new ApiException('下单失败');

        return true;
    }

    //全部积分支付
    public  function handleOrderIntegralEvent(EventInterface $event)
    {
        $data['state'] = 1;
        $data['in'] =getOrderSn();
        $data['integral'] = $event->getParam(2);
        $data['total_integral'] = $event->getParam(2);
        $data['payment_id'] = $event->getParam(3);
        $data['payment_time'] = time();
        $data['payment_code'] = 'jifen';
        $data['payment_name'] = '积分支付';
        $data['updated_at'] = time();

        $result =  $this->orderDao->updateById($event->getParam(0),$event->getParam(1),$data);

        if($result===false) throw new ApiException('支付失败');

        return true;
    }

    //现金补差支付
    public  function handleOrderDifferEvent(EventInterface $event)
    {
        sgo(function (){

        });
        $data['is_pay'] = 1;
        $data['state'] = 1;
        $data['payment_time'] = time();
        $data['payment_code'] = 'wechat';
        $data['payment_name'] = '微信支付';
        $data['updated_at'] = time();
        $data['price'] = $event->getParam(2);
        $data['integral'] = $event->getParam(3);
        $data['payment_id'] = $event->getParam(4);
        $data['on'] = $event->getParam(5);

        $result =  $this->orderDao->updateById($event->getParam(0),$event->getParam(1),$data);

        if($result===false) throw new ApiException('支付失败');

        return true;
    }

    //更新退款订单信息
    public  function handleOrderRefundEvent(EventInterface $event)
    {
        $data['refund_state'] = $event->getParam(2);
        $data['updated_at']  = time();

        $result =  $this->orderDao->updateById($event->getParam(0),$event->getParam(1),$data);

        if($result===false) throw new ApiException('退款失败');

        return true;
    }


    //添加订单流水
    public  function handleOrderAddRecord(EventInterface $event)
    {

        $data['order_id'] = $event->getParam(0);
        $data['description'] = $event->getParam(1);
        $data['created_at']  = time();

        $orderLogDao = \Swoft::getBean(OrderLogDao::class);

        $result =  $orderLogDao->addData($data);

        if(!$result) throw new ApiException('下单失败');

        return true;
    }
}