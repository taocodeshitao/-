<?php declare(strict_types=1);


namespace App\Listener\Order;

use App\Exception\ApiException;
use App\Listener\Event;
use App\Model\Dao\PaymentDao;
use Swoft\Bean\Annotation\Mapping\Inject;
use Swoft\Event\Annotation\Mapping\Subscriber;
use Swoft\Event\EventInterface;
use Swoft\Event\EventSubscriberInterface;

/**
 * 支付
 * Class PaySubscriber
 *
 * @Subscriber()
 */
class PaySubscriber implements EventSubscriberInterface
{

    /**
     * @Inject()
     * @var PaymentDao
     */
    protected  $paymentDao;

    public static function getSubscribedEvents():array
    {
        return [
                   Event::PAY_MENT_CREATE =>'handlePaymentAdd',

                   Event::PAY_MENT_UPDATE =>'handlePaymentUpdate',
               ];
    }

    //添加微信支付记录
    public  function handlePaymentAdd(EventInterface $event)
    {
        $data['code'] = $event->getParam(0);
        $data['name'] = $event->getParam(1);
        $data['merge_sn'] =$event->getParam(2);
        $data['total_fee'] =$event->getParam(3);
        $data['state'] =0;
        $data['created_at'] =time();

        $payment_id =$this->paymentDao->addData($data);

        if(empty($payment_id)) throw new ApiException('下单失败');

        return true;

    }

    //更新微信支付记录
    public  function handlePaymentUpdate(EventInterface $event)
    {
        $data['state'] = 1;
        $data['info'] = $event->getParam(1);
        $data['updated_at'] = time();

        $result =  $this->paymentDao->updateById($event->getParam(0),$data);

        if($result===false) throw new ApiException('系统繁忙',100);
    }

}