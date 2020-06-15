<?php declare(strict_types=1);


namespace App\Listener\Card;


use App\Exception\ApiException;
use App\Listener\Event;
use App\Model\Dao\CardRecordDao;
use Swoft\Bean\Annotation\Mapping\Inject;
use Swoft\Event\Annotation\Mapping\Subscriber;
use Swoft\Event\EventInterface;
use Swoft\Event\EventSubscriberInterface;

/**
 * 福卡
 * Class CardSubscriber
 *
 * @Subscriber()
 */
class CardSubscriber implements EventSubscriberInterface
{

    /**
     * @Inject()
     * @var CardRecordDao
     */
    protected  $cardRecordDao;

    public static function getSubscribedEvents():array
    {

        return [
                   Event::CARD_REGISTER_ADD=>'handleRegisterEvent',

                   Event::CARD_EXCHANGE_ADD=>'handleExchangeEvent',
               ];
    }

    public  function handleExchangeEvent(EventInterface $event)
    {
        $data['uid'] = $event->getParam(0);
        $data['phone'] = $event->getParam(1);
        $data['card_sn'] =$event->getParam(2);
        $data['password'] = md5($event->getParam(3));
        $data['integral'] = $event->getParam(4);
        $data['money'] = $event->getParam(5);
        $data['type'] =$event->getParam(6);
        $data['stream_no'] ='C'.getOrderSn();
        $data['created_at'] = time();

        if(!$this->cardRecordDao->addData($data)) throw new ApiException('兑换失败');

    }


    public  function handleRegisterEvent(EventInterface $event):void
    {

        $data['uid'] = $event->getParam(0);
        $data['phone'] = $event->getParam(1);
        $data['card_sn'] =$event->getParam(2);
        $data['password'] =md5($event->getParam(3));
        $data['type'] =$event->getParam(4);
        $data['stream_no'] ='C'.getOrderSn();
        $data['created_at'] = time();

        if(!$this->cardRecordDao->addData($data)) throw new ApiException('注册失败');
    }


}