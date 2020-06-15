<?php declare(strict_types=1);


namespace App\Listener\User;


use App\Exception\ApiException;
use App\Listener\Event;
use App\Model\Dao\UserStreamDao;
use Swoft\Bean\Annotation\Mapping\Inject;
use Swoft\Event\Annotation\Mapping\Subscriber;
use Swoft\Event\EventInterface;
use Swoft\Event\EventSubscriberInterface;

/**
 * 流水
 * Class StreamSubscriber
 *
 * @Subscriber()
 */
class StreamSubscriber implements EventSubscriberInterface
{

    /**
     * @Inject()
     * @var UserStreamDao
     */
    protected  $streamDao;

    public static function getSubscribedEvents():array
    {

        return [Event::USER_STREAM_ADD=>'handleAddEvent'];
    }

    public  function handleAddEvent(EventInterface $event)
    {

        $data['uid'] = $event->getParam(0);
        $data['integral'] =$event->getParam(1);
        $data['balance'] =$event->getParam(2);
        $data['type'] = $event->getParam(3);
        $data['name'] = $event->getParam(4);
        $data['description'] = $event->getParam(5);
        $data['created_at'] =time();
        $data['stream_no'] ='S'.getOrderSn();

        if(!$this->streamDao->addData($data)) throw new ApiException('系统繁忙');
    }

}